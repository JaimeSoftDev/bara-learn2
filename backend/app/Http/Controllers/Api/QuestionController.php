<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\Models\Lesson;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Lesson $lesson)
    {
        $questions = $lesson->questions()
            ->with(['user', 'answers.user'])
            ->orderByDesc('created_at')
            ->get();

        return QuestionResource::collection($questions);
    }

    public function store(Request $request, Lesson $lesson)
    {
        $course = $lesson->section->course;
        $user = $request->user();

        abort_unless(
            $course->isEnrolled($user) || $user->id === $course->teacher_id || $user->isAdmin(),
            403,
            'Debes estar inscrito en el curso para preguntar.'
        );

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $question = $lesson->questions()->create([
            'user_id' => $user->id,
            ...$data,
        ]);

        return (new QuestionResource($question->load('user')))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Lesson $lesson, Question $question)
    {
        $this->authorize('delete', $question);
        abort_unless($question->lesson_id === $lesson->id, 404);

        $question->delete();

        return response()->json(status: 204);
    }
}
