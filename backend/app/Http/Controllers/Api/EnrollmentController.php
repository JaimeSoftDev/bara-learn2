<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GrantCashEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EnrollmentController extends Controller
{
    /** The authenticated student's own enrollments ("My learning"). */
    public function index(Request $request)
    {
        $enrollments = $request->user()->enrollments()
            ->with(['course.category', 'course.teacher'])
            ->orderByDesc('enrolled_at')
            ->get()
            ->each(function (Enrollment $enrollment) {
                $totalLessons = $enrollment->course->lessons()->count();
                $completed = $enrollment->user->id
                    ? LessonProgress::where('user_id', $enrollment->user_id)
                        ->whereNotNull('completed_at')
                        ->whereIn('lesson_id', $enrollment->course->lessons()->pluck('lessons.id'))
                        ->count()
                    : 0;

                $enrollment->setAttribute(
                    'progress_percent',
                    $totalLessons > 0 ? (int) round($completed / $totalLessons * 100) : 0
                );
            });

        return EnrollmentResource::collection($enrollments);
    }

    /** Enroll the current user in a free course instantly. */
    public function store(Request $request, Course $course)
    {
        abort_unless($course->status === 'published', 404);
        abort_unless($course->is_free, 422, 'Este curso es de pago, utiliza el checkout.');

        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $request->user()->id, 'course_id' => $course->id],
            ['source' => 'free', 'price_paid_cents' => 0, 'enrolled_at' => now()]
        );

        return (new EnrollmentResource($enrollment->load('course')))->response()->setStatusCode(201);
    }

    /** Teacher/admin: list students enrolled in a course with their progress. */
    public function students(Request $request, Course $course)
    {
        $this->authorize('manageStudents', $course);

        $lessonIds = $course->lessons()->pluck('lessons.id');
        $totalLessons = $lessonIds->count();

        $enrollments = $course->enrollments()
            ->with(['user', 'grantedBy'])
            ->orderByDesc('enrolled_at')
            ->get()
            ->map(function (Enrollment $enrollment) use ($lessonIds, $totalLessons) {
                $completed = LessonProgress::where('user_id', $enrollment->user_id)
                    ->whereNotNull('completed_at')
                    ->whereIn('lesson_id', $lessonIds)
                    ->count();

                $enrollment->setAttribute(
                    'progress_percent',
                    $totalLessons > 0 ? (int) round($completed / $totalLessons * 100) : 0
                );

                return $enrollment;
            });

        return EnrollmentResource::collection($enrollments);
    }

    /**
     * Teacher/admin: grant access to a course to a student who paid in
     * cash (or in person) rather than through Stripe. This records an
     * order for bookkeeping and an enrollment identical to a paid one.
     */
    public function grantCash(GrantCashEnrollmentRequest $request, Course $course)
    {
        $data = $request->validated();
        $student = User::where('email', $data['email'])->firstOrFail();

        if (! $student->isStudent()) {
            throw ValidationException::withMessages([
                'email' => 'Solo se puede inscribir a usuarios con rol de alumno.',
            ]);
        }

        if ($course->isEnrolled($student)) {
            throw ValidationException::withMessages([
                'email' => 'Este alumno ya está inscrito en el curso.',
            ]);
        }

        $amountCents = isset($data['amount_paid'])
            ? (int) round($data['amount_paid'] * 100)
            : $course->price_cents;

        $order = Order::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'amount_cents' => $amountCents,
            'currency' => config('services.stripe.currency', 'eur'),
            'payment_method' => 'cash',
            'status' => 'paid',
            'created_by' => $request->user()->id,
            'notes' => $data['notes'] ?? null,
            'paid_at' => now(),
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'source' => 'cash',
            'granted_by' => $request->user()->id,
            'price_paid_cents' => $amountCents,
            'enrolled_at' => now(),
        ]);

        return response()->json([
            'message' => "Acceso concedido a {$student->name}.",
            'order' => $order,
            'enrollment' => new EnrollmentResource($enrollment->load(['user', 'grantedBy'])),
        ], 201);
    }
}
