<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product->name,
            'slug' => $this->product->slug,
            'thumbnail' => url('storage/' . $this->product->thumbnail),
            'price' => $this->product->price,
            'discount_price' => $this->product->discount_price,
            'stock_status' => $this->product->stock > 0 ? 'In Stock' : 'Out of Stock',
        ];
    }
}
