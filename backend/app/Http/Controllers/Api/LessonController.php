<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lesson\StoreLessonRequest;
use App\Http\Resources\LessonResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Http\Request;
use Stevebauman\Purify\Facades\Purify;

class LessonController extends Controller
{
    public function store(StoreLessonRequest $request, Course $course, Section $section)
    {
        abort_unless($section->course_id === $course->id, 404);

        $data = $request->validated();
        $position = $data['position'] ?? ($section->lessons()->max('position') + 1);

        $lesson = $section->lessons()->create([
            'title' => $data['title'],
            'youtube_url' => $data['youtube_url'],
            'youtube_video_id' => Lesson::extractYoutubeId($data['youtube_url']),
            'content' => isset($data['content']) ? Purify::clean($data['content']) : null,
            'position' => $position,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'is_preview' => $data['is_preview'] ?? false,
        ]);

        return (new LessonResource($lesson))->response()->setStatusCode(201);
    }

    public function update(Request $request, Course $course, Section $section, Lesson $lesson)
    {
        $this->authorize('update', $course);
        abort_unless($section->course_id === $course->id && $lesson->section_id === $section->id, 404);

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'youtube_url' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'is_preview' => ['nullable', 'boolean'],
        ]);

        if (isset($data['youtube_url'])) {
            $videoId = Lesson::extractYoutubeId($data['youtube_url']);
            abort_if(! $videoId, 422, 'La URL de YouTube no es válida.');
            $data['youtube_video_id'] = $videoId;
        }

        if (array_key_exists('content', $data) && $data['content']) {
            $data['content'] = Purify::clean($data['content']);
        }

        $lesson->update($data);

        return new LessonResource($lesson);
    }

    public function destroy(Request $request, Course $course, Section $section, Lesson $lesson)
    {
        $this->authorize('update', $course);
        abort_unless($section->course_id === $course->id && $lesson->section_id === $section->id, 404);

        $lesson->delete();

        return response()->json(status: 204);
    }
}
