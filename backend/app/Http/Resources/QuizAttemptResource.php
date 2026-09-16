<?php

namespace App\Http\Resources;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read QuizAttempt $resource
 */
class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'score' => $this->score,
            'passed' => $this->passed,
            'submitted_at' => $this->submitted_at,
            'answers' => $this->answers->map(fn ($answer) => [
                'question_id' => $answer->quiz_question_id,
                'question' => $answer->question->question,
                'selected_option_id' => $answer->quiz_option_id,
                'is_correct' => $answer->is_correct,
                'correct_option_id' => $answer->question->correctOption()?->id,
                'options' => $answer->question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct,
                ])->values(),
            ])->values(),
        ];
    }
}
