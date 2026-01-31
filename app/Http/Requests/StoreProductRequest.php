<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Basic Info
            'name' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'tags' => 'nullable|string', // Comma separated: "shirt,cotton,red"
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'description' => 'required|string',
            'has_variants' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',

            // Images
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images' => 'nullable|array', // Gallery
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',

            // Scenario A: Simple Product (No Variants)
            'price' => 'required_if:has_variants,false|nullable|numeric|min:0',
            'stock' => 'required_if:has_variants,false|nullable|integer|min:0',
            'sku' => 'required_if:has_variants,false|nullable|string|unique:products,sku',
            'discount_price' => 'nullable|numeric|lt:price', // Must be less than regular price

            // Scenario B: Variable Product (Has Variants)
            'variants' => 'required_if:has_variants,true|array',
            'variants.*.color' => 'nullable|string',
            'variants.*.size' => 'nullable|string',
            'variants.*.weight' => 'nullable|string', // e.g., 1KG (Optional)
            'variants.*.price' => 'required_with:variants|numeric|min:0',
            'variants.*.discount_price' => 'nullable|numeric|lt:variants.*.price',
            'variants.*.stock' => 'required_with:variants|integer|min:0',
            'variants.*.sku' => 'required_with:variants|string|distinct|unique:product_variants,sku',
        ];
    }
}
