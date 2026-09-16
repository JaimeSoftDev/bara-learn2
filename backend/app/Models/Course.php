<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $fillable = [
        'teacher_id', 'category_id', 'title', 'slug', 'subtitle', 'description',
        'thumbnail_url', 'level', 'language', 'price_cents', 'requirements',
        'what_you_will_learn', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'what_you_will_learn' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('position');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Section::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlistedBy(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function tutoringThreads(): HasMany
    {
        return $this->hasMany(TutoringThread::class);
    }

    public function finalQuiz(): ?Quiz
    {
        if ($this->relationLoaded('quizzes')) {
            return $this->quizzes->firstWhere('section_id', null);
        }

        return $this->quizzes()->whereNull('section_id')->first();
    }

    public function hasPassedAllQuizzes(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->quizzes->every(fn (Quiz $quiz) => $quiz->hasBeenPassedBy($user));
    }

    protected function isFree(): Attribute
    {
        return Attribute::get(fn () => $this->price_cents === 0);
    }

    protected function price(): Attribute
    {
        return Attribute::get(fn () => round($this->price_cents / 100, 2));
    }

    protected function averageRating(): Attribute
    {
        return Attribute::get(fn () => round($this->reviews()->avg('rating') ?? 0, 1));
    }

    protected function reviewsCount(): Attribute
    {
        return Attribute::get(fn () => $this->reviews()->count());
    }

    protected function studentsCount(): Attribute
    {
        return Attribute::get(fn () => $this->enrollments()->count());
    }

    protected function lessonsCount(): Attribute
    {
        return Attribute::get(fn () => $this->lessons()->count());
    }

    public function isEnrolled(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->enrollments()->where('user_id', $user->id)->exists();
    }
}
