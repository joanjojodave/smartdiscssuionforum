<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['group_id', 'created_by', 'title', 'category', 'ml_label', 'is_resolved'])]
class Topic extends Model
{
    protected function casts(): array
    {
        return [
            'is_resolved' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function rootPosts(): HasMany
    {
        return $this->hasMany(Post::class)->whereNull('parent_post_id');
    }

    public function hasUnansweredQuestions(): bool
    {
        return $this->posts()->where('is_question', true)->where('is_answer', false)
            ->whereDoesntHave('replies', fn ($q) => $q->where('is_answer', true))
            ->exists();
    }
}
