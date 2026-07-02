<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Quizzes &amp; Announcements</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @forelse ($quizzes as $quiz)
                <div class="bg-white shadow-sm rounded-lg p-5 flex items-center justify-between">
                    <div>
                        <a href="{{ route('quizzes.show', $quiz) }}" class="font-semibold text-indigo-700 hover:underline">{{ $quiz->title }}</a>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $quiz->group->name }} &middot; starts {{ $quiz->start_at->toDayDateTimeString() }} &middot; {{ $quiz->duration_minutes }} minutes
                            @if ($quiz->target_category) &middot; category: {{ $quiz->target_category }} @endif
                        </p>
                    </div>
                    <span class="px-3 py-1 text-xs rounded-full
                        {{ $quiz->status === 'scheduled' ? 'bg-yellow-100 text-yellow-800' : ($quiz->status === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700') }}">
                        {{ ucfirst($quiz->status) }}
                    </span>
                </div>
            @empty
                <p class="text-gray-500">No quizzes scheduled yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
