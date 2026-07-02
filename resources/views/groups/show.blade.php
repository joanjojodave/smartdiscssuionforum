<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $group->name }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('messages.index', $group) }}" class="px-3 py-2 bg-white border text-sm rounded-md hover:bg-gray-50">Group chat</a>
                @if ($membership && $membership->status === 'active')
                    <a href="{{ route('topics.create', $group) }}" class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">+ New topic</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (! $membership)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded-md p-4">
                    You are not a member of this group yet.
                    <form method="POST" action="{{ route('groups.join', $group) }}" class="inline">
                        @csrf
                        <button class="underline font-medium">Join now</button>
                    </form>
                </div>
            @elseif ($membership->status === 'pending')
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded-md p-4">
                    Your join request is pending. <a class="underline font-medium" href="{{ route('groups.rules', $group) }}">Accept the group rules</a> to participate.
                </div>
            @elseif ($membership->isBlacklisted())
                <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-md p-4">
                    You are restricted from participating in this group until {{ $membership->blacklist_until->toDayDateTimeString() }}.
                </div>
            @endif

            @foreach ($topics as $topic)
                <div class="bg-white shadow-sm rounded-lg p-5 flex items-center justify-between">
                    <div>
                        <a href="{{ route('topics.show', $topic) }}" class="font-semibold text-indigo-700 hover:underline">{{ $topic->title }}</a>
                        @if ($topic->hasUnansweredQuestions())
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-700">Unanswered question</span>
                        @endif
                        @if ($topic->is_resolved)
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">Resolved</span>
                        @endif
                        <p class="text-xs text-gray-400 mt-1">
                            by {{ $topic->author->name }} &middot; {{ $topic->created_at->diffForHumans() }}
                            &middot; category: {{ $topic->category ?? $topic->ml_label ?? 'general' }}
                            &middot; {{ $topic->posts_count }} post(s)
                        </p>
                    </div>
                    <a href="{{ route('topics.export', $topic) }}" class="text-xs px-3 py-1.5 border rounded-md text-gray-600 hover:bg-gray-50">Export PDF</a>
                </div>
            @endforeach

            {{ $topics->links() }}
        </div>
    </div>
</x-app-layout>
