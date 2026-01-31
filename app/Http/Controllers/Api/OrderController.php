<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\UserAddress;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Http\Requests\PlaceOrderRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\ShippingCharge;

class OrderController extends Controller
{
    public function store(PlaceOrderRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // --- 1. Resolve Shipping Address ---
            $shippingData = [];

            if (!empty($request->address_id) && auth('sanctum')->check()) {
                // Fetch saved address strictly for the authenticated user
                $savedAddress = UserAddress::where('id', $request->address_id)
                    ->where('user_id', auth('sanctum')->id())
                    ->firstOrFail();

                $shippingData = [
                    'name' => $savedAddress->name,
                    'phone' => $savedAddress->phone,
                    'email' => $savedAddress->email ?? auth('sanctum')->user()->email,
                    'address' => $savedAddress->address,
                    'city' => $savedAddress->city,
                    'postal_code' => $savedAddress->postal_code,
                ];
            } else {
                // Use manual input
                $shippingData = [
                    'name' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                    'email' => $validated['customer_email'] ?? (auth('sanctum')->user()->email ?? null),
                    'address' => $validated['shipping_address'],
                    'city' => $validated['city'],
                    'postal_code' => $validated['postal_code'] ?? null,
                ];
            }

            // --- 2. Process Items & Calculate Subtotal ---
            $orderItemsData = [];
            $subTotal = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                $price = 0;
                $sku = null;
                $color = null;
                $size = null;

                // Check if it is a Variant or Simple Product
                if (isset($item['product_variant_id']) && $item['product_variant_id']) {
                    $variant = ProductVariant::findOrFail($item['product_variant_id']);

                    // Stock Validation
                    if ($variant->stock < $item['quantity']) {
                        throw new \Exception("Stock out for product: " . $product->name . " (" . $variant->size . ")");
                    }

                    $price = $variant->discount_price ?? $variant->price;

                    // Deduct Stock
                    $variant->decrement('stock', $item['quantity']);

                    $sku = $variant->sku;
                    $color = $variant->color;
                    $size = $variant->size;

                } else {
                    // Simple Product Logic
                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Stock out for product: " . $product->name);
                    }

                    $price = $product->discount_price ?? $product->price;

                    // Deduct Stock
                    $product->decrement('stock', $item['quantity']);

                    $sku = $product->sku;
                }

                $totalPrice = $price * $item['quantity'];
                $subTotal += $totalPrice;

                // Prepare Item Data Snapshot
                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'product_name' => $product->name,
                    'color' => $color,
                    'size' => $size,
                    'sku' => $sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $price,
                    'total_price' => $totalPrice,
                ];
            }
            // --- 3. Dynamic Shipping Calculation START ---
            $customerCity = strtolower(trim($shippingData['city'])); // Normalize Input
            $shippingCost = 0;

            // Check if specific city exists (e.g., dhaka, khulna)
            $charge = ShippingCharge::where('city', $customerCity)->first();

            if ($charge) {
                $shippingCost = $charge->amount;
            } else {
                // If city not found, use 'default' or 'others' rate
                $defaultCharge = ShippingCharge::where('city', 'default')->first();

                // Fallback: If DB has no default, use 100 as safety
                $shippingCost = $defaultCharge ? $defaultCharge->amount : 100;
            }
            // --- Dynamic Shipping Calculation END ---
            // --- 3. Coupon Logic ---

            $discount = 0;
            $couponCode = null;
            $couponId = null;

            if ($request->has('coupon_code') && !empty($request->coupon_code)) {
                $coupon = Coupon::where('code', $request->coupon_code)->first();

                // Validate Coupon Validity (Expiry, Min Spend, etc.)
                if ($coupon && $coupon->isValid($subTotal)) {

                    // Check One-Time Usage per User
                    if (auth('sanctum')->check()) {
                        $alreadyUsed = CouponUsage::where('user_id', auth('sanctum')->id())
                            ->where('coupon_id', $coupon->id)
                            ->exists();

                        if ($alreadyUsed) {
                            throw new \Exception("You have already used this coupon.");
                        }
                    }

                    // Calculate Discount
                    if ($coupon->type == 'fixed') {
                        $discount = $coupon->value;
                    } else {
                        // Percent logic
                        $discount = ($subTotal * $coupon->value) / 100;
                    }

                    $couponCode = $coupon->code;
                    $couponId = $coupon->id;
                } else {
                    // Optional: You can throw exception here if you want to stop order on invalid coupon
                    // throw new \Exception("Invalid coupon code or minimum spend not met.");
                }
            }

            // Final Calculation
            $grandTotal = ($subTotal + $shippingCost) - $discount;


            // --- 4. Create Order ---
            $order = Order::create([
                'user_id' => auth('sanctum')->id() ?? null,
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'sub_total' => $subTotal,
                'shipping_cost' => $shippingCost,
                'discount_amount' => $discount,
                'coupon_code' => $couponCode,
                'grand_total' => $grandTotal,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'order_status' => 'pending',

                // Shipping Info
                'customer_name' => $shippingData['name'],
                'customer_phone' => $shippingData['phone'],
                'customer_email' => $shippingData['email'],
                'shipping_address' => $shippingData['address'],
                'city' => $shippingData['city'],
                'postal_code' => $shippingData['postal_code'],
                'order_notes' => $validated['order_notes'] ?? null,
            ]);

            // --- 5. Save Order Items ---
            foreach ($orderItemsData as $item) {
                $order->items()->create($item);
            }

            // --- 6. Record Coupon Usage ---
            if ($couponId && auth('sanctum')->check()) {
                CouponUsage::create([
                    'user_id' => auth('sanctum')->id(),
                    'coupon_id' => $couponId,
                    'order_id' => $order->id
                ]);

                // Increment global usage count
                Coupon::where('id', $couponId)->increment('used_count');
            }

            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully',
                'order_number' => $order->order_number,
                'grand_total' => $order->grand_total
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Order Failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
