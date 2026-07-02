<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Group</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('groups.store') }}" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                <div>
                    <x-input-label for="name" value="Group name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" required value="{{ old('name') }}" />
                </div>
                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300">{{ old('description') }}</textarea>
                </div>
                <div>
                    <x-input-label for="rules" value="Group rules (shown to new members during onboarding)" />
                    <textarea id="rules" name="rules" rows="5" class="mt-1 block w-full rounded-md border-gray-300" required>{{ old('rules') }}</textarea>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="inactivity_warning_days" value="Warning after (days)" />
                        <x-text-input type="number" id="inactivity_warning_days" name="inactivity_warning_days" class="mt-1 block w-full" value="{{ old('inactivity_warning_days', 7) }}" required />
                    </div>
                    <div>
                        <x-input-label for="inactivity_blacklist_days" value="Re-check interval (days)" />
                        <x-text-input type="number" id="inactivity_blacklist_days" name="inactivity_blacklist_days" class="mt-1 block w-full" value="{{ old('inactivity_blacklist_days', 14) }}" required />
                    </div>
                    <div>
                        <x-input-label for="blacklist_duration_days" value="Blacklist duration (days)" />
                        <x-text-input type="number" id="blacklist_duration_days" name="blacklist_duration_days" class="mt-1 block w-full" value="{{ old('blacklist_duration_days', 7) }}" required />
                    </div>
                </div>
                <x-primary-button>Create group</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
