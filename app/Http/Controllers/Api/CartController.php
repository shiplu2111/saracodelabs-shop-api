<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Get User Cart
     */
    public function index()
    {
        $cart = Cart::with(['items.product', 'items.variant'])
                    ->firstOrCreate(['user_id' => auth()->id()]);

        return response()->json($cart);
    }

    /**
     * Add Item to Cart
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = auth()->user();

        // Get or Create Cart
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Check Stock
        if ($request->product_variant_id) {
            $variant = ProductVariant::find($request->product_variant_id);
            if ($variant->stock < $request->quantity) {
                return response()->json(['message' => 'Variant out of stock'], 400);
            }
        } else {
            $product = Product::find($request->product_id);
            if ($product->stock < $request->quantity) {
                return response()->json(['message' => 'Product out of stock'], 400);
            }
        }

        // Check if item already exists in cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->where('product_variant_id', $request->product_variant_id)
            ->first();

        if ($cartItem) {
            // Update quantity
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            // Create new item
            $cart->items()->create([
                'product_id' => $request->product_id,
                'product_variant_id' => $request->product_variant_id,
                'quantity' => $request->quantity
            ]);
        }

        return response()->json(['message' => 'Item added to cart'], 201);
    }

    /**
     * Update Item Quantity
     */
    public function update(Request $request, $itemId)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);

        $cartItem = CartItem::findOrFail($itemId);
        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json(['message' => 'Cart updated']);
    }

    /**
     * Remove Item
     */
    public function destroy($itemId)
    {
        CartItem::destroy($itemId);
        return response()->json(['message' => 'Item removed']);
    }
}
