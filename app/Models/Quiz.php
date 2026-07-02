<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['group_id', 'lecturer_id', 'title', 'start_at', 'duration_minutes', 'target_category', 'status'])]
class Quiz extends Model
{
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function endsAt(): \Illuminate\Support\Carbon
    {
        return $this->start_at->clone()->addMinutes($this->duration_minutes);
    }

    public function hasStarted(): bool
    {
        return now()->greaterThanOrEqualTo($this->start_at);
    }

    public function hasEnded(): bool
    {
        return now()->greaterThanOrEqualTo($this->endsAt());
    }

    public function totalMarks(): int
    {
        return (int) $this->questions()->sum('marks');
    }
}
