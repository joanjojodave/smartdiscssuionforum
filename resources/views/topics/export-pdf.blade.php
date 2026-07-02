<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $topic->title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .meta { color: #666; font-size: 10px; margin-bottom: 16px; }
        .post { border-bottom: 1px solid #e5e5e5; padding: 10px 0; }
        .reply { margin-left: 20px; border-left: 2px solid #eee; padding-left: 10px; }
        .author { font-weight: bold; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; background: #eef; font-size: 9px; margin-left: 4px; }
        .badge-answer { background: #dfd; }
    </style>
</head>
<body>
    <h1>{{ $topic->title }}</h1>
    <div class="meta">
        Group: {{ $topic->group->name }} &middot; Category: {{ $topic->category ?? $topic->ml_label }} &middot;
        Started by {{ $topic->author->name }} on {{ $topic->created_at->toFormattedDateString() }} &middot;
        Exported {{ now()->toFormattedDateString() }}
    </div>

    @foreach ($posts as $post)
        <div class="post">
            <span class="author">{{ $post->author->name }}</span>
            <span class="meta">{{ $post->created_at->toDayDateTimeString() }}</span>
            @if ($post->is_question)<span class="badge">Question</span>@endif
            @if ($post->is_answer)<span class="badge badge-answer">Accepted answer</span>@endif
            <p>{{ $post->body }}</p>

            @foreach ($post->replies as $reply)
                <div class="reply">
                    <span class="author">{{ $reply->author->name }}</span>
                    <span class="meta">{{ $reply->created_at->toDayDateTimeString() }}</span>
                    @if ($reply->is_answer)<span class="badge badge-answer">Accepted answer</span>@endif
                    <p>{{ $reply->body }}</p>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
