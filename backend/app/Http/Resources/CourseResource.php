<?php

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Course $resource
 */
class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'thumbnail_url' => $this->thumbnail_url,
            'level' => $this->level,
            'language' => $this->language,
            'price' => $this->price,
            'is_free' => $this->is_free,
            'status' => $this->status,
            'requirements' => $this->requirements ?? [],
            'what_you_will_learn' => $this->what_you_will_learn ?? [],
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->reviews_count,
            'students_count' => $this->students_count,
            'lessons_count' => $this->lessons_count,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'is_enrolled' => $this->isEnrolled($user),
            'is_owner' => $user && ($user->id === $this->teacher_id || $user->isAdmin()),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'teacher' => $this->whenLoaded('teacher', fn () => [
                'id' => $this->teacher->id,
                'name' => $this->teacher->name,
                'avatar_url' => $this->teacher->avatar_url,
                'headline' => $this->teacher->headline,
                'bio' => $this->teacher->bio,
            ]),
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
            'final_exam' => $this->when($this->relationLoaded('quizzes'), fn () => $this->finalQuiz() ? new QuizSummaryResource($this->finalQuiz()) : null),
        ];
    }
}
