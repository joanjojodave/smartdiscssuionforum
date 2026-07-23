<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Groups</h2>
            @if (Auth::user()->isAdmin())
                <a href="{{ route('groups.create') }}" class="px-3 py-2 bg-fb-600 text-white text-sm rounded-md hover:bg-fb-700">+ New Group</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @forelse ($groups as $group)
                @php $status = $myMemberships[$group->id] ?? null; @endphp
                <div class="bg-white shadow-sm rounded-lg p-5 flex items-center justify-between">
                    <div>
                        <a href="{{ route('groups.show', $group) }}" class="text-lg font-semibold text-fb-700 hover:underline">{{ $group->name }}</a>
                        <p class="text-sm text-gray-500">{{ $group->description }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $group->members_count }} member(s)</p>
                    </div>
                    <div>
                        @if ($status === 'active')
                            <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800">Member</span>
                        @elseif ($status === 'pending')
                            <a href="{{ route('groups.rules', $group) }}" class="px-3 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Accept rules to join</a>
                        @elseif ($status === 'blacklisted')
                            <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800">Restricted</span>
                        @elseif ($status === 'warned')
                            <span class="px-3 py-1 text-xs rounded-full bg-orange-100 text-orange-800">Warned</span>
                        @elseif ($status === 'declined')
                            <span class="px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-600">Declined</span>
                        @else
                            <form method="POST" action="{{ route('groups.join', $group) }}">
                                @csrf
                                <button class="px-3 py-2 bg-fb-50 text-fb-700 text-sm rounded-md hover:bg-fb-100">Join</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-gray-500">No groups yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
