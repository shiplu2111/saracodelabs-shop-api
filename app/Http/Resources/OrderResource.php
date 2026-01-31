<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer' => [
                'id' => $this->user_id, // can be null for guest
                'name' => $this->customer_name,
                'phone' => $this->customer_phone,
                'email' => $this->customer_email,
                'shipping_address' => $this->shipping_address . ', ' . $this->city,
            ],
            'financials' => [
                'sub_total' => $this->sub_total,
                'shipping_cost' => $this->shipping_cost,
                'discount' => $this->discount_amount,
                'grand_total' => $this->grand_total,
            ],
            'status' => [
                'order_status' => $this->order_status,
                'payment_status' => $this->payment_status,
                'payment_method' => $this->payment_method,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'order_date' => $this->created_at->format('d M, Y h:i A'),
        ];
    }
}
