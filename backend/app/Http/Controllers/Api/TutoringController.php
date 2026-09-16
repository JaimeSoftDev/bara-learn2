<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TutoringMessageResource;
use App\Http\Resources\TutoringThreadResource;
use App\Models\Course;
use App\Models\TutoringThread;
use Illuminate\Http\Request;

class TutoringController extends Controller
{
    public function show(Request $request, Course $course)
    {
        $user = $request->user();
        abort_unless($course->isEnrolled($user), 403, 'Debes estar inscrito en el curso para acceder a las tutorías.');

        $thread = TutoringThread::firstOrCreate(['course_id' => $course->id, 'student_id' => $user->id]);
        // Lazily creating the thread on first visit is an implementation
        // detail; a GET here should always read as 200, never 201.
        $thread->wasRecentlyCreated = false;
        $thread->load(['messages.sender', 'course', 'student']);
        $thread->markReadBy($user);

        return new TutoringThreadResource($thread);
    }

    public function sendMessage(Request $request, Course $course)
    {
        $user = $request->user();
        abort_unless($course->isEnrolled($user), 403, 'Debes estar inscrito en el curso para acceder a las tutorías.');

        $thread = TutoringThread::firstOrCreate(['course_id' => $course->id, 'student_id' => $user->id]);

        abort_if(
            $thread->questionsRemaining() <= 0,
            422,
            'Has agotado tus preguntas de tutoría incluidas en este curso.'
        );

        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);

        $message = $thread->messages()->create(['sender_id' => $user->id, 'body' => $data['body']]);
        $thread->touch();
        $thread->markReadBy($user);

        return (new TutoringMessageResource($message->load('sender')))->response()->setStatusCode(201);
    }

    public function markRead(Request $request, Course $course)
    {
        $user = $request->user();
        abort_unless($course->isEnrolled($user), 403);

        $thread = TutoringThread::where('course_id', $course->id)->where('student_id', $user->id)->first();
        $thread?->markReadBy($user);

        return response()->json(['message' => 'ok']);
    }
}
