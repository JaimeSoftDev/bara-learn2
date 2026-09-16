<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateGatingTest extends TestCase
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

    public function test_completing_all_lessons_without_passing_the_final_exam_does_not_issue_a_certificate(): void
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['section_id' => $section->id]);
        $student = $this->enrollStudent($course);

        $teacher = $course->teacher;
        $quiz = $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes", [
            'title' => 'Examen final',
        ])->json('data');
        $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes/{$quiz['id']}/questions", [
            'question' => '¿Qué es el ADN?',
            'options' => [
                ['option_text' => 'Ácido desoxirribonucleico', 'is_correct' => true],
                ['option_text' => 'Una proteína', 'is_correct' => false],
            ],
        ]);

        $response = $this->actingAs($student)->postJson("/api/lessons/{$lesson->id}/complete");

        $response->assertOk();
        $response->assertJsonPath('progress_percent', 100);
        $response->assertJsonPath('certificate_issued', false);
        $this->assertDatabaseMissing('certificates', ['user_id' => $student->id, 'course_id' => $course->id]);
    }

    public function test_certificate_is_issued_once_all_lessons_are_done_and_the_final_exam_is_passed(): void
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['section_id' => $section->id]);
        $student = $this->enrollStudent($course);

        $teacher = $course->teacher;
        $quizData = $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes", [
            'title' => 'Examen final',
        ])->json('data');
        $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes/{$quizData['id']}/questions", [
            'question' => '¿Qué es el ADN?',
            'options' => [
                ['option_text' => 'Ácido desoxirribonucleico', 'is_correct' => true],
                ['option_text' => 'Una proteína', 'is_correct' => false],
            ],
        ]);

        $this->actingAs($student)->postJson("/api/lessons/{$lesson->id}/complete")
            ->assertJsonPath('certificate_issued', false);

        $quiz = Quiz::find($quizData['id']);
        $question = $quiz->questions()->with('options')->first();

        $response = $this->actingAs($student)->postJson("/api/courses/{$course->slug}/quizzes/{$quiz->id}/attempts", [
            'answers' => [
                ['question_id' => $question->id, 'option_id' => $question->correctOption()->id],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.passed', true);
        $this->assertDatabaseHas('certificates', ['user_id' => $student->id, 'course_id' => $course->id]);
    }

    public function test_a_course_without_any_quizzes_issues_the_certificate_on_100_percent_completion(): void
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['section_id' => $section->id]);
        $student = $this->enrollStudent($course);

        $response = $this->actingAs($student)->postJson("/api/lessons/{$lesson->id}/complete");

        $response->assertOk();
        $response->assertJsonPath('certificate_issued', true);
        $this->assertDatabaseHas('certificates', ['user_id' => $student->id, 'course_id' => $course->id]);
    }
}
