<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $group->name }} - Group Chat</h2>
    </x-slot>

    <div class="py-8"
         x-data="groupChat({{ $group->id }}, {{ auth()->id() }})"
         x-init="init()">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg flex flex-col h-[32rem]">
                <div class="flex-1 overflow-y-auto p-4 space-y-3" x-ref="scrollArea">
                    <template x-for="m in messages" :key="m.id">
                        <div class="flex" :class="m.sender_id === userId ? 'justify-end' : 'justify-start'">
                            <div class="max-w-xs px-3 py-2 rounded-lg text-sm"
                                 :class="m.sender_id === userId ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800'">
                                <div class="text-xs opacity-70" x-show="m.sender_id !== userId" x-text="m.sender"></div>
                                <div x-text="m.body"></div>
                            </div>
                        </div>
                    </template>
                    <p x-show="messages.length === 0" class="text-center text-gray-400 text-sm">No messages yet. Say hello!</p>
                </div>

                <form @submit.prevent="send" class="border-t p-3">
                    <div x-show="showExclude" class="mb-2 text-xs text-gray-600 border rounded-md p-2 bg-gray-50">
                        <div class="font-medium mb-1">Exclude from this message:</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($members as $member)
                                @if ($member->id !== auth()->id())
                                    <label class="flex items-center gap-1">
                                        <input type="checkbox" value="{{ $member->id }}" x-model="excluded">
                                        {{ $member->name }}
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" x-model="body" placeholder="Type a message..." class="flex-1 rounded-md border-gray-300 text-sm" />
                        <button type="button" @click="showExclude = !showExclude" class="px-3 py-2 text-xs border rounded-md hover:bg-gray-50">Exclude</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function groupChat(groupId, userId) {
            return {
                groupId, userId,
                messages: @json($messagesForJs),
                body: '',
                excluded: [],
                showExclude: false,
                init() {
                    this.scrollToBottom();
                    if (window.Echo) {
                        window.Echo.private('group.' + this.groupId).listen('.message.sent', (e) => {
                            this.messages.push(e);
                            this.$nextTick(() => this.scrollToBottom());
                        });
                    }
                },
                scrollToBottom() {
                    this.$refs.scrollArea.scrollTop = this.$refs.scrollArea.scrollHeight;
                },
                send() {
                    if (! this.body.trim()) return;

                    fetch(@json($storeUrl), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ body: this.body, exclude: this.excluded }),
                    }).then(() => {
                        this.messages.push({ id: Date.now(), sender_id: this.userId, sender: 'You', body: this.body });
                        this.body = '';
                        this.excluded = [];
                        this.showExclude = false;
                        this.$nextTick(() => this.scrollToBottom());
                    });
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
