<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Group Participation Grading</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm rounded-lg p-4 flex items-center justify-between gap-4">
                <form method="GET" class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Group:</label>
                    <select name="group_id" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm">
                        @foreach ($groups as $g)
                            <option value="{{ $g->id }}" {{ $group && $group->id === $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </form>

                @if ($group)
                    <form method="POST" action="{{ route('lecturer.participation.recompute') }}">
                        @csrf
                        <input type="hidden" name="group_id" value="{{ $group->id }}">
                        <button class="px-3 py-2 bg-fb-600 text-white text-sm rounded-md hover:bg-fb-700">Recompute marks</button>
                    </form>
                @endif
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-2">Student</th>
                            <th class="px-4 py-2">Posts</th>
                            <th class="px-4 py-2">Replies</th>
                            <th class="px-4 py-2">Participation marks</th>
                            <th class="px-4 py-2">Grade</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($marks as $row)
                            <tr>
                                <td class="px-4 py-2">{{ $row['user']->name }}</td>
                                <td class="px-4 py-2">{{ $row['posts'] }}</td>
                                <td class="px-4 py-2">{{ $row['replies'] }}</td>
                                <td class="px-4 py-2">{{ $row['score'] }}</td>
                                <td class="px-4 py-2 font-medium">{{ $row['grade'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-4 text-center text-gray-400">No members in this group yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
