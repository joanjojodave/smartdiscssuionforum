<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['group_id', 'sender_id', 'body', 'sync_status', 'sent_at'])]
class Message extends Model
{
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function exclusions(): HasMany
    {
        return $this->hasMany(MessageExclusion::class);
    }

    public function isExcludedFor(User $user): bool
    {
        return $this->exclusions()->where('excluded_user_id', $user->id)->exists();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereDoesntHave('exclusions', fn ($q) => $q->where('excluded_user_id', $user->id));
    }
}
