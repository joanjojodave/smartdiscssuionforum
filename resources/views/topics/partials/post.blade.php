@php $question ??= null; @endphp
<div class="flex items-start justify-between">
    <div>
        <span class="font-medium text-gray-800">{{ $post->author->name }}</span>
        <span class="text-xs text-gray-400">{{ $post->created_at->diffForHumans() }}</span>
        @if ($post->is_question)
            <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">Question</span>
        @endif
        @if ($post->is_answer)
            <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">✓ Accepted answer</span>
        @endif
        @if (! $post->is_relevant)
            <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600" title="Flagged as low relevance by the moderation filter">Low relevance</span>
        @endif
        @if ($post->is_flood)
            <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-700">Flagged: flooding</span>
        @endif
    </div>
    <div class="flex items-center gap-2">
        @if ($question && $question->is_question && ! $post->is_answer && (auth()->id() === $question->author_id || auth()->user()->isAdmin()))
            <form method="POST" action="{{ route('posts.mark-answer', $post) }}">
                @csrf
                <button class="text-xs text-green-700 hover:underline">Mark as answer</button>
            </form>
        @endif
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="text-xs text-gray-400 hover:text-gray-600">Share ▾</button>
            <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-1 w-32 bg-white border rounded-md shadow-lg text-xs z-10">
                <a href="{{ route('posts.share', ['post' => $post, 'platform' => 'twitter']) }}" target="_blank" class="block px-3 py-2 hover:bg-gray-50">X / Twitter</a>
                <a href="{{ route('posts.share', ['post' => $post, 'platform' => 'facebook']) }}" target="_blank" class="block px-3 py-2 hover:bg-gray-50">Facebook</a>
                <a href="{{ route('posts.share', ['post' => $post, 'platform' => 'whatsapp']) }}" target="_blank" class="block px-3 py-2 hover:bg-gray-50">WhatsApp</a>
                <a href="{{ route('posts.share', ['post' => $post, 'platform' => 'linkedin']) }}" target="_blank" class="block px-3 py-2 hover:bg-gray-50">LinkedIn</a>
            </div>
        </div>
    </div>
</div>
<p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $post->body }}</p>
