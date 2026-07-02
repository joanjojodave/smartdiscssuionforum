<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\ParticipationGradingService;
use Illuminate\Http\Request;

class ParticipationController extends Controller
{
    public function index(Request $request, ParticipationGradingService $grading)
    {
        $groups = Group::with('members')->get();

        $groupId = $request->integer('group_id') ?: $groups->first()?->id;
        $group = $groups->firstWhere('id', $groupId);

        $marks = [];

        if ($group) {
            foreach ($group->members as $member) {
                $mark = $member->participationMarks()
                    ->where('group_id', $group->id)
                    ->where('period', $grading->currentPeriod())
                    ->first();

                $score = (int) ($mark->score ?? 0);

                $marks[] = [
                    'user' => $member,
                    'posts' => \App\Models\Post::whereHas('topic', fn ($q) => $q->where('group_id', $group->id))->where('author_id', $member->id)->count(),
                    'replies' => \App\Models\Post::whereHas('topic', fn ($q) => $q->where('group_id', $group->id))->where('author_id', $member->id)->whereNotNull('parent_post_id')->count(),
                    'score' => $score,
                    'grade' => $grading->gradeFor($score),
                ];
            }
        }

        return view('lecturer.participation', compact('groups', 'group', 'marks'));
    }

    public function recompute(Request $request, ParticipationGradingService $grading)
    {
        $group = Group::findOrFail($request->integer('group_id'));
        $grading->recomputeForGroup($group);

        return back()->with('status', 'Participation marks recomputed for '.$group->name.'.');
    }
}
