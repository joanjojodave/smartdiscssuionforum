<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $quiz->title }} - In Progress</h2>
    </x-slot>

    <div class="py-8" x-data="quizLockdown({{ max($secondsRemaining, 0) }})" x-init="start()" @beforeunload.window="warnLeave">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="sticky top-0 z-10 bg-indigo-600 text-white rounded-lg px-4 py-3 mb-4 flex items-center justify-between shadow">
                <span class="text-sm font-medium">🔒 Lockdown mode - stay on this page until you submit</span>
                <span class="font-mono text-lg" x-text="formatted"></span>
            </div>

            <form method="POST" action="{{ route('quizzes.submit', $quiz) }}" x-ref="quizForm" class="space-y-4">
                @csrf
                @foreach ($quiz->questions as $i => $question)
                    <div class="bg-white shadow-sm rounded-lg p-5">
                        <p class="font-medium text-gray-800 mb-3">{{ $i + 1 }}. {{ $question->text }} <span class="text-xs text-gray-400">({{ $question->marks }} mark(s))</span></p>
                        <div class="space-y-2">
                            @foreach ($question->options as $key => $option)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" name="answers[{{ $question->id }}]" value="{{ $key }}">
                                    <span><strong>{{ $key }}.</strong> {{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <x-primary-button type="submit">Submit quiz</x-primary-button>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function quizLockdown(secondsRemaining) {
            return {
                remaining: secondsRemaining,
                formatted: '',
                timer: null,
                start() {
                    this.tick();
                    this.timer = setInterval(() => this.tick(), 1000);
                },
                tick() {
                    if (this.remaining <= 0) {
                        clearInterval(this.timer);
                        this.formatted = '00:00';
                        this.$refs.quizForm.submit();
                        return;
                    }
                    const m = Math.floor(this.remaining / 60).toString().padStart(2, '0');
                    const s = (this.remaining % 60).toString().padStart(2, '0');
                    this.formatted = m + ':' + s;
                    this.remaining--;
                },
                warnLeave(e) {
                    e.preventDefault();
                    e.returnValue = '';
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
