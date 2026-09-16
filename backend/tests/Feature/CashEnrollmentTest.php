<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_teacher_can_grant_cash_access_to_a_student_for_their_own_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => 'published',
            'price_cents' => 4999,
        ]);

        $response = $this->actingAs($teacher)->postJson(
            "/api/courses/{$course->slug}/students/grant-cash",
            ['email' => $student->email, 'notes' => 'Pagado en clase']
        );

        $response->assertCreated();

        $this->assertDatabaseHas('orders', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => 'cash',
            'status' => 'paid',
            'amount_cents' => 4999,
            'created_by' => $teacher->id,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'source' => 'cash',
            'granted_by' => $teacher->id,
        ]);

        // From the student's point of view the course now looks exactly
        // like a paid enrollment: fully unlocked and listed in "my learning".
        $courseResponse = $this->actingAs($student)->getJson("/api/courses/{$course->slug}");
        $courseResponse->assertOk()->assertJsonPath('data.is_enrolled', true);

        $enrollmentsResponse = $this->actingAs($student)->getJson('/api/my/enrollments');
        $enrollmentsResponse->assertOk();
        $this->assertEquals('cash', $enrollmentsResponse->json('data.0.source'));
    }

    public function test_a_teacher_cannot_grant_cash_access_on_another_teachers_course(): void
    {
        $owner = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create(['teacher_id' => $owner->id, 'status' => 'published']);

        $response = $this->actingAs($otherTeacher)->postJson(
            "/api/courses/{$course->slug}/students/grant-cash",
            ['email' => $student->email]
        );

        $response->assertForbidden();
    }

    public function test_cannot_grant_cash_access_twice_to_the_same_student(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'status' => 'published']);

        $this->actingAs($teacher)->postJson(
            "/api/courses/{$course->slug}/students/grant-cash",
            ['email' => $student->email]
        )->assertCreated();

        $response = $this->actingAs($teacher)->postJson(
            "/api/courses/{$course->slug}/students/grant-cash",
            ['email' => $student->email]
        );

        $response->assertUnprocessable();
    }

    public function test_a_student_cannot_grant_cash_access(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create(['status' => 'published']);

        $response = $this->actingAs($student)->postJson(
            "/api/courses/{$course->slug}/students/grant-cash",
            ['email' => $otherStudent->email]
        );

        $response->assertForbidden();
    }
}
