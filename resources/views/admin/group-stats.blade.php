<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $group->name }} - Statistics</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                @foreach ($stats as $label => $value)
                    <div class="bg-white shadow-sm rounded-lg p-4 text-center">
                        <div class="text-2xl font-semibold text-indigo-700">{{ $value }}</div>
                        <div class="text-xs text-gray-500 uppercase">{{ str_replace('_', ' ', $label) }}</div>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('admin.groups.flags', $group) }}" class="inline-block mt-6 text-sm text-indigo-700 underline">View moderation flags for this group →</a>
        </div>
    </div>
</x-app-layout>
