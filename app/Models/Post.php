<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'topic_id', 'author_id', 'parent_post_id', 'body',
    'is_question', 'is_answer', 'is_relevant', 'is_flood', 'relevance_score',
])]
class Post extends Model
{
    protected function casts(): array
    {
        return [
            'is_question' => 'boolean',
            'is_answer' => 'boolean',
            'is_relevant' => 'boolean',
            'is_flood' => 'boolean',
            'relevance_score' => 'float',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'parent_post_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Post::class, 'parent_post_id');
    }

    public function isUnanswered(): bool
    {
        return $this->is_question
            && ! $this->replies()->where('is_answer', true)->exists();
    }
}
