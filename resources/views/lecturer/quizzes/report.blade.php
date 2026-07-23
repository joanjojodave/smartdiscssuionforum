<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $quiz->title }} - Report ({{ $quiz->group->name }})</h2>
            <a href="{{ route('lecturer.quizzes.create') }}" class="px-3 py-2 bg-fb-600 text-white text-sm rounded-md hover:bg-fb-700">+ Schedule another quiz</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm rounded-lg p-4 text-sm text-gray-600 flex gap-6">
                <span>Status: <strong>{{ ucfirst($quiz->status) }}</strong></span>
                <span>Starts: <strong>{{ $quiz->start_at->toDayDateTimeString() }}</strong></span>
                <span>Duration: <strong>{{ $quiz->duration_minutes }} min</strong></span>
                <span>Questions: <strong>{{ $quiz->questions->count() }}</strong></span>
                <span>Total marks: <strong>{{ $totalMarks }}</strong></span>
            </div>

            @include('quizzes.partials.report-table')
        </div>
    </div>
</x-app-layout>
