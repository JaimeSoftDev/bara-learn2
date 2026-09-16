<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAuthoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_a_section_quiz_and_a_final_exam(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);
        $section = Section::factory()->create(['course_id' => $course->id]);

        $sectionQuiz = $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes", [
            'section_id' => $section->id,
            'title' => 'Examen de la sección',
            'passing_score' => 60,
        ]);
        $sectionQuiz->assertCreated();
        $sectionQuiz->assertJsonPath('data.is_final_exam', false);

        $finalExam = $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes", [
            'title' => 'Examen final',
        ]);
        $finalExam->assertCreated();
        $finalExam->assertJsonPath('data.is_final_exam', true);
        $finalExam->assertJsonPath('data.passing_score', 70);

        $this->assertDatabaseCount('quizzes', 2);
    }

    public function test_a_course_cannot_have_two_final_exams_or_a_section_with_two_quizzes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);
        $section = Section::factory()->create(['course_id' => $course->id]);

        $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes", ['title' => 'Final 1'])
            ->assertCreated();
        $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes", ['title' => 'Final 2'])
            ->assertStatus(422);

        $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes", [
            'section_id' => $section->id,
            'title' => 'Quiz 1',
        ])->assertCreated();
        $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes", [
            'section_id' => $section->id,
            'title' => 'Quiz 2',
        ])->assertStatus(422);
    }

    public function test_teacher_can_add_a_multiple_choice_question_with_exactly_one_correct_option(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);

        $quiz = $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes", [
            'title' => 'Examen final',
        ])->json('data');

        $response = $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes/{$quiz['id']}/questions", [
            'question' => '¿Qué es el ADN?',
            'options' => [
                ['option_text' => 'Ácido desoxirribonucleico', 'is_correct' => true],
                ['option_text' => 'Una proteína', 'is_correct' => false],
            ],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('quiz_questions', ['question' => '¿Qué es el ADN?']);
        $this->assertDatabaseHas('quiz_options', ['option_text' => 'Ácido desoxirribonucleico', 'is_correct' => true]);
    }

    public function test_a_question_is_rejected_without_exactly_one_correct_option(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);

        $quiz = $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes", [
            'title' => 'Examen final',
        ])->json('data');

        $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes/{$quiz['id']}/questions", [
            'question' => 'Sin respuesta correcta',
            'options' => [
                ['option_text' => 'A', 'is_correct' => false],
                ['option_text' => 'B', 'is_correct' => false],
            ],
        ])->assertStatus(422);

        $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes/{$quiz['id']}/questions", [
            'question' => 'Dos respuestas correctas',
            'options' => [
                ['option_text' => 'A', 'is_correct' => true],
                ['option_text' => 'B', 'is_correct' => true],
            ],
        ])->assertStatus(422);
    }

    public function test_a_teacher_cannot_manage_quizzes_of_another_teachers_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $course = Course::factory()->create(['teacher_id' => $otherTeacher->id]);

        $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes", [
            'title' => 'Intento ajeno',
        ])->assertForbidden();
    }
}
