<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(?User $user, Course $course): bool
    {
        if ($course->status === 'published') {
            return true;
        }

        return $user && ($user->isAdmin() || $user->id === $course->teacher_id);
    }

    public function create(User $user): bool
    {
        return $user->isTeacher() || $user->isAdmin();
    }

    public function update(User $user, Course $course): bool
    {
        return $user->isAdmin() || $user->id === $course->teacher_id;
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->isAdmin() || $user->id === $course->teacher_id;
    }

    public function manageStudents(User $user, Course $course): bool
    {
        return $user->isAdmin() || $user->id === $course->teacher_id;
    }
}
