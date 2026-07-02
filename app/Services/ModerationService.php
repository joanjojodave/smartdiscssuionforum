<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;

/**
 * Rule-based content moderation: flags flooding (too many posts in a short
 * window) and scores relevance against the topic's title/category so that
 * clearly off-topic or spammy posts can be filtered from the main thread.
 *
 * This mirrors the /moderate endpoint described for the ML service in the
 * design document, implemented locally so moderation works without a
 * separate Python service. It can be swapped for a real HTTP call later
 * without changing any caller.
 */
class ModerationService
{
    private const FLOOD_WINDOW_MINUTES = 2;

    private const FLOOD_THRESHOLD = 4;

    private const RELEVANCE_THRESHOLD = 0.12;

    public function evaluate(Topic $topic, User $author, string $body): array
    {
        $isFlood = $this->isFlooding($author, $topic);
        $relevance = $this->relevanceScore($topic, $body);

        return [
            'is_flood' => $isFlood,
            'relevance_score' => $relevance,
            'is_relevant' => ! $isFlood && $relevance >= self::RELEVANCE_THRESHOLD,
        ];
    }

    private function isFlooding(User $author, Topic $topic): bool
    {
        return Post::where('author_id', $author->id)
            ->where('topic_id', $topic->id)
            ->where('created_at', '>=', now()->subMinutes(self::FLOOD_WINDOW_MINUTES))
            ->count() >= self::FLOOD_THRESHOLD;
    }

    private function relevanceScore(Topic $topic, string $body): float
    {
        $keywords = $this->tokenize($topic->title.' '.$topic->category);
        $words = $this->tokenize($body);

        if ($keywords === [] || $words === []) {
            return 0.5;
        }

        $overlap = count(array_intersect($keywords, $words));
        $score = $overlap / max(count($keywords), 1);

        // A reasonably long, substantive post is treated as on-topic by default
        // even without direct keyword overlap with the title.
        if (str_word_count($body) >= 15) {
            $score = max($score, 0.3);
        }

        return round(min($score, 1.0), 2);
    }

    private function tokenize(?string $text): array
    {
        $text = strtolower((string) $text);
        $words = preg_split('/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $stopwords = ['the', 'a', 'an', 'is', 'are', 'and', 'or', 'to', 'of', 'in', 'on', 'for', 'this', 'that'];

        return array_values(array_diff($words, $stopwords));
    }
}
