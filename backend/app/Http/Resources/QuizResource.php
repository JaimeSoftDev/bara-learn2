<?php

namespace App\Http\Resources;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Quiz $resource
 */
class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'section_id' => $this->section_id,
            'is_final_exam' => $this->isFinalExam(),
            'title' => $this->title,
            'passing_score' => $this->passing_score,
            'questions_count' => $this->whenLoaded('questions', fn () => $this->questions->count()),
            'questions' => QuizQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
