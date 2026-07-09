<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $topic->title }}</h2>
                <p class="text-xs text-gray-400 flex items-center gap-2">
                    in <a href="{{ route('groups.show', $topic->group) }}" class="underline">{{ $topic->group->name }}</a>
                    @if ($topic->category ?? $topic->ml_label)
                        <span class="inline-flex px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-medium normal-case">{{ $topic->category ?? $topic->ml_label }}</span>
                    @endif
                </p>
            </div>
            <a href="{{ route('topics.export', $topic) }}" class="px-3 py-2 bg-white border text-sm rounded-md hover:bg-gray-50">Export PDF</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @foreach ($posts as $post)
                <div id="post-{{ $post->id }}" class="bg-white shadow-sm rounded-lg p-5 {{ $post->isUnanswered() ? 'ring-2 ring-red-300' : '' }}">
                    @include('topics.partials.post', ['post' => $post])

                    @if ($post->isUnanswered())
                        <p class="mt-3 text-xs font-medium text-red-600">⚠ This question is still unanswered.</p>
                    @endif

                    <div class="mt-4 pl-6 border-l-2 border-gray-100 space-y-4">
                        @foreach ($post->replies as $reply)
                            <div id="post-{{ $reply->id }}" class="{{ $reply->is_answer ? 'bg-green-50 rounded-md p-2' : '' }}">
                                @include('topics.partials.post', ['post' => $reply, 'question' => $post])
                            </div>
                        @endforeach
                    </div>

                    @if ($membership && $membership->status === 'active')
                        <form method="POST" action="{{ route('posts.store', $topic) }}" class="mt-4 flex gap-2">
                            @csrf
                            <input type="hidden" name="parent_post_id" value="{{ $post->id }}">
                            <input type="text" name="body" placeholder="Write a reply..." class="flex-1 rounded-md border-gray-300 text-sm" required>
                            <button class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">Reply</button>
                        </form>
                    @endif
                </div>
            @endforeach

            @if ($membership && $membership->status === 'active')
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="font-medium text-gray-700 mb-2">Add to this topic</h3>
                    <form method="POST" action="{{ route('posts.store', $topic) }}" class="space-y-2">
                        @csrf
                        <textarea name="body" rows="3" class="w-full rounded-md border-gray-300 text-sm" placeholder="Share something related to this topic..." required></textarea>
                        <label class="flex items-center gap-2 text-xs text-gray-600">
                            <input type="checkbox" name="is_question" value="1"> This is a new question
                        </label>
                        <x-primary-button>Post</x-primary-button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
