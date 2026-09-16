<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourseWithLesson(array $courseAttrs = []): Course
    {
        $course = Course::factory()->create(array_merge(['status' => 'published'], $courseAttrs));
        $section = Section::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create(['section_id' => $section->id, 'is_preview' => false]);

        return $course;
    }

    public function test_draft_courses_are_not_listed_in_the_public_catalog(): void
    {
        Course::factory()->create(['status' => 'draft']);
        $published = Course::factory()->create(['status' => 'published']);

        $response = $this->getJson('/api/courses');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($published->id));
        $this->assertCount(1, $ids);
    }

    public function test_guests_cannot_see_locked_lesson_content(): void
    {
        $course = $this->makeCourseWithLesson();

        $response = $this->getJson("/api/courses/{$course->slug}");

        $response->assertOk();
        $lesson = $response->json('data.sections.0.lessons.0');
        $this->assertTrue($lesson['locked']);
        $this->assertNull($lesson['content']);
        $this->assertNull($lesson['youtube_video_id']);
    }

    public function test_enrolled_students_can_see_lesson_content(): void
    {
        $course = $this->makeCourseWithLesson();
        $student = User::factory()->create(['role' => 'student']);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'source' => 'free',
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($student)->getJson("/api/courses/{$course->slug}");

        $response->assertOk();
        $lesson = $response->json('data.sections.0.lessons.0');
        $this->assertFalse($lesson['locked']);
        $this->assertNotNull($lesson['content']);
    }

    public function test_a_student_can_enroll_in_a_free_course(): void
    {
        $course = $this->makeCourseWithLesson(['price_cents' => 0]);
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->postJson("/api/courses/{$course->slug}/enroll");

        $response->assertCreated();
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'source' => 'free',
        ]);
    }

    public function test_a_student_cannot_free_enroll_in_a_paid_course(): void
    {
        $course = $this->makeCourseWithLesson(['price_cents' => 2999]);
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->postJson("/api/courses/{$course->slug}/enroll");

        $response->assertUnprocessable();
    }
}
