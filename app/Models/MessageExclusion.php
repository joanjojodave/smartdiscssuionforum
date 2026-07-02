<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['message_id', 'excluded_user_id'])]
class MessageExclusion extends Model
{
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function excludedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'excluded_user_id');
    }
}
