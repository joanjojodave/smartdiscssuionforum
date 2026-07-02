<?php

namespace App\Services;

use App\Models\QuizAttempt;

class QuizGradingService
{
    public function grade(QuizAttempt $attempt): float
    {
        $attempt->loadMissing('answers.question');

        $score = 0;

        foreach ($attempt->answers as $answer) {
            $isCorrect = $answer->selected !== null && $answer->selected === $answer->question->correct_option;
            $answer->update(['is_correct' => $isCorrect]);

            if ($isCorrect) {
                $score += $answer->question->marks;
            }
        }

        $attempt->update(['score' => $score]);

        return $score;
    }
}
