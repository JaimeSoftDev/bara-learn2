<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Str;

class CertificateService
{
    public function progressPercent(int $userId, Course $course): int
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

    public function maybeIssue(User $user, Course $course): bool
    {
        if ($this->progressPercent($user->id, $course) < 100) {
            return false;
        }

        if (! $course->hasPassedAllQuizzes($user)) {
            return false;
        }

        $certificate = Certificate::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['code' => (string) Str::uuid(), 'issued_at' => now()]
        );

        return $certificate->wasRecentlyCreated;
    }
}
