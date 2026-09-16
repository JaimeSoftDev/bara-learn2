<?php

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Question $resource
 */
class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar_url' => $this->user->avatar_url,
            ]),
            'answers' => $this->whenLoaded('answers', fn () => $this->answers->map(fn ($answer) => [
                'id' => $answer->id,
                'body' => $answer->body,
                'is_instructor_answer' => $answer->is_instructor_answer,
                'created_at' => $answer->created_at,
                'user' => [
                    'id' => $answer->user->id,
                    'name' => $answer->user->name,
                    'avatar_url' => $answer->user->avatar_url,
                ],
            ])),
        ];
    }
}
