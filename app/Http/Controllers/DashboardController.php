<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\ParticipationMark;
use App\Models\Quiz;
use App\Services\ParticipationGradingService;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ParticipationGradingService $grading, RecommendationService $recommender)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isLecturer()) {
            return $this->lecturer($user);
        }

        return $this->student($user, $grading, $recommender);
    }

    private function student($user, ParticipationGradingService $grading, RecommendationService $recommender)
    {
        $memberships = $user->memberships()->with('group')->get();

        $mark = ParticipationMark::where('user_id', $user->id)
            ->where('period', $grading->currentPeriod())
            ->orderByDesc('score')
            ->first();

        $score = (int) ($mark->score ?? 0);

        $announcements = Quiz::whereIn('group_id', $memberships->pluck('group_id'))
            ->latest('start_at')
            ->limit(5)
            ->get();

        $recommendations = $recommender->recommendFor($user, 5);

        return view('dashboard.student', [
            'memberships' => $memberships,
            'score' => $score,
            'grade' => $grading->gradeFor($score),
            'announcements' => $announcements,
            'recommendations' => $recommendations,
            'notifications' => $user->notifications()->limit(8)->get(),
        ]);
    }

    private function lecturer($user)
    {
        $groups = Group::where(function ($query) use ($user) {
            $query->whereHas('quizzes', fn ($q) => $q->where('lecturer_id', $user->id))
                ->orWhere('created_by', $user->id);
        })
            ->with('members')
            ->get();

        $quizzes = Quiz::where('lecturer_id', $user->id)->latest('start_at')->get();

        return view('dashboard.lecturer', [
            'groups' => $groups,
            'quizzes' => $quizzes,
        ]);
    }
}
