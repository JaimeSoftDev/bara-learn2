<?php

namespace App\Http\Resources;

use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read QuizQuestion $resource
 */
class QuizQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'position' => $this->position,
            'options' => QuizOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
