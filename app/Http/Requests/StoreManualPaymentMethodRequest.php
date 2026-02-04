<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow all admins (Middleware handles security)
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:mobile_banking,bank_transfer,others',
            'account_number' => 'required|string|max:50',
            'description' => 'nullable|string',
            'qr_code' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'is_active' => 'boolean'
        ];
    }
}
