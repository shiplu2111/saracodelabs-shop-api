<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'color' => $this->color,
            'size' => $this->size,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            // If product exists, show thumbnail, else null
            'thumbnail' => $this->product ? url('storage/' . $this->product->thumbnail) : null,
        ];
    }
}
