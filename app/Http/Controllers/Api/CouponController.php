<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'cart_total' => 'required|numeric'
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        // 1. Basic Validation
        if (!$coupon) {
            return response()->json(['message' => 'Invalid coupon code'], 404);
        }

        if (!$coupon->isValid($request->cart_total)) {
            return response()->json(['message' => 'Coupon requirements not met'], 400);
        }

        // 2. Check User Usage Limit (1 Time Per User)
        if (auth('sanctum')->check()) {
            $alreadyUsed = CouponUsage::where('user_id', auth('sanctum')->id())
                ->where('coupon_id', $coupon->id)
                ->exists();

            if ($alreadyUsed) {
                return response()->json(['message' => 'You have already used this coupon'], 403);
            }
        }

        // 3. Calculate Discount
        $discount = 0;
        if ($coupon->type == 'fixed') {
            $discount = $coupon->value;
        } else {
            $discount = ($request->cart_total * $coupon->value) / 100;
        }

        return response()->json([
            'success' => true,
            'code' => $coupon->code,
            'discount_amount' => $discount,
            'message' => 'Coupon applied successfully!'
        ]);
    }
}
