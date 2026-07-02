<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white shadow-sm rounded-lg p-5 md:col-span-2">
                    <h3 class="font-semibold text-gray-700 mb-3">📣 Announcements &amp; upcoming quizzes</h3>
                    <ul class="divide-y">
                        @forelse ($announcements as $quiz)
                            <li class="py-2 flex justify-between items-center text-sm">
                                <a href="{{ route('quizzes.show', $quiz) }}" class="text-indigo-700 hover:underline">{{ $quiz->title }}</a>
                                <span class="text-xs text-gray-400">{{ $quiz->start_at->toDayDateTimeString() }}</span>
                            </li>
                        @empty
                            <li class="py-2 text-sm text-gray-400">No announcements yet.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5 text-center">
                    <h3 class="font-semibold text-gray-700 mb-2">🏆 Participation marks</h3>
                    <div class="text-3xl font-bold text-indigo-700">{{ $score }}%</div>
                    <div class="text-sm text-gray-500">Grade: {{ $grade }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="font-semibold text-gray-700 mb-3">📚 My groups</h3>
                    <ul class="divide-y">
                        @forelse ($memberships as $membership)
                            <li class="py-2 text-sm flex justify-between items-center">
                                <a href="{{ route('groups.show', $membership->group) }}" class="text-indigo-700 hover:underline">{{ $membership->group->name }}</a>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $membership->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ ucfirst($membership->status) }}</span>
                            </li>
                        @empty
                            <li class="py-2 text-sm text-gray-400">You haven't joined any group yet.</li>
                        @endforelse
                    </ul>
                    <a href="{{ route('groups.index') }}" class="text-xs text-indigo-700 underline mt-2 inline-block">Browse groups →</a>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="font-semibold text-gray-700 mb-3">✨ Recommended for you</h3>
                    <ul class="divide-y">
                        @forelse ($recommendations as $topic)
                            <li class="py-2 text-sm">
                                <a href="{{ route('topics.show', $topic) }}" class="text-indigo-700 hover:underline">{{ $topic->title }}</a>
                                <div class="text-xs text-gray-400">{{ $topic->category ?? $topic->ml_label }}</div>
                            </li>
                        @empty
                            <li class="py-2 text-sm text-gray-400">Engage in a few discussions to get recommendations.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="font-semibold text-gray-700 mb-3">🔔 Recent notifications</h3>
                    <ul class="divide-y">
                        @forelse ($notifications as $n)
                            <li class="py-2 text-sm {{ $n->read_at ? 'text-gray-400' : 'text-gray-700 font-medium' }}">
                                {{ $n->data['message'] ?? 'Notification' }}
                            </li>
                        @empty
                            <li class="py-2 text-sm text-gray-400">Nothing new.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
