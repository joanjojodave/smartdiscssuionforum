<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Membership;
use App\Models\Message;
use App\Notifications\GroupJoinDecisionNotification;
use Illuminate\Http\Request;

class GroupApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $memberships = $user->memberships()->pluck('status', 'group_id');

        $groups = Group::withCount('members')->get()->map(fn (Group $g) => [
            'id' => $g->id,
            'name' => $g->name,
            'description' => $g->description,
            'rules' => $g->rules,
            'members_count' => $g->members_count,
            'my_status' => $memberships[$g->id] ?? null,
        ]);

        return response()->json(['groups' => $groups]);
    }

    public function join(Request $request, Group $group)
    {
        $user = $request->user();

        $existing = $group->memberships()->where('user_id', $user->id)->first();

        if ($existing) {
            return response()->json(['status' => $existing->status, 'message' => 'Membership already requested.']);
        }

        $membership = Membership::create([
            'user_id' => $user->id,
            'group_id' => $group->id,
            'status' => 'pending',
            'agreed_rules' => false,
        ]);

        return response()->json(['status' => $membership->status]);
    }

    public function decideRules(Request $request, Group $group)
    {
        $data = $request->validate(['decision' => 'required|in:agree,decline']);
        $user = $request->user();

        $membership = $group->memberships()->where('user_id', $user->id)->firstOrFail();
        $accepted = $data['decision'] === 'agree';

        $membership->update([
            'agreed_rules' => $accepted,
            'status' => $accepted ? 'active' : 'declined',
            'joined_at' => $accepted ? now() : null,
            'last_active_at' => $accepted ? now() : null,
        ]);

        $user->notify(new GroupJoinDecisionNotification($group, $accepted));

        return response()->json(['status' => $membership->status]);
    }

    public function topics(Group $group)
    {
        $topics = $group->topics()->withCount('posts')->latest()->get()->map(fn ($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'category' => $t->category ?? $t->ml_label,
            'is_resolved' => $t->is_resolved,
            'has_unanswered' => $t->hasUnansweredQuestions(),
            'posts_count' => $t->posts_count,
            'author' => $t->author->name,
            'created_at' => $t->created_at->toIso8601String(),
        ]);

        return response()->json(['topics' => $topics]);
    }

    public function members(Request $request, Group $group)
    {
        $members = $group->members()->wherePivot('status', 'active')->get()
            ->reject(fn ($m) => $m->id === $request->user()->id)
            ->values()
            ->map(fn ($m) => ['id' => $m->id, 'name' => $m->name]);

        return response()->json(['members' => $members]);
    }

    public function messages(Request $request, Group $group)
    {
        $messages = $group->messages()
            ->visibleTo($request->user())
            ->with('sender')
            ->latest()
            ->limit(200)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (Message $m) => $this->messagePayload($m));

        return response()->json(['messages' => $messages]);
    }

    public function storeMessage(Request $request, Group $group)
    {
        $data = $request->validate([
            'body' => 'required|string|max:5000',
            'exclude' => 'array',
            'exclude.*' => 'exists:users,id',
            'client_ref' => 'nullable|string|max:100',
        ]);

        $message = Message::create([
            'group_id' => $group->id,
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
            'sync_status' => 'synced',
            'sent_at' => now(),
        ]);

        foreach ($data['exclude'] ?? [] as $excludedUserId) {
            $message->exclusions()->create(['excluded_user_id' => $excludedUserId]);
        }

        $group->memberships()->where('user_id', $request->user()->id)->first()?->touchActivity();

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => $this->messagePayload($message),
            'client_ref' => $data['client_ref'] ?? null,
        ], 201);
    }

    private function messagePayload(Message $m): array
    {
        return [
            'id' => $m->id,
            'sender_id' => $m->sender_id,
            'sender' => $m->sender->name,
            'body' => $m->body,
            'sent_at' => $m->sent_at?->toIso8601String(),
        ];
    }
}
