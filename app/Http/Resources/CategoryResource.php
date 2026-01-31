<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image ? url('storage/' . $this->image) : null, // Return Full URL
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,

            // Recursive Resource Loading (If children are loaded in controller)
            'children' => CategoryResource::collection($this->whenLoaded('children')),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
