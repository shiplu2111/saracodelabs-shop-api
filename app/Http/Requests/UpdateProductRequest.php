<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // FIX: আমরা সরাসরি রাউট থেকে ID ধরছি (আগে এখানে ->id দেওয়া ছিল যা ভুল)
        $productId = $this->route('product');

        return [
            // Basic Info
            'name' => 'sometimes|required|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'tags' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'description' => 'nullable|string',
            'has_variants' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',

            // Images
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',

            // Simple Product Rules
            'price' => 'required_if:has_variants,false|nullable|numeric|min:0',
            'stock' => 'required_if:has_variants,false|nullable|integer|min:0',

            // SKU validation: Ignore current product ID
            'sku' => 'required_if:has_variants,false|nullable|string|unique:products,sku,' . $productId,

            'discount_price' => 'nullable|numeric|lt:price',

            // Variants
            'variants' => 'nullable|array',
            'variants.*.color' => 'nullable|string',
            'variants.*.size' => 'nullable|string',
            'variants.*.weight' => 'nullable|string',
            'variants.*.price' => 'required_with:variants|numeric|min:0',
            'variants.*.discount_price' => 'nullable|numeric|min:0',
            'variants.*.stock' => 'required_with:variants|integer|min:0',
            // Note: SKU update check for variants is handled in controller logic usually
        ];
    }
}
