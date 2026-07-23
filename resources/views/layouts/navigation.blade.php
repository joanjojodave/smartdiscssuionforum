<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <x-application-logo class="block h-9 w-auto fill-current text-fb-600" />
                        <span class="font-semibold text-gray-800 hidden md:inline">Smart Discussion Forum</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('groups.index')" :active="request()->routeIs('groups.*')">
                        {{ __('Groups') }}
                    </x-nav-link>
                    <x-nav-link :href="route('quizzes.index')" :active="request()->routeIs('quizzes.*')">
                        {{ __('Quizzes') }}
                    </x-nav-link>
                    @if (Auth::user()->isLecturer())
                        <x-nav-link :href="route('lecturer.quizzes.create')" :active="request()->routeIs('lecturer.quizzes.*')">
                            {{ __('Schedule Quiz') }}
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->isLecturer() || Auth::user()->isAdmin())
                        <x-nav-link :href="route('lecturer.participation')" :active="request()->routeIs('lecturer.participation')">
                            {{ __('Participation') }}
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            {{ __('Admin') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.members')" :active="request()->routeIs('admin.members')">
                            {{ __('Members') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-2">
                <!-- Notifications -->
                <x-dropdown align="right" width="80">
                    <x-slot name="trigger">
                        <button class="relative inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <span>🔔</span>
                            @if (Auth::user()->unreadNotifications()->count() > 0)
                                <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                    {{ Auth::user()->unreadNotifications()->count() }}
                                </span>
                            @endif
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="max-h-96 overflow-y-auto">
                            @forelse (Auth::user()->notifications()->limit(10)->get() as $notification)
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm {{ $notification->read_at ? 'text-gray-400' : 'text-gray-800 font-medium bg-fb-50' }} hover:bg-gray-100">
                                        {{ $notification->data['message'] ?? 'Notification' }}
                                        <div class="text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</div>
                                    </button>
                                </form>
                            @empty
                                <div class="px-4 py-2 text-sm text-gray-500">No notifications yet.</div>
                            @endforelse
                        </div>
                    </x-slot>
                </x-dropdown>

                <!-- Settings Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }} <span class="text-xs text-gray-400">({{ ucfirst(Auth::user()->role) }})</span></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('groups.index')" :active="request()->routeIs('groups.*')">
                {{ __('Groups') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('quizzes.index')" :active="request()->routeIs('quizzes.*')">
                {{ __('Quizzes') }}
            </x-responsive-nav-link>
            @if (Auth::user()->isLecturer())
                <x-responsive-nav-link :href="route('lecturer.quizzes.create')" :active="request()->routeIs('lecturer.quizzes.*')">
                    {{ __('Schedule Quiz') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->isLecturer() || Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('lecturer.participation')" :active="request()->routeIs('lecturer.participation')">
                    {{ __('Participation') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                    {{ __('Admin') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.members')" :active="request()->routeIs('admin.members')">
                    {{ __('Members') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
