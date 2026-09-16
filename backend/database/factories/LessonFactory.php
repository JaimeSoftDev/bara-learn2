<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'title' => fake()->sentence(4),
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'content' => '<p>'.fake()->paragraph().'</p>',
            'position' => 0,
            'duration_seconds' => fake()->numberBetween(180, 1200),
            'is_preview' => false,
        ];
    }
}
