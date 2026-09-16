<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    public function overview(Request $request)
    {
        $teacherId = $request->user()->id;
        $courseIds = Course::where('teacher_id', $teacherId)->pluck('id');

        $paidOrders = Order::whereIn('course_id', $courseIds)->where('status', 'paid');

        return response()->json([
            'courses_count' => $courseIds->count(),
            'published_courses_count' => Course::whereIn('id', $courseIds)->where('status', 'published')->count(),
            'students_count' => Enrollment::whereIn('course_id', $courseIds)->distinct()->count('user_id'),
            'revenue_stripe' => round((clone $paidOrders)->where('payment_method', 'stripe')->sum('amount_cents') / 100, 2),
            'revenue_cash' => round((clone $paidOrders)->where('payment_method', 'cash')->sum('amount_cents') / 100, 2),
            'top_courses' => Course::whereIn('id', $courseIds)
                ->withCount('enrollments')
                ->orderByDesc('enrollments_count')
                ->take(5)
                ->get(['id', 'title', 'slug'])
                ->map(fn ($c) => ['id' => $c->id, 'title' => $c->title, 'slug' => $c->slug, 'students' => $c->enrollments_count]),
        ]);
    }

    public function orders(Request $request)
    {
        $courseIds = Course::where('teacher_id', $request->user()->id)->pluck('id');

        $orders = Order::whereIn('course_id', $courseIds)
            ->with(['user', 'course', 'createdBy'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return OrderResource::collection($orders);
    }
}
