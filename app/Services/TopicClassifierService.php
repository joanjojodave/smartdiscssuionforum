<?php

namespace App\Services;

/**
 * Keyword-frequency topic classifier.
 *
 * Design-document section 5.13 / 6.4 specifies a Python/FastAPI + scikit-learn
 * ML service exposing POST /classify => { category, confidence }. That service
 * is a separate deployable component outside this Laravel codebase; this class
 * implements the same request/response contract locally (see classify()) so
 * the forum is fully functional standalone, and a real HTTP-backed
 * implementation can later be swapped in behind the same method signature.
 */
class TopicClassifierService
{
    public function classify(string $text): array
    {
        $categories = config('ml.categories');
        $words = $this->tokenize($text);

        $best = config('ml.default_category');
        $bestScore = 0;
        $totalHits = 0;

        foreach ($categories as $category => $keywords) {
            if ($keywords === []) {
                continue;
            }

            $hits = count(array_intersect($words, $keywords));
            $totalHits += $hits;

            if ($hits > $bestScore) {
                $bestScore = $hits;
                $best = $category;
            }
        }

        $confidence = $totalHits > 0 ? round($bestScore / max($totalHits, 1), 2) : 0.3;

        return [
            'category' => $best,
            'confidence' => max($confidence, 0.3),
        ];
    }

    private function tokenize(string $text): array
    {
        return preg_split('/[^a-z0-9]+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
    }
}
