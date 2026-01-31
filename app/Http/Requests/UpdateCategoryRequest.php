<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // ফিক্স: আমরা সরাসরি রাউট থেকে প্যারামিটার ধরছি
        // এটি সরাসরি ID রিটার্ন করবে (যেমন: 2)
        $categoryId = $this->route('category');

        return [
            // unique চেকের সময় এই ID কে ইগনোর করবে
            'name' => 'required|string|max:255|unique:categories,name,' . $categoryId,
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active' => 'boolean'
        ];
    }
}
