<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
// use App\Http\Resources\UserResource; // namespace same hole lagbe na

class TicketReplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'attachment' => $this->attachment ? url('storage/' . $this->attachment) : null,

            // 🔥 User info using our new Resource
            'user' => new UserResource($this->user),

            'created_at' => $this->created_at->format('d M Y, h:i A'),
            'is_human_time' => $this->created_at->diffForHumans(),
        ];
    }
}
