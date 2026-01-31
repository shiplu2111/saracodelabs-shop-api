<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class BrandController extends Controller
{
    use FileUploadTrait;

    /**
     * Get all brands
     */
    public function index()
    {
        return BrandResource::collection(Brand::latest()->get());
    }

    /**
     * Get list for dropdowns (lighter response)
     */
    public function list()
    {
        return response()->json(Brand::select('id', 'name')->where('is_active', true)->get());
    }

    /**
     * Store new brand
     */
    public function store(StoreBrandRequest $request)
    {
        if (!auth()->user()->can('brand.create')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->uploadFile($request->file('logo'), 'brands');
        }

        $brand = Brand::create($data);

        return new BrandResource($brand);
    }

    /**
     * Show single brand
     */
    public function show($id)
    {
        return new BrandResource(Brand::findOrFail($id));
    }

    /**
     * Update brand
     */
    public function update(UpdateBrandRequest $request, $id)
    {
        if (!auth()->user()->can('brand.edit')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $brand = Brand::findOrFail($id);
        $data = $request->validated();

        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->name);
        }

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->uploadFile($request->file('logo'), 'brands', $brand->logo);
        }

        $brand->update($data);

        return new BrandResource($brand);
    }

    /**
     * Delete brand with Error Handling
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('brand.delete')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $brand = Brand::findOrFail($id);

        try {
            // Attempt to delete DB record
            $brand->delete();

            // If DB delete successful, delete image
            if ($brand->logo) {
                $this->deleteFile($brand->logo);
            }

            return response()->json(['message' => 'Brand deleted successfully']);

        } catch (QueryException $e) {
            // Handle Foreign Key Constraint (e.g., Brand attached to Products)
            if ($e->getCode() == "23000") {
                return response()->json([
                    'message' => 'Cannot delete this brand because it is linked to existing products.'
                ], 422);
            }

            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }
}
