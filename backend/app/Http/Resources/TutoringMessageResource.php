<?php

namespace App\Http\Resources;

use App\Models\TutoringMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read TutoringMessage $resource
 */
class TutoringMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'avatar_url' => $this->sender->avatar_url,
            ],
            'is_mine' => $this->sender_id === $request->user()?->id,
            'created_at' => $this->created_at,
        ];
    }
}
