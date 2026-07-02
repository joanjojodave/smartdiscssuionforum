<?php

namespace App\Services;

use App\Models\Topic;
use App\Models\User;

/**
 * Recommends topics based on the categories the user has previously engaged
 * with (topics they created, replied to, or asked/answered questions in).
 * Implements the same contract as the design document's POST /recommend
 * endpoint (user_id, context -> topic ids), see section 6.4.
 */
class RecommendationService
{
    public function recommendFor(User $user, int $limit = 5)
    {
        $engagedCategories = Topic::query()
            ->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereHas('posts', fn ($p) => $p->where('author_id', $user->id));
            })
            ->pluck('ml_label')
            ->merge(Topic::where('created_by', $user->id)->pluck('category'))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys();

        $groupIds = $user->groups()->pluck('groups.id');

        $query = Topic::whereIn('group_id', $groupIds)
            ->where('created_by', '!=', $user->id)
            ->whereDoesntHave('posts', fn ($p) => $p->where('author_id', $user->id));

        if ($engagedCategories->isNotEmpty()) {
            $preferred = (clone $query)->where(function ($q) use ($engagedCategories) {
                $q->whereIn('ml_label', $engagedCategories)->orWhereIn('category', $engagedCategories);
            })->latest()->limit($limit)->get();

            if ($preferred->count() >= $limit) {
                return $preferred;
            }

            $fillIds = $preferred->pluck('id');

            $fill = $query->whereNotIn('id', $fillIds)->latest()->limit($limit - $preferred->count())->get();

            return $preferred->concat($fill);
        }

        return $query->latest()->limit($limit)->get();
    }
}
