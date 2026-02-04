<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource; // ✅ Ensure this line exists (optional if same namespace)

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'subject' => $this->subject,
            'priority' => ucfirst($this->priority),
            'status' => ucfirst($this->status),
            'created_at' => $this->created_at->format('d M Y'),

            // ✅ Now this will work because UserResource exists
            'user' => new UserResource($this->whenLoaded('user')),

            'replies' => TicketReplyResource::collection($this->whenLoaded('replies')),
        ];
    }
}
