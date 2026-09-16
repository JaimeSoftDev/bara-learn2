<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizResource;
use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        $quizzes = $course->quizzes()->with('questions.options')->orderByRaw('section_id is null')->orderBy('section_id')->get();

        return QuizResource::collection($quizzes);
    }

    public function store(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'title' => ['required', 'string', 'max:255'],
            'passing_score' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! empty($data['section_id'])) {
            $section = $course->sections()->whereKey($data['section_id'])->firstOrFail();
            abort_if($section->quiz()->exists(), 422, 'Esta sección ya tiene un examen.');
        } else {
            abort_if($course->finalQuiz(), 422, 'El curso ya tiene un examen final.');
        }

        $quiz = $course->quizzes()->create([
            'section_id' => $data['section_id'] ?? null,
            'title' => $data['title'],
            'passing_score' => $data['passing_score'] ?? 70,
        ]);

        return (new QuizResource($quiz->load('questions.options')))->response()->setStatusCode(201);
    }

    public function update(Request $request, Course $course, Quiz $quiz)
    {
        $this->authorize('update', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'passing_score' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $quiz->update($data);

        return new QuizResource($quiz->load('questions.options'));
    }

    public function destroy(Request $request, Course $course, Quiz $quiz)
    {
        $this->authorize('update', $course);
        abort_unless($quiz->course_id === $course->id, 404);

        $quiz->delete();

        return response()->json(status: 204);
    }
}
