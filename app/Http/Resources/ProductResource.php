<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_description' => $this->short_description,
            'tags' => $this->tags ? explode(',', $this->tags) : [], // Comma separated to Array
            'slug' => $this->slug,
            'sku' => $this->sku,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'brand' => new BrandResource($this->whenLoaded('brand')),

            // Image URLs
            'thumbnail' => $this->thumbnail ? url('storage/' . $this->thumbnail) : null,
            'images' => $this->images ? array_map(fn($img) => url('storage/' . $img), $this->images) : [],

            'description' => $this->description,

            // Pricing
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'stock' => $this->stock,

            'has_variants' => $this->has_variants,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,

            // Load variants if they exist
            'variants' => $this->when($this->has_variants, $this->variants),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
