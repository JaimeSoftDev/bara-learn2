<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $fillable = ['course_id', 'section_id', 'title', 'passing_score'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function isFinalExam(): bool
    {
        return $this->section_id === null;
    }

    public function bestAttemptFor(?User $user): ?QuizAttempt
    {
        if (! $user) {
            return null;
        }

        return $this->attempts()
            ->where('user_id', $user->id)
            ->orderByDesc('passed')
            ->orderByDesc('score')
            ->first();
    }

    public function hasBeenPassedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->attempts()->where('user_id', $user->id)->where('passed', true)->exists();
    }
}
