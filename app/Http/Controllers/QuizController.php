<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizGradingService;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $quizzes = Quiz::latest('start_at')->get();
        } elseif ($user->isLecturer()) {
            $groupIds = $user->groups()->pluck('groups.id');
            $quizzes = Quiz::where('lecturer_id', $user->id)
                ->orWhereIn('group_id', $groupIds)
                ->latest('start_at')
                ->get();
        } else {
            $groupIds = $user->groups()->pluck('groups.id');
            $quizzes = Quiz::whereIn('group_id', $groupIds)->latest('start_at')->get();
        }

        // The list page is often the only page a student ever loads, so it
        // has to self-heal status here too -- otherwise a quiz stays stuck
        // showing "Scheduled" for anyone who never personally opens it.
        $quizzes->each(fn (Quiz $quiz) => $this->syncStatus($quiz));

        return view('quizzes.index', compact('quizzes'));
    }

    public function show(Request $request, Quiz $quiz)
    {
        $this->syncStatus($quiz);

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $request->user()->id)->first();

        return view('quizzes.show', compact('quiz', 'attempt'));
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

        return redirect()->route('quizzes.attempt', $quiz);
    }

    public function attempt(Request $request, Quiz $quiz)
    {
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $request->user()->id)->firstOrFail();

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('quizzes.show', $quiz);
        }

        if ($quiz->hasEnded()) {
            $this->autoSubmit($attempt, $quiz);

            return redirect()->route('quizzes.show', $quiz)->with('status', 'Time was up, so the quiz was auto-submitted.');
        }

        $quiz->load('questions');

        return view('quizzes.attempt', [
            'quiz' => $quiz,
            'attempt' => $attempt,
            'secondsRemaining' => now()->diffInSeconds($quiz->endsAt(), false),
        ]);
    }

    public function submit(Request $request, Quiz $quiz, QuizGradingService $grading)
    {
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $request->user()->id)->firstOrFail();

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('quizzes.show', $quiz);
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

        return redirect()->route('quizzes.show', $quiz)->with('status', 'Quiz submitted.');
    }

    public function report(Quiz $quiz)
    {
        $quiz->load(['questions', 'attempts.user']);

        return view('quizzes.report', ['quiz' => $quiz, 'totalMarks' => $quiz->totalMarks()]);
    }

    private function autoSubmit(QuizAttempt $attempt, Quiz $quiz): void
    {
        foreach ($quiz->questions as $question) {
            Answer::firstOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => $question->id],
                ['selected' => null]
            );
        }

        $attempt->update(['submitted_at' => now(), 'status' => 'auto_submitted']);
        app(QuizGradingService::class)->grade($attempt);
    }

    /**
     * Server is the sole timing authority (SDD 1.2): flips scheduled -> open
     * -> closed purely off start_at / duration, never off client input.
     */
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
