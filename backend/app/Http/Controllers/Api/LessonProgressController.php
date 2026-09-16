<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LessonProgressController extends Controller
{
    public function complete(Request $request, Lesson $lesson)
    {
        $course = $lesson->section->course;
        $user = $request->user();

        abort_unless($course->isEnrolled($user) || $user->id === $course->teacher_id || $user->isAdmin(), 403);

        LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['completed_at' => now()]
        );

        $certificateIssued = $this->maybeIssueCertificate($user, $course);

        return response()->json([
            'completed' => true,
            'progress_percent' => $this->progressPercent($user->id, $course),
            'certificate_issued' => $certificateIssued,
        ]);
    }

    public function uncomplete(Request $request, Lesson $lesson)
    {
        $course = $lesson->section->course;
        $user = $request->user();

        LessonProgress::where('user_id', $user->id)->where('lesson_id', $lesson->id)->delete();

        return response()->json([
            'completed' => false,
            'progress_percent' => $this->progressPercent($user->id, $course),
        ]);
    }

    private function progressPercent(int $userId, $course): int
    {
        $lessonIds = $course->lessons()->pluck('lessons.id');
        $total = $lessonIds->count();

        if ($total === 0) {
            return 0;
        }

        $completed = LessonProgress::where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->whereIn('lesson_id', $lessonIds)
            ->count();

        return (int) round($completed / $total * 100);
    }

    private function maybeIssueCertificate($user, $course): bool
    {
        if ($this->progressPercent($user->id, $course) < 100) {
            return false;
        }

        $certificate = Certificate::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['code' => (string) Str::uuid(), 'issued_at' => now()]
        );

        return $certificate->wasRecentlyCreated;
    }
}
