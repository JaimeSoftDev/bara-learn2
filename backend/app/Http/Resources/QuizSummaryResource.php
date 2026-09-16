<?php

namespace App\Http\Resources;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Quiz $resource
 */
class QuizSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $bestAttempt = $this->bestAttemptFor($user);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'passing_score' => $this->passing_score,
            'is_final_exam' => $this->isFinalExam(),
            'questions_count' => $this->questions()->count(),
            'passed' => $this->hasBeenPassedBy($user),
            'best_score' => $bestAttempt?->score,
        ];
    }
}
