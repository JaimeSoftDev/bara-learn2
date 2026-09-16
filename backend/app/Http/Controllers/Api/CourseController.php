<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\CourseSummaryResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::query()
            ->with(['teacher', 'category'])
            ->where('status', 'published');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->toString()) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        if ($level = $request->string('level')->toString()) {
            $query->where('level', $level);
        }

        if ($request->filled('price')) {
            match ($request->string('price')->toString()) {
                'free' => $query->where('price_cents', 0),
                'paid' => $query->where('price_cents', '>', 0),
                default => null,
            };
        }

        $sort = $request->string('sort')->toString();
        match ($sort) {
            'newest' => $query->orderByDesc('published_at'),
            'price_asc' => $query->orderBy('price_cents'),
            'price_desc' => $query->orderByDesc('price_cents'),
            'rating' => $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating'),
            default => $query->orderByDesc('published_at'),
        };

        $courses = $query->paginate($request->integer('per_page', 12))->withQueryString();

        return CourseSummaryResource::collection($courses);
    }

    public function mine(Request $request)
    {
        $courses = Course::query()
            ->with(['category'])
            ->where('teacher_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return CourseSummaryResource::collection($courses);
    }

    public function store(StoreCourseRequest $request)
    {
        $data = $request->validated();

        $course = Course::create([
            'teacher_id' => $request->user()->id,
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'subtitle' => $data['subtitle'] ?? null,
            'description' => ! empty($data['description']) ? Purify::clean($data['description']) : null,
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'level' => $data['level'],
            'language' => $data['language'] ?? 'es',
            'price_cents' => (int) round(($data['price'] ?? 0) * 100),
            'requirements' => $data['requirements'] ?? [],
            'what_you_will_learn' => $data['what_you_will_learn'] ?? [],
        ]);

        return (new CourseResource($course->fresh()))->response()->setStatusCode(201);
    }

    public function show(Request $request, Course $course)
    {
        $this->authorize('view', $course);

        $course->load(['teacher', 'category', 'sections.lessons', 'sections.quiz', 'quizzes']);

        $user = $request->user();
        $canSeeFull = $course->isEnrolled($user) || ($user && ($user->id === $course->teacher_id || $user->isAdmin()));

        foreach ($course->sections as $section) {
            foreach ($section->lessons as $lesson) {
                $lesson->setAttribute('locked', ! $canSeeFull && ! $lesson->is_preview);
                $lesson->setAttribute('completed', $lesson->isCompletedBy($user));
            }
        }

        return new CourseResource($course);
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        $data = $request->validated();

        if (($data['status'] ?? null) === 'published' && $course->sections()->whereHas('lessons')->doesntExist()) {
            return response()->json([
                'message' => 'Añade al menos una lección antes de publicar el curso.',
            ], 422);
        }

        $updates = collect($data)->except(['price', 'status'])->toArray();

        if (array_key_exists('description', $updates) && $updates['description']) {
            $updates['description'] = Purify::clean($updates['description']);
        }

        if (isset($data['title']) && $data['title'] !== $course->title) {
            $updates['slug'] = $this->uniqueSlug($data['title'], $course->id);
        }

        if (isset($data['price'])) {
            $updates['price_cents'] = (int) round($data['price'] * 100);
        }

        if (isset($data['status'])) {
            $updates['status'] = $data['status'];
            $updates['published_at'] = $data['status'] === 'published'
                ? ($course->published_at ?? now())
                : $course->published_at;
        }

        $course->update($updates);

        return new CourseResource($course->fresh(['teacher', 'category']));
    }

    public function destroy(Request $request, Course $course)
    {
        $this->authorize('delete', $course);

        $course->delete();

        return response()->json(status: 204);
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (
            Course::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-".++$i;
        }

        return $slug;
    }
}
