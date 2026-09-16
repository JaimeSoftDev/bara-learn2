<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\Request;

class AnswerController extends Controller
{
    public function store(Request $request, Question $question)
    {
        $course = $question->lesson->section->course;
        $user = $request->user();

        abort_unless(
            $course->isEnrolled($user) || $user->id === $course->teacher_id || $user->isAdmin(),
            403
        );

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $answer = $question->answers()->create([
            'user_id' => $user->id,
            'body' => $data['body'],
            'is_instructor_answer' => $user->id === $course->teacher_id,
        ]);

        return response()->json($answer->load('user'), 201);
    }

    public function destroy(Request $request, Question $question, Answer $answer)
    {
        $this->authorize('delete', $answer);
        abort_unless($answer->question_id === $question->id, 404);

        $answer->delete();

        return response()->json(status: 204);
    }
}
