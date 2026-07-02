<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Quiz;
use App\Notifications\QuizPublishedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class QuizManageController extends Controller
{
    public function create(Request $request)
    {
        $groups = Group::all();

        return view('lecturer.quizzes.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'title' => 'required|string|max:200',
            'start_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:1|max:300',
            'target_category' => 'nullable|string|max:80',
            'questions' => 'required|array|min:1',
            'questions.*.text' => 'required|string|max:1000',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.options.*' => 'required|string|max:300',
            'questions.*.correct_option' => 'required|string|max:10',
            'questions.*.marks' => 'required|integer|min:1|max:100',
        ]);

        $quiz = Quiz::create([
            'group_id' => $data['group_id'],
            'lecturer_id' => $request->user()->id,
            'title' => $data['title'],
            'start_at' => $data['start_at'],
            'duration_minutes' => $data['duration_minutes'],
            'target_category' => $data['target_category'] ?? null,
            'status' => 'scheduled',
        ]);

        foreach ($data['questions'] as $q) {
            $options = collect($q['options'])->mapWithKeys(function ($text, $i) {
                $key = chr(65 + $i); // A, B, C, D...
                return [$key => $text];
            });

            $quiz->questions()->create([
                'text' => $q['text'],
                'options' => $options,
                'correct_option' => strtoupper($q['correct_option']),
                'marks' => $q['marks'],
            ]);
        }

        $group = $quiz->group;
        $studentIds = $group->members()->wherePivot('status', 'active')->pluck('users.id');
        $students = \App\Models\User::whereIn('id', $studentIds)->get();
        Notification::send($students, new QuizPublishedNotification($quiz));

        return redirect()->route('lecturer.quizzes.report', $quiz)->with('status', 'Quiz scheduled and announced to the group.');
    }

    public function report(Quiz $quiz)
    {
        abort_unless($quiz->lecturer_id === request()->user()->id || request()->user()->isAdmin(), 403);

        $quiz->load(['questions', 'attempts.user']);

        $totalMarks = $quiz->totalMarks();

        return view('lecturer.quizzes.report', compact('quiz', 'totalMarks'));
    }
}
