<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->catchPhrase();

        return [
            'teacher_id' => User::factory()->teacher(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 100000),
            'subtitle' => fake()->sentence(),
            'description' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'thumbnail_url' => 'https://picsum.photos/seed/'.fake()->uuid().'/640/360',
            'level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'language' => 'es',
            'price_cents' => fake()->randomElement([0, 1999, 2999, 4999, 7999]),
            'requirements' => [fake()->sentence(), fake()->sentence()],
            'what_you_will_learn' => [fake()->sentence(), fake()->sentence(), fake()->sentence()],
            'status' => 'published',
            'published_at' => now(),
        ];
    }
}
