<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Panel: Group Performance</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="font-semibold text-gray-700 mb-3">Group statistics</h3>
                    <table class="w-full text-sm">
                        <thead class="text-xs text-gray-400 uppercase text-left">
                            <tr><th class="py-1">Group</th><th>Posts</th><th>Replies</th><th>Members</th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($groupStats as $row)
                                <tr>
                                    <td class="py-1">
                                        <a href="{{ route('admin.groups.stats', $row['group']) }}" class="text-fb-700 hover:underline">{{ $row['group']->name }}</a>
                                    </td>
                                    <td>{{ $row['posts'] }}</td>
                                    <td>{{ $row['replies'] }}</td>
                                    <td>{{ $row['group']->members_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="font-semibold text-gray-700 mb-3">User moderation queue (inactivity warnings)</h3>
                    <table class="w-full text-sm">
                        <thead class="text-xs text-gray-400 uppercase text-left">
                            <tr><th class="py-1">Name</th><th>Group</th><th>Warnings</th><th>Action</th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($moderationQueue as $membership)
                                <tr>
                                    <td class="py-1">{{ $membership->user->name }}</td>
                                    <td>{{ $membership->group->name }}</td>
                                    <td>{{ $membership->warnings_count }}</td>
                                    <td class="space-x-2">
                                        @if ($membership->status !== 'blacklisted')
                                            <form method="POST" action="{{ route('admin.memberships.warn', $membership) }}" class="inline">
                                                @csrf
                                                <button class="text-orange-600 text-xs hover:underline">Warn</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.memberships.blacklist', $membership) }}" class="inline">
                                                @csrf
                                                <button class="text-red-600 text-xs hover:underline">Blacklist</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.memberships.reinstate', $membership) }}" class="inline">
                                                @csrf
                                                <button class="text-green-600 text-xs hover:underline">Reinstate</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-2 text-gray-400">No warnings right now.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="font-semibold text-gray-700 mb-3">Unanswered questions per group</h3>
                    <table class="w-full text-sm">
                        <thead class="text-xs text-gray-400 uppercase text-left"><tr><th class="py-1">Group</th><th>Unanswered</th></tr></thead>
                        <tbody class="divide-y">
                            @foreach ($unansweredByGroup as $row)
                                <tr><td class="py-1">{{ $row['group']->name }}</td><td>{{ $row['count'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5">
                    <h3 class="font-semibold text-gray-700 mb-3">Flagged irrelevant / flooded material</h3>
                    <table class="w-full text-sm">
                        <thead class="text-xs text-gray-400 uppercase text-left"><tr><th class="py-1">Topic</th><th>Posted by</th><th>Action</th></tr></thead>
                        <tbody class="divide-y">
                            @forelse ($flagged as $post)
                                <tr>
                                    <td class="py-1"><a href="{{ route('topics.show', $post->topic_id) }}#post-{{ $post->id }}" class="text-fb-700 hover:underline">{{ $post->topic->title }}</a></td>
                                    <td>{{ $post->author->name }}</td>
                                    <td class="space-x-2">
                                        <form method="POST" action="{{ route('admin.posts.restore', $post) }}" class="inline">
                                            @csrf
                                            <button class="text-green-600 text-xs hover:underline">Restore</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.posts.delete', $post) }}" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 text-xs hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-2 text-gray-400">Nothing flagged.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex gap-4">
                <a href="{{ route('admin.members') }}" class="text-sm text-fb-700 underline">Warn or restrict any member →</a>
                <a href="{{ route('admin.users') }}" class="text-sm text-fb-700 underline">Manage user roles →</a>
            </div>
        </div>
    </div>
</x-app-layout>
