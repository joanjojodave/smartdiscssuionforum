<?php

namespace App\Http\Controllers\Api;

use App\Events\PostCreated;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Post;
use App\Models\Topic;
use App\Notifications\NewReplyNotification;
use App\Services\ModerationService;
use App\Services\TopicClassifierService;
use Illuminate\Http\Request;

class TopicApiController extends Controller
{
    public function store(Request $request, Group $group, TopicClassifierService $classifier, ModerationService $moderation)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'category' => 'nullable|string|max:80',
            'body' => 'required|string|max:10000',
            'is_question' => 'boolean',
            'client_ref' => 'nullable|string|max:100',
        ]);

        $classification = $classifier->classify($data['title'].' '.$data['body']);

        $topic = Topic::create([
            'group_id' => $group->id,
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'category' => ($data['category'] ?? null) ?: $classification['category'],
            'ml_label' => $classification['category'],
        ]);

        $moderationResult = $moderation->evaluate($topic, $request->user(), $data['body']);

        $post = Post::create([
            'topic_id' => $topic->id,
            'author_id' => $request->user()->id,
            'body' => $data['body'],
            'is_question' => (bool) ($data['is_question'] ?? false),
            'is_relevant' => $moderationResult['is_relevant'],
            'is_flood' => $moderationResult['is_flood'],
            'relevance_score' => $moderationResult['relevance_score'],
        ]);

        $group->memberships()->where('user_id', $request->user()->id)->first()?->touchActivity();

        broadcast(new PostCreated($post))->toOthers();

        return response()->json([
            'topic' => $this->topicPayload($topic->fresh()),
            'client_ref' => $data['client_ref'] ?? null,
        ], 201);
    }

    public function show(Topic $topic)
    {
        $topic->load(['group', 'author']);

        $posts = $topic->posts()
            ->whereNull('parent_post_id')
            ->with(['author', 'replies.author'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Post $p) => $this->postPayload($p, true));

        return response()->json([
            'topic' => $this->topicPayload($topic),
            'posts' => $posts,
        ]);
    }

    public function storePost(Request $request, Topic $topic, ModerationService $moderation)
    {
        $data = $request->validate([
            'body' => 'required|string|max:10000',
            'parent_post_id' => 'nullable|exists:posts,id',
            'is_question' => 'boolean',
            'client_ref' => 'nullable|string|max:100',
        ]);

        $moderationResult = $moderation->evaluate($topic, $request->user(), $data['body']);

        if ($moderationResult['is_flood']) {
            return response()->json(['message' => 'You are posting too quickly. Please slow down before posting again.'], 422);
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

        return response()->json([
            'post' => $this->postPayload($post->fresh(), false),
            'client_ref' => $data['client_ref'] ?? null,
        ], 201);
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

        return response()->json(['status' => 'marked']);
    }

    private function topicPayload(Topic $topic): array
    {
        return [
            'id' => $topic->id,
            'group_id' => $topic->group_id,
            'title' => $topic->title,
            'category' => $topic->category ?? $topic->ml_label,
            'is_resolved' => $topic->is_resolved,
            'author' => $topic->author->name,
            'created_at' => $topic->created_at->toIso8601String(),
            'updated_at' => $topic->updated_at->toIso8601String(),
        ];
    }

    private function postPayload(Post $post, bool $withReplies): array
    {
        $payload = [
            'id' => $post->id,
            'topic_id' => $post->topic_id,
            'parent_post_id' => $post->parent_post_id,
            'author' => $post->author->name,
            'author_id' => $post->author_id,
            'body' => $post->body,
            'is_question' => $post->is_question,
            'is_answer' => $post->is_answer,
            'is_relevant' => $post->is_relevant,
            'is_flood' => $post->is_flood,
            'created_at' => $post->created_at->toIso8601String(),
        ];

        if ($withReplies) {
            $payload['replies'] = $post->replies->map(fn (Post $r) => $this->postPayload($r, false));
        }

        return $payload;
    }
}
