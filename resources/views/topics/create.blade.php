<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Topic in {{ $group->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('topics.store', $group) }}" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                <div>
                    <x-input-label for="title" value="Title" />
                    <x-text-input id="title" name="title" class="mt-1 block w-full" required value="{{ old('title') }}" />
                </div>
                <div>
                    <x-input-label for="category" value="Category (optional - auto-classified if left blank)" />
                    <x-text-input id="category" name="category" class="mt-1 block w-full" value="{{ old('category') }}" />
                </div>
                <div>
                    <x-input-label for="body" value="Message" />
                    <textarea id="body" name="body" rows="6" class="mt-1 block w-full rounded-md border-gray-300" required>{{ old('body') }}</textarea>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_question" value="1">
                    This is a question I need answered
                </label>
                <x-primary-button>Post topic</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
