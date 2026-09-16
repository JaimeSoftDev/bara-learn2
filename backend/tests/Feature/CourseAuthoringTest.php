<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAuthoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_teacher_can_create_a_course_with_only_the_required_fields(): void
    {
        $teacher = User::factory()->teacher()->create();

        // The "create course" form only sends title/subtitle/level/price/
        // category/thumbnail — description, requirements and
        // what_you_will_learn are added later in the editor and are
        // absent from this payload entirely (not just empty).
        $response = $this->actingAs($teacher)->postJson('/api/courses', [
            'title' => 'Curso mínimo',
            'level' => 'beginner',
            'price' => 0,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('courses', [
            'title' => 'Curso mínimo',
            'teacher_id' => $teacher->id,
            'description' => null,
        ]);
    }

    public function test_a_teacher_can_create_a_course_with_all_fields(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->postJson('/api/courses', [
            'title' => 'Curso completo',
            'subtitle' => 'Un subtítulo',
            'description' => '<p>Descripción</p>',
            'level' => 'advanced',
            'price' => 29.99,
            'requirements' => ['Requisito 1'],
            'what_you_will_learn' => ['Aprenderás X'],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('courses', [
            'title' => 'Curso completo',
            'price_cents' => 2999,
        ]);
    }

    public function test_a_student_cannot_create_a_course(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->postJson('/api/courses', [
            'title' => 'No debería crearse',
            'level' => 'beginner',
            'price' => 0,
        ]);

        $response->assertForbidden();
    }
}
