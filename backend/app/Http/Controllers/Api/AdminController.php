<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseSummaryResource;
use App\Http\Resources\UserResource;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function overview()
    {
        return response()->json([
            'users_count' => User::count(),
            'teachers_count' => User::where('role', 'teacher')->count(),
            'students_count' => User::where('role', 'student')->count(),
            'courses_count' => Course::count(),
            'published_courses_count' => Course::where('status', 'published')->count(),
            'revenue_total' => round(Order::where('status', 'paid')->sum('amount_cents') / 100, 2),
            'revenue_stripe' => round(Order::where('status', 'paid')->where('payment_method', 'stripe')->sum('amount_cents') / 100, 2),
            'revenue_cash' => round(Order::where('status', 'paid')->where('payment_method', 'cash')->sum('amount_cents') / 100, 2),
        ]);
    }

    public function users(Request $request)
    {
        $query = User::query()->orderByDesc('created_at');

        if ($role = $request->string('role')->toString()) {
            $query->where('role', $role);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }

        return UserResource::collection($query->paginate(20));
    }

    public function updateUserRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['student', 'teacher', 'admin'])],
        ]);

        $user->update(['role' => $data['role']]);

        return new UserResource($user);
    }

    public function courses(Request $request)
    {
        $query = Course::query()->with(['teacher', 'category'])->orderByDesc('created_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return CourseSummaryResource::collection($query->paginate(20));
    }

    public function deleteCourse(Course $course)
    {
        $course->delete();

        return response()->json(status: 204);
    }
}
