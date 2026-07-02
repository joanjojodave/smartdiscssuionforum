<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Schedule a Quiz</h2>
    </x-slot>

    <div class="py-8" x-data="quizBuilder()">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('lecturer.quizzes.store') }}" @submit="beforeSubmit" class="space-y-6">
                @csrf

                <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                    <div>
                        <x-input-label for="group_id" value="Group" />
                        <select id="group_id" name="group_id" class="mt-1 block w-full rounded-md border-gray-300" required>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="title" value="Quiz title" />
                        <x-text-input id="title" name="title" class="mt-1 block w-full" required />
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="start_at" value="Start date &amp; time" />
                            <input type="datetime-local" id="start_at" name="start_at" class="mt-1 block w-full rounded-md border-gray-300" required>
                        </div>
                        <div>
                            <x-input-label for="duration_minutes" value="Duration (minutes)" />
                            <x-text-input type="number" id="duration_minutes" name="duration_minutes" class="mt-1 block w-full" required value="30" />
                        </div>
                        <div>
                            <x-input-label for="target_category" value="Target category" />
                            <x-text-input id="target_category" name="target_category" class="mt-1 block w-full" placeholder="e.g. all students" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-400">Configurations must be set before the quiz's start time. Once published, students see this as an announcement; the quiz interface opens automatically at the start time and locks down until submission.</p>
                </div>

                <template x-for="(q, qi) in questions" :key="qi">
                    <div class="bg-white shadow-sm rounded-lg p-6 space-y-3">
                        <div class="flex justify-between items-center">
                            <h3 class="font-medium text-gray-700">Question <span x-text="qi + 1"></span></h3>
                            <button type="button" @click="questions.splice(qi, 1)" class="text-xs text-red-500" x-show="questions.length > 1">Remove</button>
                        </div>
                        <textarea :name="`questions[${qi}][text]`" rows="2" class="w-full rounded-md border-gray-300 text-sm" placeholder="Question text" required x-model="q.text"></textarea>

                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="(opt, oi) in q.options" :key="oi">
                                <input type="text" :name="`questions[${qi}][options][${oi}]`" class="rounded-md border-gray-300 text-sm" :placeholder="`Option ${String.fromCharCode(65 + oi)}`" x-model="q.options[oi]" required>
                            </template>
                        </div>

                        <div class="flex gap-4 items-center">
                            <div>
                                <label class="text-xs text-gray-500">Correct option</label>
                                <select :name="`questions[${qi}][correct_option]`" class="block rounded-md border-gray-300 text-sm">
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Marks</label>
                                <input type="number" :name="`questions[${qi}][marks]`" value="1" min="1" class="block w-20 rounded-md border-gray-300 text-sm">
                            </div>
                        </div>
                    </div>
                </template>

                <button type="button" @click="addQuestion" class="px-3 py-2 bg-white border rounded-md text-sm hover:bg-gray-50">+ Add question</button>

                <div>
                    <x-primary-button>Schedule &amp; announce quiz</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function quizBuilder() {
            return {
                questions: [{ text: '', options: ['', '', '', ''] }],
                addQuestion() {
                    this.questions.push({ text: '', options: ['', '', '', ''] });
                },
                beforeSubmit() {},
            };
        }
    </script>
    @endpush
</x-app-layout>
