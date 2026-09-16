<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseSummaryResource;
use App\Models\Course;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $courses = Course::query()
            ->whereIn('id', $request->user()->wishlist()->pluck('course_id'))
            ->with(['teacher', 'category'])
            ->get();

        return CourseSummaryResource::collection($courses);
    }

    public function toggle(Request $request, Course $course)
    {
        $existing = Wishlist::where('user_id', $request->user()->id)->where('course_id', $course->id)->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['wishlisted' => false]);
        }

        Wishlist::create(['user_id' => $request->user()->id, 'course_id' => $course->id]);

        return response()->json(['wishlisted' => true]);
    }
}
