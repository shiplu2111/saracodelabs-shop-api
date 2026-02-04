<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * 🟢 1. Get All Products
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'variants'])
            ->where('is_active', true);

        // --- Search & Filter Logic (Same as before) ---
        $query->when($request->search, function ($q) use ($request) {
            $q->where(function ($subQ) use ($request) {
                $subQ->where('name', 'like', '%' . $request->search . '%')
                     ->orWhere('short_description', 'like', '%' . $request->search . '%')
                     ->orWhere('tags', 'like', '%' . $request->search . '%');
            });
        });

        $query->when($request->category_id, function ($q) use ($request) {
            $q->where('category_id', $request->category_id);
        });

        $query->when($request->brand_id, function ($q) use ($request) {
            $q->where('brand_id', $request->brand_id);
        });

        $query->when($request->min_price, function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('price', '>=', $request->min_price)
                    ->orWhereHas('variants', function ($v) use ($request) {
                        $v->where('price', '>=', $request->min_price)
                          ->orWhere('discount_price', '>=', $request->min_price);
                    });
            });
        });

        $query->when($request->max_price, function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('price', '<=', $request->max_price)->where('price', '>', 0)
                    ->orWhereHas('variants', function ($v) use ($request) {
                        $v->where('price', '<=', $request->max_price);
                    });
            });
        });

        // Sorting
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'price_low': $query->orderBy('price', 'asc'); break;
                case 'price_high': $query->orderBy('price', 'desc'); break;
                default: $query->latest(); break;
            }
        } else {
            $query->latest();
        }

        $products = $query->paginate(12);

        // 🔥 TRANSFORM LOGIC (FIXED)
        $products->getCollection()->transform(function ($product) {
            // 1. Fix Thumbnail URL
            $product->thumbnail = $product->thumbnail ? url('storage/' . $product->thumbnail) : null;

            // 2. 🔥 Fix Gallery Images URL (NEW ADDITION)
            if (!empty($product->images)) {
                // If cast to array in model, use as is. If string, decode it.
                $imagesArray = is_string($product->images) ? json_decode($product->images, true) : $product->images;

                if (is_array($imagesArray)) {
                    $product->images = array_map(function($img) {
                        return url('storage/' . $img);
                    }, $imagesArray);
                }
            }

            // 3. Variant Prices Logic
            if ($product->has_variants && $product->variants->isNotEmpty()) {
                $prices = $product->variants->map(function ($variant) {
                    return $variant->discount_price > 0 ? $variant->discount_price : $variant->price;
                });
                $product->min_price = $prices->min();
                $product->max_price = $prices->max();
                $product->show_price_range = $product->min_price !== $product->max_price;
            } else {
                $effectivePrice = $product->discount_price > 0 ? $product->discount_price : $product->price;
                $product->min_price = $effectivePrice;
                $product->max_price = $effectivePrice;
                $product->show_price_range = false;
            }

            unset($product->variants);
            return $product;
        });

        return response()->json($products);
    }

    /**
     * 🟢 2. Single Product (Logic remains same, just ensuring images work)
     */
    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'brand', 'variants', 'reviews.user'])
            ->firstOrFail();

        // Fix Thumbnail
        $product->thumbnail = $product->thumbnail ? url('storage/' . $product->thumbnail) : null;

        // Fix Gallery Images
        if (!empty($product->images)) {
            $imagesArray = is_string($product->images) ? json_decode($product->images, true) : $product->images;
            if (is_array($imagesArray)) {
                $product->images = array_map(function($img) {
                    return url('storage/' . $img);
                }, $imagesArray);
            }
        }

        // Variant Prices Logic...
        if ($product->has_variants && $product->variants->isNotEmpty()) {
             $prices = $product->variants->map(function ($variant) {
                return $variant->discount_price > 0 ? $variant->discount_price : $variant->price;
            });
            $product->min_price = $prices->min();
            $product->max_price = $prices->max();
        } else {
            $effectivePrice = $product->discount_price > 0 ? $product->discount_price : $product->price;
            $product->min_price = $effectivePrice;
            $product->max_price = $effectivePrice;
        }

        // Related Products
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->take(4)->get()
            ->map(function($p) {
                $p->thumbnail = $p->thumbnail ? url('storage/' . $p->thumbnail) : null;
                // Don't forget to fix images for related products too if you show them
                return $p;
            });

        return response()->json([
            'product' => $product,
            'related_products' => $relatedProducts
        ]);
    }

    // Categories and Brands methods...
    public function categories() {
        return response()->json(Category::withCount('products')->where('is_active', true)->get()->map(function ($cat) {
            $cat->image = $cat->image ? url('storage/' . $cat->image) : null;
            return $cat;
        }));
    }

    public function brands() {
        return response()->json(Brand::where('is_active', true)->get()->map(function($b){
             $b->logo = $b->logo ? url('storage/' . $b->logo) : null;
             return $b;
        }));
    }
}
