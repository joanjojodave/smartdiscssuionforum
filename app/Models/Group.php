<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'description', 'rules', 'created_by',
    'inactivity_warning_days', 'inactivity_blacklist_days', 'blacklist_duration_days',
])]
class Group extends Model
{
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot(['status', 'warnings_count', 'last_active_at', 'agreed_rules', 'blacklist_until', 'joined_at'])
            ->withTimestamps();
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function participationMarks(): HasMany
    {
        return $this->hasMany(ParticipationMark::class);
    }
}
