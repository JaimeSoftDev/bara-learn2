<?php

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Course $resource
 */
class CourseSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'thumbnail_url' => $this->thumbnail_url,
            'level' => $this->level,
            'language' => $this->language,
            'price' => $this->price,
            'is_free' => $this->is_free,
            'status' => $this->status,
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->reviews_count,
            'students_count' => $this->students_count,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'teacher' => $this->whenLoaded('teacher', fn () => [
                'id' => $this->teacher->id,
                'name' => $this->teacher->name,
                'avatar_url' => $this->teacher->avatar_url,
            ]),
        ];
    }
}
