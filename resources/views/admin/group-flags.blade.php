<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $group->name }} - Flags</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm rounded-lg p-5">
                <h3 class="font-semibold text-gray-700 mb-3">Membership status</h3>
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-400 uppercase text-left"><tr><th class="py-1">Member</th><th>Status</th><th>Warnings</th><th>Last active</th><th>Action</th></tr></thead>
                    <tbody class="divide-y">
                        @foreach ($memberships as $m)
                            <tr>
                                <td class="py-1">{{ $m->user->name }}</td>
                                <td>{{ ucfirst($m->status) }}</td>
                                <td>{{ $m->warnings_count }}</td>
                                <td>{{ $m->last_active_at?->diffForHumans() ?? 'never' }}</td>
                                <td class="space-x-2 py-1">
                                    @if (in_array($m->status, ['active', 'warned']))
                                        <form method="POST" action="{{ route('admin.memberships.warn', $m) }}" class="inline">
                                            @csrf
                                            <button class="text-orange-600 text-xs hover:underline">Warn</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.memberships.blacklist', $m) }}" class="inline">
                                            @csrf
                                            <button class="text-red-600 text-xs hover:underline">Blacklist</button>
                                        </form>
                                    @elseif ($m->status === 'blacklisted')
                                        <form method="POST" action="{{ route('admin.memberships.reinstate', $m) }}" class="inline">
                                            @csrf
                                            <button class="text-green-600 text-xs hover:underline">Reinstate</button>
                                        </form>
                                    @else
                                        <span class="text-gray-300">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-5">
                <h3 class="font-semibold text-gray-700 mb-3">Flagged posts</h3>
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-400 uppercase text-left"><tr><th class="py-1">Topic</th><th>Author</th><th>Reason</th></tr></thead>
                    <tbody class="divide-y">
                        @forelse ($flaggedPosts as $post)
                            <tr>
                                <td class="py-1"><a href="{{ route('topics.show', $post->topic_id) }}" class="text-fb-700 hover:underline">{{ $post->topic->title }}</a></td>
                                <td>{{ $post->author->name }}</td>
                                <td>{{ $post->is_flood ? 'Flooding' : 'Low relevance' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-2 text-gray-400">Nothing flagged.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
