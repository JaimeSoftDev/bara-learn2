<?php

namespace App\Http\Resources;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Section $resource
 */
class SectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'position' => $this->position,
            'lessons' => LessonResource::collection($this->whenLoaded('lessons')),
            'quiz' => $this->whenLoaded('quiz', fn () => $this->quiz ? new QuizSummaryResource($this->quiz) : null),
        ];
    }
}
