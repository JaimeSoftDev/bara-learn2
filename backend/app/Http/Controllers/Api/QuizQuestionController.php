<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizQuestionResource;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;

class QuizQuestionController extends Controller
{
    public function store(Request $request, Course $course, Quiz $quiz)
    {
        $this->authorize('update', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        $data = $this->validateQuestion($request);

        $question = $quiz->questions()->create([
            'question' => $data['question'],
            'position' => $quiz->questions()->max('position') + 1,
        ]);

        foreach ($data['options'] as $index => $option) {
            $question->options()->create([
                'option_text' => $option['option_text'],
                'is_correct' => $option['is_correct'],
                'position' => $index,
            ]);
        }

        return (new QuizQuestionResource($question->load('options')))->response()->setStatusCode(201);
    }

    public function update(Request $request, Course $course, Quiz $quiz, QuizQuestion $question)
    {
        $this->authorize('update', $course);
        abort_unless($quiz->course_id === $course->id, 404);
        abort_unless($question->quiz_id === $quiz->id, 404);

        $data = $this->validateQuestion($request);

        $question->update(['question' => $data['question']]);

        $question->options()->delete();
        foreach ($data['options'] as $index => $option) {
            $question->options()->create([
                'option_text' => $option['option_text'],
                'is_correct' => $option['is_correct'],
                'position' => $index,
            ]);
        }

        return new QuizQuestionResource($question->load('options'));
    }

    public function destroy(Request $request, Course $course, Quiz $quiz, QuizQuestion $question)
    {
        $this->authorize('update', $course);
        abort_unless($quiz->course_id === $course->id, 404);
        abort_unless($question->quiz_id === $quiz->id, 404);

        $question->delete();

        return response()->json(status: 204);
    }

    public function reorder(Request $request, Course $course, Quiz $quiz)
    {
        $this->authorize('update', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        $data = $request->validate([
            'questions' => ['required', 'array'],
            'questions.*.id' => ['required', 'integer', 'exists:quiz_questions,id'],
            'questions.*.position' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($data['questions'] as $item) {
            $quiz->questions()->where('id', $item['id'])->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Orden actualizado.']);
    }

    private function validateQuestion(Request $request): array
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'options' => ['required', 'array', 'min:2', 'max:8'],
            'options.*.option_text' => ['required', 'string', 'max:500'],
            'options.*.is_correct' => ['required', 'boolean'],
        ]);

        $correctCount = collect($data['options'])->filter(fn ($option) => $option['is_correct'])->count();

        abort_unless($correctCount === 1, 422, 'Debe haber exactamente una opción correcta.');

        return $data;
    }
}
