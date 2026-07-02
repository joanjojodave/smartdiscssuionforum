<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Group;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, Group $group)
    {
        $user = $request->user();

        $messages = $group->messages()
            ->visibleTo($user)
            ->with('sender')
            ->latest()
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        $members = $group->members()->wherePivot('status', 'active')->get();

        $messagesForJs = $messages->map(fn ($m) => [
            'id' => $m->id,
            'sender_id' => $m->sender_id,
            'sender' => $m->sender->name,
            'body' => $m->body,
        ])->values();

        $storeUrl = route('messages.store', $group);

        return view('groups.messages', compact('group', 'messages', 'members', 'messagesForJs', 'storeUrl'));
    }

    public function store(Request $request, Group $group)
    {
        $data = $request->validate([
            'body' => 'required|string|max:5000',
            'exclude' => 'array',
            'exclude.*' => 'exists:users,id',
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

        return back();
    }
}
