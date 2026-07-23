<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:flex-row">

            <!-- Branding panel -->
            <div class="hidden sm:flex sm:w-2/5 lg:w-1/3 bg-fb-600 text-white flex-col justify-between p-10">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <x-application-logo class="h-9 w-auto fill-current text-white" />
                    <span class="font-semibold text-lg">Smart Discussion Forum</span>
                </a>

                <div>
                    <p class="text-2xl font-semibold leading-snug text-white">
                        Discussions that stay on-topic.<br>Quizzes that time themselves.
                    </p>
                    <p class="mt-4 text-fb-100 text-sm leading-relaxed max-w-sm">
                        Moderated discussion, participation grading and lecturer-scheduled quizzes for your class or research group — on the web and offline on desktop.
                    </p>
                </div>

                <p class="text-xs text-fb-200">&copy; {{ date('Y') }} Smart Discussion Forum &middot; Group 28, Makerere University CoCIS</p>
            </div>

            <!-- Form panel -->
            <div class="flex-1 flex flex-col justify-center items-center px-6 py-10 bg-gray-50">
                <div class="sm:hidden mb-8">
                    <a href="{{ route('home') }}" class="flex items-center gap-2">
                        <x-application-logo class="h-9 w-auto fill-current text-fb-600" />
                        <span class="font-semibold text-gray-800">Smart Discussion Forum</span>
                    </a>
                </div>

                <div class="w-full sm:max-w-md px-6 py-8 bg-white shadow-sm border border-gray-100 overflow-hidden rounded-xl">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-xs text-gray-400">Need an account? <a href="{{ route('register') }}" class="text-fb-600 hover:underline">Sign up</a> &middot; <a href="{{ route('login') }}" class="text-fb-600 hover:underline">Log in</a></p>
            </div>
        </div>
    </body>
</html>
