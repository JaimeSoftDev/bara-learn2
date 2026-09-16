<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAttemptTest extends TestCase
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

    private function createQuizWithQuestion(Course $course): Quiz
    {
        $teacher = $course->teacher;

        $quiz = $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes", [
            'title' => 'Examen final',
            'passing_score' => 60,
        ])->json('data');

        $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes/{$quiz['id']}/questions", [
            'question' => '¿Qué es el ADN?',
            'options' => [
                ['option_text' => 'Ácido desoxirribonucleico', 'is_correct' => true],
                ['option_text' => 'Una proteína', 'is_correct' => false],
            ],
        ]);

        $this->actingAs($teacher)->postJson("/api/courses/{$course->slug}/quizzes/{$quiz['id']}/questions", [
            'question' => '¿Cuántos cromosomas tiene un humano?',
            'options' => [
                ['option_text' => '46', 'is_correct' => true],
                ['option_text' => '23', 'is_correct' => false],
            ],
        ]);

        return Quiz::find($quiz['id']);
    }

    public function test_an_enrolled_student_can_view_the_quiz_without_correct_answers(): void
    {
        $course = Course::factory()->create();
        $quiz = $this->createQuizWithQuestion($course);
        $student = $this->enrollStudent($course);

        $response = $this->actingAs($student)->getJson("/api/courses/{$course->slug}/quizzes/{$quiz->id}/take");

        $response->assertOk();
        $response->assertJsonCount(2, 'data.questions');
        $this->assertArrayNotHasKey('is_correct', $response->json('data.questions.0.options.0'));
    }

    public function test_a_non_enrolled_student_cannot_view_or_take_the_quiz(): void
    {
        $course = Course::factory()->create();
        $quiz = $this->createQuizWithQuestion($course);
        $stranger = User::factory()->create(['role' => 'student']);

        $this->actingAs($stranger)->getJson("/api/courses/{$course->slug}/quizzes/{$quiz->id}/take")
            ->assertForbidden();

        $this->actingAs($stranger)->postJson("/api/courses/{$course->slug}/quizzes/{$quiz->id}/attempts", [
            'answers' => [],
        ])->assertForbidden();
    }

    public function test_a_student_who_answers_everything_correctly_passes_and_sees_the_correction(): void
    {
        $course = Course::factory()->create();
        $quiz = $this->createQuizWithQuestion($course);
        $student = $this->enrollStudent($course);

        $questions = $quiz->questions()->with('options')->get();
        $answers = $questions->map(fn ($question) => [
            'question_id' => $question->id,
            'option_id' => $question->correctOption()->id,
        ])->values()->all();

        $response = $this->actingAs($student)->postJson("/api/courses/{$course->slug}/quizzes/{$quiz->id}/attempts", [
            'answers' => $answers,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.score', 100);
        $response->assertJsonPath('data.passed', true);
        $response->assertJsonPath('data.answers.0.is_correct', true);
        $this->assertNotNull($response->json('data.answers.0.correct_option_id'));

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 100,
            'passed' => true,
        ]);
    }

    public function test_a_student_who_fails_the_passing_score_does_not_pass(): void
    {
        $course = Course::factory()->create();
        $quiz = $this->createQuizWithQuestion($course);
        $student = $this->enrollStudent($course);

        $questions = $quiz->questions()->with('options')->get();
        $wrongFirst = $questions[0]->options->firstWhere('is_correct', false);
        $correctSecond = $questions[1]->correctOption();

        $response = $this->actingAs($student)->postJson("/api/courses/{$course->slug}/quizzes/{$quiz->id}/attempts", [
            'answers' => [
                ['question_id' => $questions[0]->id, 'option_id' => $wrongFirst->id],
                ['question_id' => $questions[1]->id, 'option_id' => $correctSecond->id],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.score', 50);
        $response->assertJsonPath('data.passed', false);

        $this->assertFalse($quiz->fresh()->hasBeenPassedBy($student));
    }
}
