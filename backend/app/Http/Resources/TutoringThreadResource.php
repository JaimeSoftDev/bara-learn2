<?php

namespace App\Http\Resources;

use App\Models\TutoringThread;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read TutoringThread $resource
 */
class TutoringThreadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'course' => [
                'id' => $this->course->id,
                'title' => $this->course->title,
                'slug' => $this->course->slug,
            ],
            'student' => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'avatar_url' => $this->student->avatar_url,
            ],
            'questions_used' => $this->questionsUsed(),
            'questions_limit' => $this->questionsLimit(),
            'questions_remaining' => $this->questionsRemaining(),
            'unread_count' => $user ? $this->unreadCountFor($user) : 0,
            'last_message' => $this->whenLoaded('messages', function () {
                $last = $this->messages->last();

                return $last ? [
                    'body' => $last->body,
                    'sender_id' => $last->sender_id,
                    'created_at' => $last->created_at,
                ] : null;
            }),
            'messages' => TutoringMessageResource::collection($this->whenLoaded('messages')),
            'updated_at' => $this->updated_at,
        ];
    }
}
