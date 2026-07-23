<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Join {{ $group->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-2">Platform &amp; group rules</h3>
                <div class="prose prose-sm max-w-none text-gray-600 whitespace-pre-line border rounded-md p-4 bg-gray-50 mb-6">{{ $group->rules }}</div>

                @if ($membership->status === 'declined')
                    <p class="text-sm text-red-600 mb-4">You previously declined these rules, so you are not a member of this group.</p>
                @endif

                <form method="POST" action="{{ route('groups.rules.decide', $group) }}" class="flex gap-3">
                    @csrf
                    <button name="decision" value="agree" class="px-4 py-2 bg-fb-600 text-white rounded-md hover:bg-fb-700">I agree - join the group</button>
                    <button name="decision" value="decline" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">I decline</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
