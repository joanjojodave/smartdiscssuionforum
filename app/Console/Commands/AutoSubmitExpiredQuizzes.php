<?php

namespace App\Console\Commands;

use App\Models\Answer;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Notifications\QuizReportReadyNotification;
use App\Services\QuizGradingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[Signature('app:auto-submit-expired-quizzes')]
#[Description('Auto-submits quiz attempts whose duration has elapsed and closes expired quizzes, since the server -- not the client -- is the timing authority (SDD 1.2).')]
class AutoSubmitExpiredQuizzes extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(QuizGradingService $grading)
    {
        // Quiz endsAt() is computed in PHP (start_at + duration_minutes), so
        // candidates are filtered here rather than with DB-engine-specific
        // date arithmetic -- keeps this portable between SQLite and MySQL.
        $candidateQuizzes = Quiz::where('status', '!=', 'closed')
            ->where('start_at', '<=', now())
            ->with(['questions', 'attempts' => fn ($q) => $q->where('status', 'in_progress')])
            ->get()
            ->filter(fn (Quiz $quiz) => $quiz->hasEnded());

        $submittedCount = 0;

        foreach ($candidateQuizzes as $quiz) {
            foreach ($quiz->attempts as $attempt) {
                foreach ($quiz->questions as $question) {
                    Answer::firstOrCreate(
                        ['attempt_id' => $attempt->id, 'question_id' => $question->id],
                        ['selected' => null]
                    );
                }

                $attempt->update(['submitted_at' => now(), 'status' => 'auto_submitted']);
                $grading->grade($attempt);
                $submittedCount++;
            }

            $quiz->update(['status' => 'closed']);

            $studentIds = $quiz->group->members()->wherePivot('status', 'active')->pluck('users.id');
            $students = \App\Models\User::whereIn('id', $studentIds)->get();
            Notification::send($students, new QuizReportReadyNotification($quiz));
        }

        $openNow = Quiz::where('status', 'scheduled')->where('start_at', '<=', now())->get();
        foreach ($openNow as $quiz) {
            if (! $quiz->hasEnded()) {
                $quiz->update(['status' => 'open']);
            }
        }

        $this->info("Auto-submitted {$submittedCount} attempt(s); closed {$candidateQuizzes->count()} quiz(zes).");
    }
}
