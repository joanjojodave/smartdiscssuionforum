<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Membership;
use App\Notifications\InactivityBlacklistedNotification;
use App\Notifications\InactivityWarningNotification;

/**
 * Implements requirement #4 / SDD section 1.2: members who go quiet get two
 * warnings, and a third breach of the inactivity threshold blacklists them
 * for a configurable duration. The blacklist clears automatically once
 * blacklist_until passes (checked lazily via Membership::isBlacklisted()).
 */
class InactivityMonitorService
{
    public function checkGroup(Group $group): void
    {
        $warningThreshold = now()->subDays($group->inactivity_warning_days);

        $memberships = $group->memberships()
            ->whereIn('status', ['active', 'warned'])
            ->where(function ($q) use ($warningThreshold) {
                $q->where('last_active_at', '<', $warningThreshold)
                    ->orWhereNull('last_active_at');
            })
            ->get();

        foreach ($memberships as $membership) {
            $this->applyBreach($membership, $group);
        }

        // Automatically lift blacklists whose timer has expired.
        Membership::where('group_id', $group->id)
            ->where('status', 'blacklisted')
            ->where('blacklist_until', '<=', now())
            ->update(['status' => 'active', 'warnings_count' => 0, 'blacklist_until' => null]);
    }

    private function applyBreach(Membership $membership, Group $group): void
    {
        $membership->warnings_count += 1;

        if ($membership->warnings_count >= 3) {
            $membership->status = 'blacklisted';
            $membership->blacklist_until = now()->addDays($group->blacklist_duration_days);
            $membership->save();

            $membership->user->notify(new InactivityBlacklistedNotification($group, $membership->blacklist_until));

            return;
        }

        $membership->status = 'warned';
        $membership->save();

        $membership->user->notify(new InactivityWarningNotification($group, $membership->warnings_count));
    }
}
