<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AnswerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\LessonProgressController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuizAttemptController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\QuizQuestionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\TeacherDashboardController;
use App\Http\Controllers\Api\TeacherTutoringController;
use App\Http\Controllers\Api\TutoringController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// --- Auth (Sanctum SPA, cookie based) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// --- Public catalog ---
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{course:slug}', [CourseController::class, 'show']);
Route::get('/courses/{course:slug}/reviews', [ReviewController::class, 'index']);
Route::get('/certificates/verify/{code}', [CertificateController::class, 'verify']);

// --- Stripe webhook (no CSRF / no auth, verified via signature) ---
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [UserController::class, 'updateProfile']);
    Route::put('/me/password', [UserController::class, 'updatePassword']);

    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Student
    Route::get('/my/enrollments', [EnrollmentController::class, 'index']);
    Route::get('/my/wishlist', [WishlistController::class, 'index']);
    Route::get('/my/certificates', [CertificateController::class, 'index']);
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download']);
    Route::post('/courses/{course:slug}/enroll', [EnrollmentController::class, 'store']);
    Route::post('/courses/{course:slug}/checkout', [CheckoutController::class, 'store']);
    Route::post('/courses/{course:slug}/wishlist', [WishlistController::class, 'toggle']);
    Route::post('/courses/{course:slug}/reviews', [ReviewController::class, 'store']);
    Route::delete('/courses/{course:slug}/reviews/{review}', [ReviewController::class, 'destroy']);

    Route::get('/lessons/{lesson}/questions', [QuestionController::class, 'index']);
    Route::post('/lessons/{lesson}/questions', [QuestionController::class, 'store']);
    Route::delete('/lessons/{lesson}/questions/{question}', [QuestionController::class, 'destroy']);
    Route::post('/questions/{question}/answers', [AnswerController::class, 'store']);
    Route::delete('/questions/{question}/answers/{answer}', [AnswerController::class, 'destroy']);

    Route::get('/courses/{course:slug}/quizzes/{quiz}/take', [QuizAttemptController::class, 'show']);
    Route::post('/courses/{course:slug}/quizzes/{quiz}/attempts', [QuizAttemptController::class, 'store']);

    Route::get('/courses/{course:slug}/tutoring', [TutoringController::class, 'show']);
    Route::post('/courses/{course:slug}/tutoring/messages', [TutoringController::class, 'sendMessage']);
    Route::post('/courses/{course:slug}/tutoring/read', [TutoringController::class, 'markRead']);

    Route::post('/lessons/{lesson}/complete', [LessonProgressController::class, 'complete']);
    Route::delete('/lessons/{lesson}/complete', [LessonProgressController::class, 'uncomplete']);

    // Teacher: course authoring
    Route::middleware('role:teacher,admin')->group(function () {
        Route::get('/my/courses', [CourseController::class, 'mine']);
        Route::post('/courses', [CourseController::class, 'store']);
        Route::put('/courses/{course:slug}', [CourseController::class, 'update']);
        Route::delete('/courses/{course:slug}', [CourseController::class, 'destroy']);

        Route::post('/courses/{course:slug}/sections', [SectionController::class, 'store']);
        Route::put('/courses/{course:slug}/sections/{section}', [SectionController::class, 'update']);
        Route::delete('/courses/{course:slug}/sections/{section}', [SectionController::class, 'destroy']);
        Route::post('/courses/{course:slug}/sections/reorder', [SectionController::class, 'reorder']);

        Route::post('/courses/{course:slug}/sections/{section}/lessons', [LessonController::class, 'store']);
        Route::put('/courses/{course:slug}/sections/{section}/lessons/{lesson}', [LessonController::class, 'update']);
        Route::delete('/courses/{course:slug}/sections/{section}/lessons/{lesson}', [LessonController::class, 'destroy']);

        Route::get('/courses/{course:slug}/quizzes', [QuizController::class, 'index']);
        Route::post('/courses/{course:slug}/quizzes', [QuizController::class, 'store']);
        Route::put('/courses/{course:slug}/quizzes/{quiz}', [QuizController::class, 'update']);
        Route::delete('/courses/{course:slug}/quizzes/{quiz}', [QuizController::class, 'destroy']);

        Route::post('/courses/{course:slug}/quizzes/{quiz}/questions', [QuizQuestionController::class, 'store']);
        Route::put('/courses/{course:slug}/quizzes/{quiz}/questions/{question}', [QuizQuestionController::class, 'update']);
        Route::delete('/courses/{course:slug}/quizzes/{quiz}/questions/{question}', [QuizQuestionController::class, 'destroy']);
        Route::post('/courses/{course:slug}/quizzes/{quiz}/questions/reorder', [QuizQuestionController::class, 'reorder']);

        Route::get('/courses/{course:slug}/students', [EnrollmentController::class, 'students']);
        Route::post('/courses/{course:slug}/students/grant-cash', [EnrollmentController::class, 'grantCash']);

        Route::get('/teacher/overview', [TeacherDashboardController::class, 'overview']);
        Route::get('/teacher/orders', [TeacherDashboardController::class, 'orders']);

        Route::get('/teacher/tutoring', [TeacherTutoringController::class, 'index']);
        Route::get('/teacher/tutoring/{thread}', [TeacherTutoringController::class, 'show']);
        Route::post('/teacher/tutoring/{thread}/messages', [TeacherTutoringController::class, 'sendMessage']);
        Route::post('/teacher/tutoring/{thread}/read', [TeacherTutoringController::class, 'markRead']);
        Route::post('/teacher/tutoring/{thread}/grant', [TeacherTutoringController::class, 'grantExtra']);
    });

    // Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/overview', [AdminController::class, 'overview']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{user}/role', [AdminController::class, 'updateUserRole']);
        Route::get('/courses', [AdminController::class, 'courses']);
        Route::delete('/courses/{course}', [AdminController::class, 'deleteCourse']);
    });
});
