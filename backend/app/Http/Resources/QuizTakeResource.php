<?php

namespace App\Http\Resources;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Quiz $resource
 */
class QuizTakeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $bestAttempt = $this->bestAttemptFor($user);

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'section_id' => $this->section_id,
            'is_final_exam' => $this->isFinalExam(),
            'title' => $this->title,
            'passing_score' => $this->passing_score,
            'passed' => $this->hasBeenPassedBy($user),
            'best_attempt' => $bestAttempt ? [
                'score' => $bestAttempt->score,
                'passed' => $bestAttempt->passed,
                'submitted_at' => $bestAttempt->submitted_at,
            ] : null,
            'questions' => $this->questions->map(fn ($question) => [
                'id' => $question->id,
                'question' => $question->question,
                'options' => $question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                ])->values(),
            ])->values(),
        ];
    }
}
