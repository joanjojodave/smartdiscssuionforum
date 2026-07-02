<?php

namespace App\Http\Controllers;

use App\Events\PostCreated;
use App\Models\Post;
use App\Models\Topic;
use App\Notifications\NewReplyNotification;
use App\Services\ModerationService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function store(Request $request, Topic $topic, ModerationService $moderation)
    {
        $data = $request->validate([
            'body' => 'required|string|max:10000',
            'parent_post_id' => 'nullable|exists:posts,id',
            'is_question' => 'boolean',
        ]);

        $moderationResult = $moderation->evaluate($topic, $request->user(), $data['body']);

        if ($moderationResult['is_flood']) {
            return back()->withErrors(['body' => 'You are posting too quickly. Please slow down before posting again.']);
        }

        $post = Post::create([
            'topic_id' => $topic->id,
            'author_id' => $request->user()->id,
            'parent_post_id' => $data['parent_post_id'] ?? null,
            'body' => $data['body'],
            'is_question' => (bool) ($data['is_question'] ?? false),
            'is_relevant' => $moderationResult['is_relevant'],
            'is_flood' => $moderationResult['is_flood'],
            'relevance_score' => $moderationResult['relevance_score'],
        ]);

        $topic->group->memberships()->where('user_id', $request->user()->id)->first()?->touchActivity();

        if ($post->parent_post_id) {
            $parent = Post::find($post->parent_post_id);
            if ($parent && $parent->is_question && $parent->author_id !== $request->user()->id) {
                $parent->author->notify(new NewReplyNotification($post));
            }
        }

        broadcast(new PostCreated($post))->toOthers();

        return redirect()->route('topics.show', $topic)->with('status', 'Reply posted.');
    }

    public function markAnswer(Request $request, Post $post)
    {
        $topic = $post->topic;
        $user = $request->user();

        $question = $post->parent;

        abort_unless($question && $question->is_question, 400, 'Only replies to a question can be marked as the answer.');
        abort_unless($user->isAdmin() || $question->author_id === $user->id, 403, 'Only the person who asked or an admin can mark the accepted answer.');

        $topic->posts()->where('parent_post_id', $question->id)->update(['is_answer' => false]);
        $post->update(['is_answer' => true]);

        if (! $topic->posts()->where('is_question', true)
                ->whereDoesntHave('replies', fn ($q) => $q->where('is_answer', true))
                ->exists()) {
            $topic->update(['is_resolved' => true]);
        }

        return back()->with('status', 'Marked as the accepted answer.');
    }
}
