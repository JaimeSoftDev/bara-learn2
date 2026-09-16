<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TutoringTest extends TestCase
{
    use RefreshDatabase;

    private function enrollStudent(Course $course): User
    {
        $student = User::factory()->create(['role' => 'student']);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'source' => 'cash',
            'enrolled_at' => now(),
        ]);

        return $student;
    }

    public function test_a_non_enrolled_student_cannot_access_tutoring(): void
    {
        $course = Course::factory()->create();
        $stranger = User::factory()->create(['role' => 'student']);

        $this->actingAs($stranger)->getJson("/api/courses/{$course->slug}/tutoring")->assertForbidden();
        $this->actingAs($stranger)->postJson("/api/courses/{$course->slug}/tutoring/messages", ['body' => 'Hola'])
            ->assertForbidden();
    }

    public function test_an_enrolled_student_can_open_the_thread_and_send_a_message(): void
    {
        $course = Course::factory()->create();
        $student = $this->enrollStudent($course);

        $show = $this->actingAs($student)->getJson("/api/courses/{$course->slug}/tutoring");
        $show->assertOk();
        $show->assertJsonPath('data.questions_limit', 5);
        $show->assertJsonPath('data.questions_remaining', 5);

        $response = $this->actingAs($student)->postJson("/api/courses/{$course->slug}/tutoring/messages", [
            'body' => '¿Cómo resuelvo un cruce dihíbrido?',
        ]);
        $response->assertCreated();
        $response->assertJsonPath('data.is_mine', true);

        $this->assertDatabaseHas('tutoring_messages', ['body' => '¿Cómo resuelvo un cruce dihíbrido?']);

        $after = $this->actingAs($student)->getJson("/api/courses/{$course->slug}/tutoring");
        $after->assertJsonPath('data.questions_used', 1);
        $after->assertJsonPath('data.questions_remaining', 4);
    }

    public function test_a_student_is_blocked_after_five_questions_but_can_still_read(): void
    {
        $course = Course::factory()->create();
        $student = $this->enrollStudent($course);

        foreach (range(1, 5) as $i) {
            $this->actingAs($student)->postJson("/api/courses/{$course->slug}/tutoring/messages", [
                'body' => "Pregunta número {$i}",
            ])->assertCreated();
        }

        $blocked = $this->actingAs($student)->postJson("/api/courses/{$course->slug}/tutoring/messages", [
            'body' => 'Sexta pregunta, no debería entrar',
        ]);
        $blocked->assertStatus(422);

        $this->assertDatabaseCount('tutoring_messages', 5);

        $show = $this->actingAs($student)->getJson("/api/courses/{$course->slug}/tutoring");
        $show->assertOk();
        $show->assertJsonPath('data.questions_remaining', 0);
        $show->assertJsonCount(5, 'data.messages');
    }

    public function test_teacher_replies_do_not_count_against_the_student_quota(): void
    {
        $course = Course::factory()->create();
        $student = $this->enrollStudent($course);
        $teacher = $course->teacher;

        $this->actingAs($student)->postJson("/api/courses/{$course->slug}/tutoring/messages", ['body' => 'Pregunta 1'])
            ->assertCreated();

        $threadId = $this->actingAs($teacher)->getJson('/api/teacher/tutoring')->json('data.0.id');

        foreach (range(1, 3) as $i) {
            $this->actingAs($teacher)->postJson("/api/teacher/tutoring/{$threadId}/messages", [
                'body' => "Respuesta del profesor {$i}",
            ])->assertCreated();
        }

        $show = $this->actingAs($student)->getJson("/api/courses/{$course->slug}/tutoring");
        $show->assertJsonPath('data.questions_used', 1);
        $show->assertJsonPath('data.questions_remaining', 4);
        $show->assertJsonCount(4, 'data.messages');
    }

    public function test_teacher_can_grant_extra_questions_to_unblock_a_student(): void
    {
        $course = Course::factory()->create();
        $student = $this->enrollStudent($course);
        $teacher = $course->teacher;

        foreach (range(1, 5) as $i) {
            $this->actingAs($student)->postJson("/api/courses/{$course->slug}/tutoring/messages", [
                'body' => "Pregunta {$i}",
            ])->assertCreated();
        }

        $threadId = $this->actingAs($teacher)->getJson('/api/teacher/tutoring')->json('data.0.id');

        $this->actingAs($student)->postJson("/api/courses/{$course->slug}/tutoring/messages", ['body' => 'Bloqueada'])
            ->assertStatus(422);

        $grant = $this->actingAs($teacher)->postJson("/api/teacher/tutoring/{$threadId}/grant", ['amount' => 3]);
        $grant->assertOk();
        $grant->assertJsonPath('data.questions_limit', 8);
        $grant->assertJsonPath('data.questions_remaining', 3);

        $this->actingAs($student)->postJson("/api/courses/{$course->slug}/tutoring/messages", ['body' => 'Ahora sí entra'])
            ->assertCreated();
    }

    public function test_a_teacher_cannot_access_another_teachers_tutoring_thread(): void
    {
        $course = Course::factory()->create();
        $student = $this->enrollStudent($course);
        $otherTeacher = User::factory()->teacher()->create();

        $this->actingAs($student)->postJson("/api/courses/{$course->slug}/tutoring/messages", ['body' => 'Hola'])
            ->assertCreated();

        $threadId = $course->tutoringThreads()->first()->id;

        $this->actingAs($otherTeacher)->getJson("/api/teacher/tutoring/{$threadId}")->assertForbidden();
        $this->actingAs($otherTeacher)->postJson("/api/teacher/tutoring/{$threadId}/messages", ['body' => 'Intruso'])
            ->assertForbidden();

        $index = $this->actingAs($otherTeacher)->getJson('/api/teacher/tutoring');
        $index->assertJsonCount(0, 'data');
    }

    public function test_unread_count_reflects_unread_messages_for_each_side(): void
    {
        $course = Course::factory()->create();
        $student = $this->enrollStudent($course);
        $teacher = $course->teacher;

        $this->actingAs($student)->postJson("/api/courses/{$course->slug}/tutoring/messages", ['body' => 'Pregunta 1'])
            ->assertCreated();

        $index = $this->actingAs($teacher)->getJson('/api/teacher/tutoring');
        $index->assertJsonPath('data.0.unread_count', 1);

        $threadId = $index->json('data.0.id');
        $this->actingAs($teacher)->getJson("/api/teacher/tutoring/{$threadId}")->assertOk();

        $indexAfter = $this->actingAs($teacher)->getJson('/api/teacher/tutoring');
        $indexAfter->assertJsonPath('data.0.unread_count', 0);

        $this->actingAs($teacher)->postJson("/api/teacher/tutoring/{$threadId}/messages", ['body' => 'Respuesta'])
            ->assertCreated();

        $studentView = $this->actingAs($student)->getJson("/api/courses/{$course->slug}/tutoring");
        $studentView->assertJsonPath('data.unread_count', 0);
    }
}
