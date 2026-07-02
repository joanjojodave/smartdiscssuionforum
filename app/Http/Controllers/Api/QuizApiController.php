<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizGradingService;
use Illuminate\Http\Request;

class QuizApiController extends Controller
{
    public function index(Request $request)
    {
        $groupIds = $request->user()->groups()->pluck('groups.id');

        $quizzes = Quiz::whereIn('group_id', $groupIds)->latest('start_at')->get()
            ->map(fn (Quiz $q) => $this->quizSummary($q));

        return response()->json(['quizzes' => $quizzes]);
    }

    public function show(Request $request, Quiz $quiz)
    {
        $this->syncStatus($quiz);

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $request->user()->id)->first();

        $payload = $this->quizSummary($quiz);
        $payload['attempt_status'] = $attempt?->status;
        $payload['seconds_remaining'] = max(0, now()->diffInSeconds($quiz->endsAt(), false));

        // Only expose question content once the attempt is actually open, so
        // offline caching can't be used to see answers before start time.
        if ($quiz->hasStarted() && ! $quiz->hasEnded() && (! $attempt || $attempt->status === 'in_progress')) {
            $payload['questions'] = $quiz->questions->map(fn ($q) => [
                'id' => $q->id,
                'text' => $q->text,
                'options' => $q->options,
                'marks' => $q->marks,
            ]);
        }

        return response()->json($payload);
    }

    public function start(Request $request, Quiz $quiz)
    {
        abort_unless($quiz->hasStarted(), 403, 'This quiz has not opened yet.');
        abort_if($quiz->hasEnded(), 403, 'This quiz has already closed.');

        $attempt = QuizAttempt::firstOrCreate(
            ['quiz_id' => $quiz->id, 'user_id' => $request->user()->id],
            ['started_at' => now(), 'status' => 'in_progress']
        );

        $this->syncStatus($quiz);

        return response()->json(['attempt_status' => $attempt->status]);
    }

    public function submit(Request $request, Quiz $quiz, QuizGradingService $grading)
    {
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $request->user()->id)->firstOrFail();

        if ($attempt->status !== 'in_progress') {
            return response()->json(['attempt_status' => $attempt->status, 'score' => $attempt->score]);
        }

        $data = $request->validate([
            'answers' => 'array',
            'answers.*' => 'nullable|string|max:10',
        ]);

        foreach ($quiz->questions as $question) {
            Answer::updateOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => $question->id],
                ['selected' => strtoupper($data['answers'][$question->id] ?? '') ?: null]
            );
        }

        $autoSubmitted = $quiz->hasEnded();

        $attempt->update([
            'submitted_at' => now(),
            'status' => $autoSubmitted ? 'auto_submitted' : 'submitted',
        ]);

        $grading->grade($attempt);
        $this->syncStatus($quiz);

        return response()->json(['attempt_status' => $attempt->status, 'score' => $attempt->fresh()->score]);
    }

    public function report(Quiz $quiz)
    {
        $quiz->load(['questions', 'attempts.user']);
        $totalMarks = $quiz->totalMarks();

        $attempts = $quiz->attempts->map(fn (QuizAttempt $a) => [
            'user' => $a->user->name,
            'status' => $a->status,
            'score' => $a->score,
            'total_marks' => $totalMarks,
        ]);

        return response()->json(['quiz' => $this->quizSummary($quiz), 'total_marks' => $totalMarks, 'attempts' => $attempts]);
    }

    private function quizSummary(Quiz $quiz): array
    {
        return [
            'id' => $quiz->id,
            'group_id' => $quiz->group_id,
            'title' => $quiz->title,
            'start_at' => $quiz->start_at->toIso8601String(),
            'duration_minutes' => $quiz->duration_minutes,
            'target_category' => $quiz->target_category,
            'status' => $quiz->status,
        ];
    }

    private function syncStatus(Quiz $quiz): void
    {
        $status = match (true) {
            $quiz->hasEnded() => 'closed',
            $quiz->hasStarted() => 'open',
            default => 'scheduled',
        };

        if ($status !== $quiz->status) {
            $quiz->update(['status' => $status]);
        }
    }
}
