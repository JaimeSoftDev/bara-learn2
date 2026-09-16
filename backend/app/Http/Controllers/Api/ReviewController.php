<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Course;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Course $course)
    {
        $reviews = $course->reviews()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(10);

        return ReviewResource::collection($reviews);
    }

    public function store(Request $request, Course $course)
    {
        abort_unless($course->isEnrolled($request->user()), 403, 'Debes estar inscrito en el curso para valorarlo.');

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = Review::updateOrCreate(
            ['user_id' => $request->user()->id, 'course_id' => $course->id],
            $data
        );

        return response()->json(new ReviewResource($review->load('user')), 201);
    }

    public function destroy(Request $request, Course $course, Review $review)
    {
        $this->authorize('delete', $review);
        abort_unless($review->course_id === $course->id, 404);

        $review->delete();

        return response()->json(status: 204);
    }
}
