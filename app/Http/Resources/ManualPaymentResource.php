<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManualPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'type_formatted' => ucwords(str_replace('_', ' ', $this->type)), // e.g. "Mobile Banking"
            'account_number' => $this->account_number,
            'description' => $this->description,
            'qr_code' => $this->qr_code, // Model accessor will give full URL
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
