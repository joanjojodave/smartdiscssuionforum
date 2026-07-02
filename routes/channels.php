<?php

use App\Models\Group;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Live discussion + chat updates for a group (SDD 6.3 private-group.{id}).
Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    if ($user->isAdmin()) {
        return true;
    }

    $group = Group::find($groupId);

    return $group && $group->memberships()->where('user_id', $user->id)->where('status', 'active')->exists();
});

// Online presence in a group (SDD 6.3 presence-group.{id}).
Broadcast::channel('presence-group.{groupId}', function ($user, $groupId) {
    $group = Group::find($groupId);

    if (! $group) {
        return false;
    }

    $isMember = $user->isAdmin() || $group->memberships()->where('user_id', $user->id)->where('status', 'active')->exists();

    return $isMember ? ['id' => $user->id, 'name' => $user->name] : false;
});
