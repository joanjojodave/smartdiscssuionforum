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

            @if ($categories->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 bg-white shadow-sm rounded-lg p-3">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Topics:</span>
                    <a href="{{ route('groups.show', $group) }}"
                       class="px-2.5 py-1 text-xs rounded-full border {{ request('category') ? 'border-gray-200 text-gray-500 hover:bg-gray-50' : 'bg-indigo-600 text-white border-indigo-600' }}">
                        All
                    </a>
                    @foreach ($categories as $category)
                        <a href="{{ route('groups.show', ['group' => $group, 'category' => $category]) }}"
                           class="px-2.5 py-1 text-xs rounded-full border {{ request('category') === $category ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                            {{ $category }}
                        </a>
                    @endforeach
                </div>
            @endif

            @foreach ($topics as $topic)
                @php
                    $label = $topic->category ?? $topic->ml_label;
                    $autoClassified = $label && $topic->ml_label === $label && $topic->category === $topic->ml_label;
                @endphp
                <div class="bg-white shadow-sm rounded-lg p-5 flex items-center justify-between">
                    <div>
                        <a href="{{ route('topics.show', $topic) }}" class="font-semibold text-indigo-700 hover:underline">{{ $topic->title }}</a>
                        @if ($topic->hasUnansweredQuestions())
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-700">Unanswered question</span>
                        @endif
                        @if ($topic->is_resolved)
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">Resolved</span>
                        @endif
                        <p class="text-xs text-gray-400 mt-1 flex items-center gap-2 flex-wrap">
                            <span>by {{ $topic->author->name }} &middot; {{ $topic->created_at->diffForHumans() }} &middot; {{ $topic->posts_count }} post(s)</span>
                            @if ($label)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-medium normal-case">
                                    {{ $label }}
                                    @if ($autoClassified)
                                        <span title="Automatically classified by the topic-classification service" class="text-indigo-400">· auto</span>
                                    @endif
                                </span>
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('topics.export', $topic) }}" class="text-xs px-3 py-1.5 border rounded-md text-gray-600 hover:bg-gray-50">Export PDF</a>
                </div>
            @endforeach

            {{ $topics->links() }}
        </div>
    </div>
</x-app-layout>
