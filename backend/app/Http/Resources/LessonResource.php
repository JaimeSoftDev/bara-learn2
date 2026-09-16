<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Lesson $resource
 */
class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locked = (bool) ($this->locked ?? false);

        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'title' => $this->title,
            'position' => $this->position,
            'duration_seconds' => $this->duration_seconds,
            'is_preview' => $this->is_preview,
            'locked' => $locked,
            'completed' => (bool) ($this->completed ?? false),
            // Only expose the actual video / written content once the
            // viewer is allowed to see it (enrolled, preview lesson, or
            // the course owner / an admin).
            'youtube_video_id' => $locked ? null : $this->youtube_video_id,
            'content' => $locked ? null : $this->content,
        ];
    }
}
