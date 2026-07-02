<?php

namespace App\Http\Middleware;

use App\Models\Group;
use App\Models\Topic;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveGroupMember
{
    /**
     * Ensures the authenticated user is an active (non-blacklisted, rules-accepted)
     * member of the group before allowing discussion actions. The group is
     * resolved either from a {group} route parameter directly, or via a
     * {topic} route parameter's group_id (e.g. posts.store).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $group = $this->resolveGroup($request);

        if (! $user || ! $group) {
            abort(404);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $membership = $group->memberships()->where('user_id', $user->id)->first();

        if (! $membership || $membership->status === 'pending' || $membership->status === 'declined') {
            abort(403, 'You must join this group and accept its rules before participating.');
        }

        if ($membership->isBlacklisted()) {
            abort(403, 'You are temporarily blacklisted from this group until '.$membership->blacklist_until->toDayDateTimeString().'.');
        }

        return $next($request);
    }

    private function resolveGroup(Request $request): ?Group
    {
        $group = $request->route('group');

        if ($group instanceof Group) {
            return $group;
        }

        if ($group !== null) {
            return Group::find($group);
        }

        $topic = $request->route('topic');

        if ($topic instanceof Topic) {
            return $topic->group;
        }

        if ($topic !== null) {
            return Topic::find($topic)?->group;
        }

        return null;
    }
}
