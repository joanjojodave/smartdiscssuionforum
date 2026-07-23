<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lecturer Panel: Academic Management</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex gap-3">
                <a href="{{ route('lecturer.quizzes.create') }}" class="px-4 py-2 bg-fb-600 text-white text-sm rounded-md hover:bg-fb-700">+ Schedule quiz</a>
                <a href="{{ route('lecturer.participation') }}" class="px-4 py-2 bg-white border text-sm rounded-md hover:bg-gray-50">Group participation grading</a>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-5">
                <h3 class="font-semibold text-gray-700 mb-3">Pending quizzes &amp; announcements</h3>
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-400 uppercase text-left"><tr><th class="py-1">Title</th><th>Group</th><th>Starts</th><th>Status</th><th></th></tr></thead>
                    <tbody class="divide-y">
                        @forelse ($quizzes as $quiz)
                            <tr>
                                <td class="py-1">{{ $quiz->title }}</td>
                                <td>{{ $quiz->group->name }}</td>
                                <td>{{ $quiz->start_at->toDayDateTimeString() }}</td>
                                <td>
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $quiz->status === 'scheduled' ? 'bg-yellow-100 text-yellow-800' : ($quiz->status === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700') }}">{{ ucfirst($quiz->status) }}</span>
                                </td>
                                <td><a href="{{ route('lecturer.quizzes.report', $quiz) }}" class="text-fb-700 text-xs hover:underline">View report</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-2 text-gray-400">No quizzes scheduled yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-5">
                <h3 class="font-semibold text-gray-700 mb-3">Groups</h3>
                <ul class="divide-y">
                    @foreach ($groups as $group)
                        <li class="py-2 text-sm flex justify-between items-center">
                            <a href="{{ route('groups.show', $group) }}" class="text-fb-700 hover:underline">{{ $group->name }}</a>
                            <span class="text-xs text-gray-400">{{ $group->members->count() }} member(s)</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
