<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ParticipationMark;
use App\Models\Quiz;
use App\Services\ParticipationGradingService;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function index(Request $request, ParticipationGradingService $grading, RecommendationService $recommender)
    {
        $user = $request->user();

        if ($user->isLecturer() || $user->isAdmin()) {
            return response()->json($this->lecturerSummary($user));
        }

        return response()->json($this->studentSummary($user, $grading, $recommender));
    }

    private function studentSummary($user, ParticipationGradingService $grading, RecommendationService $recommender): array
    {
        $memberships = $user->memberships()->with('group')->get()->map(fn ($m) => [
            'group_id' => $m->group_id,
            'group_name' => $m->group->name,
            'status' => $m->status,
        ]);

        $mark = ParticipationMark::where('user_id', $user->id)
            ->where('period', $grading->currentPeriod())
            ->orderByDesc('score')
            ->first();

        $score = (int) ($mark->score ?? 0);

        $announcements = Quiz::whereIn('group_id', $memberships->pluck('group_id'))
            ->latest('start_at')
            ->limit(5)
            ->get()
            ->map(fn (Quiz $q) => [
                'id' => $q->id,
                'title' => $q->title,
                'group_id' => $q->group_id,
                'start_at' => $q->start_at?->toIso8601String(),
            ]);

        $recommendations = $recommender->recommendFor($user, 5)->map(fn ($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'group_id' => $t->group_id,
            'category' => $t->category ?? $t->ml_label,
        ]);

        return [
            'role' => 'student',
            'memberships' => $memberships,
            'score' => $score,
            'grade' => $grading->gradeFor($score),
            'announcements' => $announcements,
            'recommendations' => $recommendations,
        ];
    }

    private function lecturerSummary($user): array
    {
        $groups = $user->isAdmin()
            ? \App\Models\Group::withCount('members')->get()
            : \App\Models\Group::where(function ($query) use ($user) {
                $query->whereHas('quizzes', fn ($q) => $q->where('lecturer_id', $user->id))
                    ->orWhere('created_by', $user->id);
            })->withCount('members')->get();

        $quizzes = Quiz::when(! $user->isAdmin(), fn ($q) => $q->where('lecturer_id', $user->id))
            ->latest('start_at')
            ->get()
            ->map(fn (Quiz $q) => [
                'id' => $q->id,
                'title' => $q->title,
                'group_id' => $q->group_id,
                'start_at' => $q->start_at?->toIso8601String(),
                'status' => $q->status,
            ]);

        return [
            'role' => $user->isAdmin() ? 'admin' : 'lecturer',
            'groups' => $groups->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'members_count' => $g->members_count,
            ]),
            'quizzes' => $quizzes,
        ];
    }

    public function notifications(Request $request)
    {
        $notifications = $request->user()->notifications()->limit(20)->get()->map(fn ($n) => [
            'id' => $n->id,
            'message' => $n->data['message'] ?? 'Notification',
            'read' => $n->read_at !== null,
            'created_at' => $n->created_at->toIso8601String(),
        ]);

        return response()->json(['notifications' => $notifications]);
    }

    public function markNotificationRead(Request $request, string $notification)
    {
        $model = $request->user()->notifications()->findOrFail($notification);
        $model->markAsRead();

        return response()->json(['status' => 'read']);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);
    }

    public function updateProfile(ProfileUpdateRequest $request)
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->json(['status' => 'updated']);
    }
}
