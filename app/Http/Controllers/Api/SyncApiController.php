<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Events\PostCreated;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Message;
use App\Models\Post;
use App\Models\Topic;
use App\Services\ModerationService;
use App\Services\TopicClassifierService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Backs the desktop client's offline-first sync (SDD 5.11 Synchronization
 * Component / requirement #8). GET /pull returns everything changed since a
 * given timestamp for the caller's active groups; POST /push replays a batch
 * of actions the desktop queued while offline. Posts/messages are
 * create-only from the client's perspective, so there is nothing to merge --
 * each queued action just becomes a new row, keyed by a client-generated
 * reference so the client can reconcile its local temp IDs.
 */
class SyncApiController extends Controller
{
    public function pull(Request $request)
    {
        $user = $request->user();
        $since = $request->query('since') ? Carbon::parse($request->query('since')) : Carbon::createFromTimestamp(0);

        $groupIds = $user->memberships()->where('status', 'active')->pluck('group_id');
        $groups = Group::whereIn('id', $groupIds)->get();

        $topics = Topic::whereIn('group_id', $groupIds)->where('updated_at', '>', $since)->get();

        $posts = Post::whereHas('topic', fn ($q) => $q->whereIn('group_id', $groupIds))
            ->where('updated_at', '>', $since)
            ->with('author')
            ->get();

        $messages = Message::whereIn('group_id', $groupIds)
            ->visibleTo($user)
            ->where('updated_at', '>', $since)
            ->with('sender')
            ->get();

        $quizzes = \App\Models\Quiz::whereIn('group_id', $groupIds)
            ->where('updated_at', '>', $since)
            ->with('questions')
            ->get();

        return response()->json([
            'synced_at' => now()->toIso8601String(),
            'groups' => $groups->map(fn (Group $g) => ['id' => $g->id, 'name' => $g->name, 'description' => $g->description]),
            'topics' => $topics->map(fn (Topic $t) => [
                'id' => $t->id, 'group_id' => $t->group_id, 'title' => $t->title,
                'category' => $t->category ?? $t->ml_label, 'is_resolved' => $t->is_resolved,
                'has_unanswered' => $t->hasUnansweredQuestions(), 'posts_count' => $t->posts()->count(),
                'author' => $t->author->name,
                'updated_at' => $t->updated_at->toIso8601String(),
            ]),
            'posts' => $posts->map(fn (Post $p) => [
                'id' => $p->id, 'topic_id' => $p->topic_id, 'parent_post_id' => $p->parent_post_id,
                'author' => $p->author->name, 'author_id' => $p->author_id, 'body' => $p->body,
                'is_question' => $p->is_question, 'is_answer' => $p->is_answer,
                'created_at' => $p->created_at->toIso8601String(), 'updated_at' => $p->updated_at->toIso8601String(),
            ]),
            'messages' => $messages->map(fn (Message $m) => [
                'id' => $m->id, 'group_id' => $m->group_id, 'sender_id' => $m->sender_id,
                'sender' => $m->sender->name, 'body' => $m->body,
                'sent_at' => $m->sent_at?->toIso8601String(),
            ]),
            'quizzes' => $quizzes->map(fn ($q) => [
                'id' => $q->id, 'group_id' => $q->group_id, 'title' => $q->title,
                'start_at' => $q->start_at->toIso8601String(), 'duration_minutes' => $q->duration_minutes,
                'status' => $q->status,
            ]),
        ]);
    }

    public function push(Request $request, ModerationService $moderation, TopicClassifierService $classifier)
    {
        $user = $request->user();

        $data = $request->validate([
            'actions' => 'array',
            'actions.*.type' => 'required|in:post,message',
            'actions.*.client_ref' => 'required|string',
        ]);

        $results = [];

        foreach ($data['actions'] ?? [] as $action) {
            try {
                $results[] = match ($action['type']) {
                    'post' => $this->replayPost($user, $action, $moderation),
                    'message' => $this->replayMessage($user, $action),
                };
            } catch (\Throwable $e) {
                $results[] = ['client_ref' => $action['client_ref'], 'status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        return response()->json(['synced_at' => now()->toIso8601String(), 'results' => $results]);
    }

    private function replayPost($user, array $action, ModerationService $moderation): array
    {
        $topic = Topic::findOrFail($action['topic_id']);
        $moderationResult = $moderation->evaluate($topic, $user, $action['body']);

        $post = Post::create([
            'topic_id' => $topic->id,
            'author_id' => $user->id,
            'parent_post_id' => $action['parent_post_id'] ?? null,
            'body' => $action['body'],
            'is_question' => (bool) ($action['is_question'] ?? false),
            'is_relevant' => $moderationResult['is_relevant'],
            'is_flood' => $moderationResult['is_flood'],
            'relevance_score' => $moderationResult['relevance_score'],
        ]);

        broadcast(new PostCreated($post))->toOthers();

        return ['client_ref' => $action['client_ref'], 'status' => 'created', 'server_id' => $post->id];
    }

    private function replayMessage($user, array $action): array
    {
        $message = Message::create([
            'group_id' => $action['group_id'],
            'sender_id' => $user->id,
            'body' => $action['body'],
            'sync_status' => 'synced',
            'sent_at' => now(),
        ]);

        foreach ($action['exclude'] ?? [] as $excludedUserId) {
            $message->exclusions()->create(['excluded_user_id' => $excludedUserId]);
        }

        broadcast(new MessageSent($message))->toOthers();

        return ['client_ref' => $action['client_ref'], 'status' => 'created', 'server_id' => $message->id];
    }
}
