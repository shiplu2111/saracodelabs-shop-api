<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\UserAddress;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\ShippingCharge;
use App\Models\PaymentGateway; // Import this to check activity
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Controllers\Api\SslCommerzController;
// use App\Http\Controllers\Api\BkashController; // Future Import
// use App\Http\Controllers\Api\NagadController; // Future Import
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewOrderNotification;

class OrderController extends Controller
{
    public function store(PlaceOrderRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // --- 1. Check if Gateway is Active (Security Check) ---
            if (in_array($validated['payment_method'], ['sslcommerz', 'bkash', 'nagad', 'rocket'])) {
                $gateway = PaymentGateway::where('keyword', $validated['payment_method'])
                    ->where('is_active', true)
                    ->first();

                if (!$gateway) {
                    throw new \Exception("Payment method {$validated['payment_method']} is currently disabled.");
                }
            }

            // --- 2. Resolve Shipping Address ---
            $shippingData = [];
            if (!empty($request->address_id) && auth('sanctum')->check()) {
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
                $shippingData = [
                    'name' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                    'email' => $validated['customer_email'] ?? (auth('sanctum')->user()->email ?? null),
                    'address' => $validated['shipping_address'],
                    'city' => $validated['city'],
                    'postal_code' => $validated['postal_code'] ?? null,
                ];
            }

            // --- 3. Process Items & Inventory ---
            $orderItemsData = [];
            $subTotal = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                $price = 0;
                $sku = null;
                $color = null;
                $size = null;

                if (isset($item['product_variant_id']) && $item['product_variant_id']) {
                    $variant = ProductVariant::findOrFail($item['product_variant_id']);
                    if ($variant->stock < $item['quantity']) {
                        throw new \Exception("Stock out for: " . $product->name . " (" . $variant->size . ")");
                    }
                    $price = $variant->discount_price ?? $variant->price;
                    $variant->decrement('stock', $item['quantity']);
                    $sku = $variant->sku;
                    $color = $variant->color;
                    $size = $variant->size;
                } else {
                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Stock out for: " . $product->name);
                    }
                    $price = $product->discount_price ?? $product->price;
                    $product->decrement('stock', $item['quantity']);
                    $sku = $product->sku;
                }

                $totalPrice = $price * $item['quantity'];
                $subTotal += $totalPrice;

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

            // --- 4. Shipping Calculation ---
            $customerCity = strtolower(trim($shippingData['city']));
            $shippingCost = 0;
            $charge = ShippingCharge::where('city', $customerCity)->first();

            if ($charge) {
                $shippingCost = $charge->amount;
            } else {
                $defaultCharge = ShippingCharge::where('city', 'default')->first();
                $shippingCost = $defaultCharge ? $defaultCharge->amount : 100;
            }

            // --- 5. Coupon Logic ---
            $discount = 0;
            $couponCode = null;
            $couponId = null;

            if ($request->has('coupon_code') && !empty($request->coupon_code)) {
                $coupon = Coupon::where('code', $request->coupon_code)->first();
                if ($coupon && $coupon->isValid($subTotal)) {
                    if (auth('sanctum')->check()) {
                        $alreadyUsed = CouponUsage::where('user_id', auth('sanctum')->id())
                            ->where('coupon_id', $coupon->id)->exists();
                        if ($alreadyUsed) throw new \Exception("Coupon already used.");
                    }

                    $discount = ($coupon->type == 'fixed') ? $coupon->value : ($subTotal * $coupon->value) / 100;
                    $couponCode = $coupon->code;
                    $couponId = $coupon->id;
                }
            }

            $grandTotal = ($subTotal + $shippingCost) - $discount;

            // --- 6. Manual Proof Upload ---
            $paymentProofPath = null;
            if ($validated['payment_method'] === 'manual' && $request->hasFile('payment_proof')) {
                $paymentProofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
            }

            // --- 7. Create Order ---
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
                'manual_payment_method_id' => $validated['manual_method_id'] ?? null,
                'payment_id' => $validated['transaction_id'] ?? null,
                'payment_proof' => $paymentProofPath,
                'customer_name' => $shippingData['name'],
                'customer_phone' => $shippingData['phone'],
                'customer_email' => $shippingData['email'],
                'shipping_address' => $shippingData['address'],
                'city' => $shippingData['city'],
                'postal_code' => $shippingData['postal_code'],
                'order_notes' => $validated['order_notes'] ?? null,
            ]);

            foreach ($orderItemsData as $item) {
                $order->items()->create($item);
            }

            if ($couponId && auth('sanctum')->check()) {
                CouponUsage::create(['user_id' => auth('sanctum')->id(), 'coupon_id' => $couponId, 'order_id' => $order->id, 'discount_amount' => $discount]);
                Coupon::where('id', $couponId)->increment('used_count');
            }

            // --- 8. Payment Gateway Routing ---

            // A. SSLCommerz
            if ($validated['payment_method'] === 'sslcommerz') {
                $paymentUrl = (new SslCommerzController)->initPayment($order, $shippingData);
                DB::commit();
                return response()->json(['message' => 'Redirecting...', 'order_id' => $order->id, 'url' => $paymentUrl]);
            }

            // B. BKash (API)
            if ($validated['payment_method'] === 'bkash') {
                DB::commit();
                // TODO: Uncomment when BkashController is ready
                // return (new BkashController)->initPayment($order);
                return response()->json(['message' => 'Bkash API not integrated yet'], 501);
            }

            // C. Nagad (API)
            if ($validated['payment_method'] === 'nagad') {
                DB::commit();
                // TODO: Uncomment when NagadController is ready
                // return (new NagadController)->initPayment($order);
                return response()->json(['message' => 'Nagad API not integrated yet'], 501);
            }

            // D. Rocket (API)
            if ($validated['payment_method'] === 'rocket') {
                DB::commit();
                // TODO: Uncomment when RocketController is ready
                return response()->json(['message' => 'Rocket API not integrated yet'], 501);
            }

            // E. COD or Manual
            DB::commit();
            return response()->json([
                'message' => 'Order placed successfully',
                'order_number' => $order->order_number,
                'grand_total' => $order->grand_total
            ], 201);
        $admins = User::role(['super-admin', 'employee'])->get();
        Notification::send($admins, new NewOrderNotification($order));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Order Failed', 'error' => $e->getMessage()], 400);
        }
    }
}
