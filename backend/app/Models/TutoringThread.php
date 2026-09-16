<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutoringThread extends Model
{
    public const BASE_QUESTION_LIMIT = 5;

    protected $fillable = ['course_id', 'student_id', 'extra_questions', 'student_last_read_at', 'teacher_last_read_at'];

    protected function casts(): array
    {
        return [
            'student_last_read_at' => 'datetime',
            'teacher_last_read_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TutoringMessage::class)->orderBy('created_at');
    }

    public function questionsUsed(): int
    {
        return $this->messages()->where('sender_id', $this->student_id)->count();
    }

    public function questionsLimit(): int
    {
        return self::BASE_QUESTION_LIMIT + $this->extra_questions;
    }

    public function questionsRemaining(): int
    {
        return max(0, $this->questionsLimit() - $this->questionsUsed());
    }

    public function markReadBy(User $user): void
    {
        if ($user->id === $this->student_id) {
            $this->update(['student_last_read_at' => now()]);
        } elseif ($user->id === $this->course->teacher_id || $user->isAdmin()) {
            $this->update(['teacher_last_read_at' => now()]);
        }
    }

    public function unreadCountFor(User $user): int
    {
        $isStudent = $user->id === $this->student_id;
        $lastRead = $isStudent ? $this->student_last_read_at : $this->teacher_last_read_at;

        $query = $isStudent
            ? $this->messages()->where('sender_id', '!=', $this->student_id)
            : $this->messages()->where('sender_id', $this->student_id);

        if ($lastRead) {
            $query->where('created_at', '>', $lastRead);
        }

        return $query->count();
    }
}
