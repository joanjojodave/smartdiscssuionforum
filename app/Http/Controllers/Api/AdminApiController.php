<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Membership;
use Illuminate\Http\Request;

class AdminApiController extends Controller
{
    public function members(Request $request)
    {
        $groupId = $request->integer('group_id');

        $memberships = Membership::with(['user', 'group'])
            ->whereIn('memberships.status', ['active', 'warned', 'blacklisted'])
            ->when($groupId, fn ($q) => $q->where('group_id', $groupId))
            ->join('users', 'users.id', '=', 'memberships.user_id')
            ->orderBy('users.name')
            ->select('memberships.*')
            ->get()
            ->map(fn (Membership $m) => $this->payload($m));

        $groups = Group::orderBy('name')->get(['id', 'name']);

        return response()->json(['memberships' => $memberships, 'groups' => $groups]);
    }

    public function warn(Membership $membership)
    {
        $membership->update([
            'warnings_count' => min($membership->warnings_count + 1, 3),
            'status' => $membership->warnings_count + 1 >= 3 ? 'blacklisted' : 'warned',
            'blacklist_until' => $membership->warnings_count + 1 >= 3 ? now()->addDays($membership->group->blacklist_duration_days) : null,
        ]);

        return response()->json(['membership' => $this->payload($membership->fresh(['user', 'group']))]);
    }

    public function blacklist(Request $request, Membership $membership)
    {
        $days = $request->integer('days') ?: $membership->group->blacklist_duration_days;

        $membership->update([
            'status' => 'blacklisted',
            'blacklist_until' => now()->addDays($days),
        ]);

        return response()->json(['membership' => $this->payload($membership->fresh(['user', 'group']))]);
    }

    public function reinstate(Membership $membership)
    {
        $membership->update(['status' => 'active', 'warnings_count' => 0, 'blacklist_until' => null, 'last_active_at' => now()]);

        return response()->json(['membership' => $this->payload($membership->fresh(['user', 'group']))]);
    }

    private function payload(Membership $m): array
    {
        return [
            'id' => $m->id,
            'user_name' => $m->user->name,
            'group_name' => $m->group->name,
            'status' => $m->status,
            'warnings_count' => $m->warnings_count,
            'last_active_at' => $m->last_active_at?->toIso8601String(),
        ];
    }
}
