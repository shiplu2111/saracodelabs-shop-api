<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Hybrid Address Logic
            // If address_id is provided, we fetch from DB. If not, we require manual input.
            'address_id' => 'nullable|exists:user_addresses,id',

            'customer_name' => 'required_without:address_id|nullable|string|max:255',
            'customer_phone' => 'required_without:address_id|nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'shipping_address' => 'required_without:address_id|nullable|string',
            'city' => 'required_without:address_id|nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',

            'order_notes' => 'nullable|string',
            'coupon_code' => 'nullable|string',

            // Cart Items
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',

           // Allow all gateways
        'payment_method' => 'required|in:cod,sslcommerz,manual,bkash,nagad,rocket',

        // Manual validation rules (Conditional)
        'manual_method_id' => 'required_if:payment_method,manual|exists:manual_payment_methods,id',
        'transaction_id' => 'required_if:payment_method,manual|string|max:50',
        'payment_proof' => 'nullable|image|max:2048',
        ];
    }

}
