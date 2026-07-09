<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Members — Warn or restrict anyone</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm rounded-lg p-4">
                <form method="GET" class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Group:</label>
                    <select name="group_id" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm">
                        <option value="">All groups</option>
                        @foreach ($groups as $g)
                            <option value="{{ $g->id }}" {{ (int) $groupId === $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-2">Member</th>
                            <th class="px-4 py-2">Group</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Warnings</th>
                            <th class="px-4 py-2">Last active</th>
                            <th class="px-4 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($memberships as $m)
                            <tr>
                                <td class="px-4 py-2">{{ $m->user->name }}</td>
                                <td class="px-4 py-2">{{ $m->group->name }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 text-xs rounded-full
                                        {{ $m->status === 'blacklisted' ? 'bg-red-100 text-red-700' : ($m->status === 'warned' ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700') }}">
                                        {{ ucfirst($m->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">{{ $m->warnings_count }}</td>
                                <td class="px-4 py-2">{{ $m->last_active_at?->diffForHumans() ?? 'never' }}</td>
                                <td class="px-4 py-2 space-x-2 whitespace-nowrap">
                                    @if ($m->status !== 'blacklisted')
                                        <form method="POST" action="{{ route('admin.memberships.warn', $m) }}" class="inline">
                                            @csrf
                                            <button class="text-orange-600 text-xs hover:underline">Warn</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.memberships.blacklist', $m) }}" class="inline">
                                            @csrf
                                            <button class="text-red-600 text-xs hover:underline">Blacklist</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.memberships.reinstate', $m) }}" class="inline">
                                            @csrf
                                            <button class="text-green-600 text-xs hover:underline">Reinstate</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-4 text-center text-gray-400">No members found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
