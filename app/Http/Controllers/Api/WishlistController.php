<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Http\Resources\WishlistResource;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlist = auth()->user()->wishlist()->with('product')->latest()->get();
        return WishlistResource::collection($wishlist);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = auth()->user();

        $wishlist = Wishlist::firstOrCreate([
            'user_id' => $user->id,
            'product_id' => $request->product_id
        ]);

        return response()->json(['message' => 'Product added to wishlist'], 201);
    }

    public function destroy($productId)
    {
        // Delete by Product ID (Easier for Frontend toggle)
        $deleted = Wishlist::where('user_id', auth()->user()->id)
            ->where('product_id', $productId)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Removed from wishlist']);
        }

        return response()->json(['message' => 'Product not found in wishlist'], 404);
    }
}
