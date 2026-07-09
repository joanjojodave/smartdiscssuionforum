<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Membership;
use App\Notifications\GroupJoinDecisionNotification;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $groups = Group::withCount('members')->latest()->get();
        $myMemberships = $user->memberships()->pluck('status', 'group_id');

        return view('groups.index', compact('groups', 'myMemberships'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('groups.create');
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',
            'rules' => 'required|string|max:4000',
            'inactivity_warning_days' => 'required|integer|min:1|max:90',
            'inactivity_blacklist_days' => 'required|integer|min:1|max:90',
            'blacklist_duration_days' => 'required|integer|min:1|max:90',
        ]);

        $group = Group::create($data + ['created_by' => $request->user()->id]);

        return redirect()->route('groups.show', $group)->with('status', 'Group created.');
    }

    public function show(Request $request, Group $group)
    {
        $user = $request->user();
        $membership = $group->memberships()->where('user_id', $user->id)->first();

        $categories = $group->topics()
            ->selectRaw('COALESCE(category, ml_label) as label')
            ->whereNotNull('category')
            ->orWhereNotNull('ml_label')
            ->distinct()
            ->pluck('label')
            ->filter()
            ->sort()
            ->values();

        $topicsQuery = $group->topics()->withCount('posts')->latest();

        if ($request->filled('category')) {
            $topicsQuery->where(function ($q) use ($request) {
                $q->where('category', $request->category)->orWhere('ml_label', $request->category);
            });
        }

        $topics = $topicsQuery->paginate(10)->withQueryString();

        return view('groups.show', compact('group', 'membership', 'topics', 'categories'));
    }

    public function join(Request $request, Group $group)
    {
        $user = $request->user();

        $existing = $group->memberships()->where('user_id', $user->id)->first();

        if ($existing) {
            return back()->with('status', 'You already have a membership request for this group.');
        }

        Membership::create([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'status' => 'pending',
            'agreed_rules' => false,
        ]);

        return redirect()->route('groups.rules', $group);
    }

    public function showRules(Request $request, Group $group)
    {
        $membership = $group->memberships()->where('user_id', $request->user()->id)->firstOrFail();

        return view('groups.rules', compact('group', 'membership'));
    }

    public function agreeRules(Request $request, Group $group)
    {
        $request->validate(['decision' => 'required|in:agree,decline']);

        $membership = $group->memberships()->where('user_id', $request->user()->id)->firstOrFail();

        $accepted = $request->decision === 'agree';

        $membership->update([
            'agreed_rules' => $accepted,
            'status' => $accepted ? 'active' : 'declined',
            'joined_at' => $accepted ? now() : null,
            'last_active_at' => $accepted ? now() : null,
        ]);

        $request->user()->notify(new GroupJoinDecisionNotification($group, $accepted));

        return $accepted
            ? redirect()->route('groups.show', $group)->with('status', 'Welcome to the group!')
            : redirect()->route('groups.index')->with('status', 'You declined the group rules, so the request was not completed.');
    }
}
