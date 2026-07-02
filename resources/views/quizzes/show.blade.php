<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $quiz->title }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6 space-y-3">
                <p class="text-sm text-gray-600">Group: <strong>{{ $quiz->group->name }}</strong></p>
                <p class="text-sm text-gray-600">Starts: <strong>{{ $quiz->start_at->toDayDateTimeString() }}</strong></p>
                <p class="text-sm text-gray-600">Duration: <strong>{{ $quiz->duration_minutes }} minutes</strong> (auto-submits when time is up; late joiners get no extra time)</p>
                @if ($quiz->target_category)
                    <p class="text-sm text-gray-600">Category: <strong>{{ $quiz->target_category }}</strong></p>
                @endif

                <div class="pt-4 border-t">
                    @if ($quiz->status === 'scheduled')
                        <p class="text-yellow-700 text-sm">This quiz has not opened yet. Come back at the scheduled start time.</p>
                    @elseif (! $attempt)
                        @if ($quiz->status === 'open')
                            <form method="POST" action="{{ route('quizzes.start', $quiz) }}">
                                @csrf
                                <x-primary-button>Start quiz now</x-primary-button>
                            </form>
                        @else
                            <p class="text-gray-500 text-sm">This quiz has closed and you did not attempt it.</p>
                        @endif
                    @elseif ($attempt->status === 'in_progress')
                        <a href="{{ route('quizzes.attempt', $quiz) }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-md">Continue attempt</a>
                    @else
                        <p class="text-green-700 text-sm">You {{ $attempt->status === 'auto_submitted' ? 'were auto-submitted' : 'submitted' }} this quiz.</p>
                    @endif

                    @if ($quiz->status === 'closed')
                        <a href="{{ route('quizzes.report', $quiz) }}" class="inline-block mt-3 text-sm text-indigo-700 underline">View performance report</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
