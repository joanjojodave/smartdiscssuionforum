<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $group->name }} - Group Chat</h2>
    </x-slot>

    <div class="py-8"
         x-data="groupChat({{ $group->id }}, {{ auth()->id() }})"
         x-init="init()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-[240px_1fr_280px] gap-4 items-start">

                {{-- Chat list --}}
                <div class="bg-white shadow-sm rounded-lg p-3 space-y-1">
                    <h3 class="px-2 py-1 text-xs font-semibold text-gray-400 uppercase tracking-wide">Chats</h3>
                    @forelse ($myGroups as $g)
                        <a href="{{ route('messages.index', $g) }}"
                           class="flex items-center gap-2 px-2 py-2 rounded-md text-sm {{ $g->id === $group->id ? 'bg-fb-600 text-white' : 'text-gray-700 hover:bg-fb-50' }}">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-semibold shrink-0 {{ $g->id === $group->id ? 'bg-white/20 text-white' : 'bg-fb-100 text-fb-700' }}">
                                {{ strtoupper(substr($g->name, 0, 1)) }}
                            </span>
                            <span class="truncate">{{ $g->name }}</span>
                        </a>
                    @empty
                        <p class="px-2 py-1 text-sm text-gray-400">No other active groups yet.</p>
                    @endforelse
                </div>

                {{-- Conversation --}}
                <div class="bg-white shadow-sm rounded-lg flex flex-col h-[32rem]">
                    <div class="flex-1 overflow-y-auto p-4 space-y-3" x-ref="scrollArea">
                        <template x-for="m in messages" :key="m.id">
                            <div class="flex items-end gap-2" :class="m.sender_id === userId ? 'justify-end' : 'justify-start'">
                                <span x-show="m.sender_id !== userId"
                                      class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-fb-100 text-fb-700 text-xs font-semibold shrink-0"
                                      x-text="m.sender.charAt(0).toUpperCase()"></span>
                                <div class="max-w-xs px-3 py-2 rounded-2xl text-sm"
                                     :class="m.sender_id === userId ? 'bg-fb-600 text-white rounded-br-sm' : 'bg-gray-100 text-gray-800 rounded-bl-sm'">
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
                            <input type="text" x-model="body" placeholder="Say something..." class="flex-1 rounded-full border-gray-300 text-sm" />
                            <button type="button" @click="showExclude = !showExclude" class="px-3 py-2 text-xs border rounded-full hover:bg-gray-50">Exclude</button>
                            <button type="submit" class="w-10 h-10 flex items-center justify-center bg-fb-600 text-white rounded-full hover:bg-fb-700 shrink-0">
                                <x-icon name="message" class="w-4 h-4" />
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Notifications + suggestions rail --}}
                <div class="space-y-4">
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-1.5"><x-icon name="bell" class="w-4 h-4 text-fb-600" /> Notifications</h3>
                        <ul class="divide-y">
                            @forelse ($notifications as $n)
                                <li class="py-2 text-sm {{ $n->read_at ? 'text-gray-400' : 'text-gray-700 font-medium' }}">
                                    {{ $n->data['message'] ?? 'Notification' }}
                                    <div class="text-xs text-gray-400 font-normal">{{ $n->created_at->diffForHumans() }}</div>
                                </li>
                            @empty
                                <li class="py-2 text-sm text-gray-400">Nothing new.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-3">Suggested groups</h3>
                        <ul class="space-y-3">
                            @forelse ($suggestedGroups as $s)
                                <li class="flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-gray-700 truncate">{{ $s->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $s->members_count }} member(s)</div>
                                    </div>
                                    <form method="POST" action="{{ route('groups.join', $s) }}">
                                        @csrf
                                        <button class="px-3 py-1 bg-fb-50 text-fb-700 text-xs font-medium rounded-full hover:bg-fb-100 shrink-0">Join</button>
                                    </form>
                                </li>
                            @empty
                                <li class="text-sm text-gray-400">You've joined every group so far.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
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
