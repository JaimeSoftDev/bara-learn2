<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TutoringMessageResource;
use App\Http\Resources\TutoringThreadResource;
use App\Models\TutoringThread;
use Illuminate\Http\Request;

class TeacherTutoringController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $threads = TutoringThread::query()
            ->with(['course', 'student', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('course', fn ($q2) => $q2->where('teacher_id', $user->id)))
            ->orderByDesc('updated_at')
            ->get();

        return TutoringThreadResource::collection($threads);
    }

    public function show(Request $request, TutoringThread $thread)
    {
        $this->authorizeTeacher($request, $thread);

        $thread->load(['messages.sender', 'course', 'student']);
        $thread->markReadBy($request->user());

        return new TutoringThreadResource($thread);
    }

    public function sendMessage(Request $request, TutoringThread $thread)
    {
        $this->authorizeTeacher($request, $thread);

        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);

        $message = $thread->messages()->create(['sender_id' => $request->user()->id, 'body' => $data['body']]);
        $thread->touch();
        $thread->markReadBy($request->user());

        return (new TutoringMessageResource($message->load('sender')))->response()->setStatusCode(201);
    }

    public function markRead(Request $request, TutoringThread $thread)
    {
        $this->authorizeTeacher($request, $thread);

        $thread->markReadBy($request->user());

        return response()->json(['message' => 'ok']);
    }

    public function grantExtra(Request $request, TutoringThread $thread)
    {
        $this->authorizeTeacher($request, $thread);

        $data = $request->validate(['amount' => ['nullable', 'integer', 'min:1', 'max:50']]);

        $thread->increment('extra_questions', $data['amount'] ?? 5);

        return new TutoringThreadResource($thread->fresh(['course', 'student']));
    }

    private function authorizeTeacher(Request $request, TutoringThread $thread): void
    {
        $user = $request->user();

        abort_unless($user->isAdmin() || $thread->course->teacher_id === $user->id, 403);
    }
}
