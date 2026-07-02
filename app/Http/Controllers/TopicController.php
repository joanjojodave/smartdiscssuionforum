<?php

namespace App\Http\Controllers;

use App\Events\PostCreated;
use App\Models\Group;
use App\Models\Post;
use App\Models\Topic;
use App\Services\ModerationService;
use App\Services\TopicClassifierService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function create(Group $group)
    {
        return view('topics.create', compact('group'));
    }

    public function store(Request $request, Group $group, TopicClassifierService $classifier, ModerationService $moderation)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'category' => 'nullable|string|max:80',
            'body' => 'required|string|max:10000',
            'is_question' => 'boolean',
        ]);

        $classification = $classifier->classify($data['title'].' '.$data['body']);

        $topic = Topic::create([
            'group_id' => $group->id,
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'category' => $data['category'] ?: $classification['category'],
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

        return redirect()->route('topics.show', $topic)->with('status', 'Topic created.');
    }

    public function show(Request $request, Topic $topic)
    {
        $topic->load(['group', 'author']);

        $posts = $topic->posts()
            ->whereNull('parent_post_id')
            ->with(['author', 'replies.author'])
            ->orderBy('created_at')
            ->get();

        $membership = $topic->group->memberships()->where('user_id', $request->user()->id)->first();

        return view('topics.show', compact('topic', 'posts', 'membership'));
    }

    public function export(Topic $topic)
    {
        $topic->load(['group', 'author', 'posts' => function ($q) {
            $q->whereNull('parent_post_id')->with(['author', 'replies.author'])->orderBy('created_at');
        }]);

        $pdf = Pdf::loadView('topics.export-pdf', ['topic' => $topic, 'posts' => $topic->posts]);

        return $pdf->download('topic-'.$topic->id.'-'.str($topic->title)->slug().'.pdf');
    }
}
