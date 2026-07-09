<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Membership;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $groups = Group::withCount('members', 'topics')->get();

        $groupStats = $groups->map(function (Group $group) {
            return [
                'group' => $group,
                'posts' => Post::whereHas('topic', fn ($q) => $q->where('group_id', $group->id))->count(),
                'replies' => Post::whereHas('topic', fn ($q) => $q->where('group_id', $group->id))->whereNotNull('parent_post_id')->count(),
                'comments' => Post::whereHas('topic', fn ($q) => $q->where('group_id', $group->id))->where('is_question', false)->count(),
            ];
        });

        $moderationQueue = Membership::with(['user', 'group'])
            ->whereIn('status', ['warned', 'blacklisted'])
            ->orderByDesc('warnings_count')
            ->limit(10)
            ->get();

        $unansweredByGroup = $groups->map(function (Group $group) {
            return [
                'group' => $group,
                'count' => Topic::where('group_id', $group->id)
                    ->whereHas('posts', fn ($q) => $q->where('is_question', true)->where('is_answer', false))
                    ->count(),
            ];
        });

        $flagged = Post::with(['topic', 'author'])
            ->where(function ($q) {
                $q->where('is_relevant', false)->orWhere('is_flood', true);
            })
            ->latest()
            ->limit(15)
            ->get();

        return view('admin.dashboard', compact('groupStats', 'moderationQueue', 'unansweredByGroup', 'flagged'));
    }

    public function groupStats(Group $group)
    {
        $stats = [
            'members' => $group->members()->count(),
            'topics' => $group->topics()->count(),
            'posts' => Post::whereHas('topic', fn ($q) => $q->where('group_id', $group->id))->count(),
            'unanswered' => Topic::where('group_id', $group->id)
                ->whereHas('posts', fn ($q) => $q->where('is_question', true)->where('is_answer', false))
                ->count(),
            'quizzes' => $group->quizzes()->count(),
        ];

        return view('admin.group-stats', compact('group', 'stats'));
    }

    /**
     * A single, obvious place for an admin to warn or restrict *any*
     * member across *any* group -- not just members the automated
     * inactivity pipeline has already flagged (SDD 1.2 / requirement #4).
     */
    public function members(Request $request)
    {
        $groups = Group::orderBy('name')->get();
        $groupId = $request->integer('group_id');

        $memberships = Membership::with(['user', 'group'])
            ->whereIn('memberships.status', ['active', 'warned', 'blacklisted'])
            ->when($groupId, fn ($q) => $q->where('group_id', $groupId))
            ->join('users', 'users.id', '=', 'memberships.user_id')
            ->orderBy('users.name')
            ->select('memberships.*')
            ->get();

        return view('admin.members', compact('groups', 'memberships', 'groupId'));
    }

    public function flags(Group $group)
    {
        $memberships = $group->memberships()->with('user')->orderByDesc('warnings_count')->get();
        $flaggedPosts = Post::whereHas('topic', fn ($q) => $q->where('group_id', $group->id))
            ->where(function ($q) {
                $q->where('is_relevant', false)->orWhere('is_flood', true);
            })
            ->with(['topic', 'author'])
            ->latest()
            ->get();

        return view('admin.group-flags', compact('group', 'memberships', 'flaggedPosts'));
    }

    public function warnMembership(Membership $membership)
    {
        $membership->update([
            'warnings_count' => min($membership->warnings_count + 1, 3),
            'status' => $membership->warnings_count + 1 >= 3 ? 'blacklisted' : 'warned',
            'blacklist_until' => $membership->warnings_count + 1 >= 3 ? now()->addDays($membership->group->blacklist_duration_days) : null,
        ]);

        return back()->with('status', 'Warning recorded for '.$membership->user->name.'.');
    }

    /**
     * Requirement #4: administrators can directly restrict a selected
     * member from participating (communication restriction), independent
     * of the automated inactivity pipeline.
     */
    public function blacklistMembership(Request $request, Membership $membership)
    {
        $days = $request->integer('days') ?: $membership->group->blacklist_duration_days;

        $membership->update([
            'status' => 'blacklisted',
            'blacklist_until' => now()->addDays($days),
        ]);

        return back()->with('status', $membership->user->name.' has been restricted for '.$days.' day(s).');
    }

    public function reinstateMembership(Membership $membership)
    {
        $membership->update(['status' => 'active', 'warnings_count' => 0, 'blacklist_until' => null, 'last_active_at' => now()]);

        return back()->with('status', $membership->user->name.' has been reinstated.');
    }

    public function deletePost(Post $post)
    {
        $post->delete();

        return back()->with('status', 'Post removed.');
    }

    public function restorePost(Post $post)
    {
        $post->update(['is_relevant' => true, 'is_flood' => false]);

        return back()->with('status', 'Post restored to the discussion.');
    }

    public function users()
    {
        $users = User::withCount('memberships')->orderBy('role')->orderBy('name')->get();

        return view('admin.users', compact('users'));
    }

    public function updateUserRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|in:admin,lecturer,member']);
        $user->update(['role' => $request->role]);

        return back()->with('status', $user->name.' is now '.$request->role.'.');
    }
}
