<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'group_id', 'status', 'warnings_count',
    'last_active_at', 'agreed_rules', 'blacklist_until', 'joined_at',
])]
class Membership extends Model
{
    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
            'blacklist_until' => 'datetime',
            'joined_at' => 'datetime',
            'agreed_rules' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function isBlacklisted(): bool
    {
        return $this->status === 'blacklisted' && $this->blacklist_until && $this->blacklist_until->isFuture();
    }

    public function touchActivity(): void
    {
        $this->forceFill([
            'last_active_at' => now(),
            'warnings_count' => 0,
            'status' => $this->status === 'warned' ? 'active' : $this->status,
        ])->save();
    }
}
