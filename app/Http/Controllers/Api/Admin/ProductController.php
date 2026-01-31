<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use FileUploadTrait;

    /**
     * Get all products
     */
    public function index()
    {
        $products = Product::with(['category', 'brand'])
            ->latest()
            ->paginate(10);

        return ProductResource::collection($products);
    }

    /**
     * Store a new product
     */
   /**
     * Store a new product
     */
    public function store(StoreProductRequest $request)
    {
        // 1. Permission Check
        if (!auth()->user()->can('product.create')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            DB::beginTransaction();

            $data = $request->validated();
            // Generate unique slug
            $data['slug'] = Str::slug($data['name']) . '-' . time();

            // 2. Upload Thumbnail
            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $this->uploadFile($request->file('thumbnail'), 'products/thumbnails');
            }

            // 3. Upload Gallery Images
            if ($request->hasFile('images')) {
                $galleryPaths = [];
                foreach ($request->file('images') as $image) {
                    $galleryPaths[] = $this->uploadFile($image, 'products/gallery');
                }
                $data['images'] = $galleryPaths;
            }

            // 4. Prepare Data & Create Parent Product
            // We must remove the 'variants' array from the main data because
            // the 'products' table does not have a 'variants' column.
            $productData = collect($data)->except(['variants'])->toArray();

            $product = Product::create($productData);

            // 5. Create Variants (if exists)
            if ($request->has_variants && !empty($request->variants)) {
                foreach ($request->variants as $variant) {
                    $product->variants()->create([
                        'color' => $variant['color'] ?? null,
                        'size' => $variant['size'] ?? null,
                        'weight' => $variant['weight'] ?? null,
                        'price' => $variant['price'],
                        'discount_price' => $variant['discount_price'] ?? null, // Added discount_price
                        'stock' => $variant['stock'],
                        'sku' => $variant['sku'],
                        'is_active' => true
                    ]);
                }
            }

            DB::commit();

            return new ProductResource($product->load('variants'));

        } catch (\Exception $e) {
            DB::rollBack();

            // 6. Rollback / Cleanup Logic
            // If the database transaction fails, we must delete the images
            // that were just uploaded to prevent "orphan files" in storage.

            if (isset($data['thumbnail'])) {
                $this->deleteFile($data['thumbnail']);
            }

            if (isset($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $img) {
                    $this->deleteFile($img);
                }
            }

            return response()->json([
                'message' => 'Product creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show Single Product
     */
    public function show($id)
    {
        $product = Product::with(['category', 'brand', 'variants'])->findOrFail($id);
        return new ProductResource($product);
    }

    /**
     * Update Product
     */
    /**
     * Update Product
     */
    public function update(UpdateProductRequest $request, $id)
    {
        if (!auth()->user()->can('product.edit')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product = Product::findOrFail($id);

        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Slug Update Logic
            if ($request->has('name')) {
                $data['slug'] = Str::slug($request->name) . '-' . $product->id;
            }

            // 1. Handle Thumbnail Update
            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $this->uploadFile($request->file('thumbnail'), 'products/thumbnails', $product->thumbnail);
            }

            // 2. Handle Gallery Images (Append new ones)
            if ($request->hasFile('images')) {
                $existingImages = $product->images ?? [];
                $newImages = [];
                foreach ($request->file('images') as $image) {
                    $newImages[] = $this->uploadFile($image, 'products/gallery');
                }
                // Merge old and new images
                $data['images'] = array_merge($existingImages, $newImages);
            }

            // --- FIX START ---
            // Exclude 'variants' from the data array before updating the product table
            $productData = collect($data)->except(['variants'])->toArray();

            // 3. Update Product Basic Info
            $product->update($productData);
            // --- FIX END ---

            // 4. Handle Variants Update (Full Replacement Strategy)
            // If the request contains 'variants' array, we replace the old ones.
            if ($request->has('variants') && $request->has_variants) {
                // Delete old variants
                $product->variants()->delete();

                // Create new variants
                foreach ($request->variants as $variant) {
                    $product->variants()->create([
                        'color' => $variant['color'] ?? null,
                        'size' => $variant['size'] ?? null,
                        'weight' => $variant['weight'] ?? null, // Included Weight
                        'price' => $variant['price'],
                        'discount_price' => $variant['discount_price'] ?? null, // Included Discount Price
                        'stock' => $variant['stock'],
                        'sku' => $variant['sku'],
                        'is_active' => true
                    ]);
                }
            }

            DB::commit();
            return new ProductResource($product->load('variants'));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Update failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete Product
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('product.delete')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product = Product::findOrFail($id);

        try {
            // 1. Delete Thumbnail
            if ($product->thumbnail) {
                $this->deleteFile($product->thumbnail);
            }

            // 2. Delete Gallery Images
            if ($product->images) {
                foreach ($product->images as $img) {
                    $this->deleteFile($img);
                }
            }

            // 3. Delete Variants & Product (Cascade will handle variants usually, but good to be explicit)
            $product->variants()->delete();
            $product->delete();

            return response()->json(['message' => 'Product deleted successfully']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Delete failed', 'error' => $e->getMessage()], 500);
        }
    }
}
