<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizAttemptResource;
use App\Http\Resources\QuizTakeResource;
use App\Models\Course;
use App\Models\Quiz;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizAttemptController extends Controller
{
    public function __construct(private CertificateService $certificates) {}

    public function show(Request $request, Course $course, Quiz $quiz)
    {
        abort_unless($quiz->course_id === $course->id, 404);

        $this->authorizeAccess($request, $course);

        return new QuizTakeResource($quiz->load('questions.options'));
    }

    public function store(Request $request, Course $course, Quiz $quiz)
    {
        abort_unless($quiz->course_id === $course->id, 404);

        $this->authorizeAccess($request, $course);

        $quiz->loadMissing('questions.options');

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.option_id' => ['nullable', 'integer'],
        ]);

        $answersByQuestion = collect($data['answers'])->keyBy('question_id');
        $user = $request->user();

        $attempt = DB::transaction(function () use ($quiz, $user, $answersByQuestion) {
            $attempt = $quiz->attempts()->create([
                'user_id' => $user->id,
                'score' => 0,
                'passed' => false,
                'submitted_at' => now(),
            ]);

            $correctCount = 0;
            $totalQuestions = $quiz->questions->count();

            foreach ($quiz->questions as $question) {
                $submitted = $answersByQuestion->get($question->id);
                $optionId = $submitted['option_id'] ?? null;
                $option = $optionId ? $question->options->firstWhere('id', $optionId) : null;
                $isCorrect = (bool) $option?->is_correct;

                if ($isCorrect) {
                    $correctCount++;
                }

                $attempt->answers()->create([
                    'quiz_question_id' => $question->id,
                    'quiz_option_id' => $option?->id,
                    'is_correct' => $isCorrect,
                ]);
            }

            $score = $totalQuestions > 0 ? (int) round($correctCount / $totalQuestions * 100) : 0;

            $attempt->update([
                'score' => $score,
                'passed' => $score >= $quiz->passing_score,
            ]);

            return $attempt;
        });

        $this->certificates->maybeIssue($user, $course);

        return (new QuizAttemptResource($attempt->load('answers.question.options')))
            ->response()->setStatusCode(201);
    }

    private function authorizeAccess(Request $request, Course $course): void
    {
        $user = $request->user();

        abort_unless(
            $course->isEnrolled($user) || $user->id === $course->teacher_id || $user->isAdmin(),
            403,
            'Debes estar inscrito en el curso para acceder al examen.'
        );
    }
}
