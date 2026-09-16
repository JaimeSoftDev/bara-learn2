<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\CertificateService;
use Illuminate\Http\Request;

class LessonProgressController extends Controller
{
    public function __construct(private CertificateService $certificates) {}

    public function complete(Request $request, Lesson $lesson)
    {
        $course = $lesson->section->course;
        $user = $request->user();

        abort_unless($course->isEnrolled($user) || $user->id === $course->teacher_id || $user->isAdmin(), 403);

        LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['completed_at' => now()]
        );

        $certificateIssued = $this->certificates->maybeIssue($user, $course);

        return response()->json([
            'completed' => true,
            'progress_percent' => $this->certificates->progressPercent($user->id, $course),
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
            'progress_percent' => $this->certificates->progressPercent($user->id, $course),
        ]);
    }
}
