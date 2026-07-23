<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Discuss, learn and assess in one place</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased text-gray-900 bg-white">

    <header class="border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-application-logo class="h-8 w-auto fill-current text-fb-600" />
                <span class="font-semibold text-gray-800">Smart Discussion Forum</span>
            </div>
            <nav class="flex items-center gap-3 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-md bg-fb-600 text-white font-medium hover:bg-fb-700">Go to dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="px-3 py-2 text-gray-600 hover:text-gray-900">Log in</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-md bg-fb-600 text-white font-medium hover:bg-fb-700">Get started</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        <!-- Hero -->
        <section class="max-w-6xl mx-auto px-6 pt-16 pb-14 grid md:grid-cols-2 gap-12 items-center">
            <div>
                <span class="inline-block px-3 py-1 rounded-full bg-fb-50 text-fb-700 text-xs font-semibold tracking-wide uppercase mb-5">For academic groups &amp; classes</span>
                <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 leading-tight">
                    Discussions that stay on-topic. Quizzes that time themselves.
                </h1>
                <p class="mt-5 text-lg text-gray-600 leading-relaxed">
                    Smart Discussion Forum keeps a class or research group's conversation focused — filtering noise, surfacing unanswered questions, tracking participation, and running lecturer-scheduled quizzes with a server-enforced countdown. Available on the web and as an offline-capable desktop app.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-5 py-3 rounded-md bg-fb-600 text-white font-medium hover:bg-fb-700">Go to your dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="px-5 py-3 rounded-md bg-fb-600 text-white font-medium hover:bg-fb-700">Create an account</a>
                        <a href="{{ route('login') }}" class="px-5 py-3 rounded-md border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">Log in</a>
                    @endauth
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-6 space-y-4">
                <div class="bg-white rounded-lg shadow-sm p-4 flex items-start gap-3">
                    <span class="text-xl">💬</span>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Kayengere Brenda <span class="font-normal text-gray-400">· accepted answer</span></p>
                        <p class="text-sm text-gray-600 mt-0.5">Naive Bayes is a strong baseline on small, short-text datasets — start there before an SVM.</p>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Week 3 Recap Quiz</p>
                        <p class="text-xs text-gray-400">Software Engineering 2026 · 20 min</p>
                    </div>
                    <span class="px-2.5 py-1 text-xs rounded-full bg-green-100 text-green-700 font-medium">closed</span>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
                    <p class="text-sm text-gray-600">Participation this month</p>
                    <p class="text-sm font-semibold text-fb-700">82% · Grade B+</p>
                </div>
            </div>
        </section>

        <!-- Feature grid -->
        <section class="bg-gray-50 border-y border-gray-100">
            <div class="max-w-6xl mx-auto px-6 py-16">
                <h2 class="text-2xl font-bold text-gray-900 text-center">Everything a managed discussion group needs</h2>
                <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white rounded-xl p-5 border border-gray-100">
                        <div class="w-9 h-9 rounded-lg bg-fb-50 text-fb-600 flex items-center justify-center mb-3">🎯</div>
                        <h3 class="font-semibold text-gray-800">Focused discussions</h3>
                        <p class="text-sm text-gray-500 mt-1.5">Flooding and off-topic posts are flagged automatically; unanswered questions stay visible until resolved.</p>
                    </div>
                    <div class="bg-white rounded-xl p-5 border border-gray-100">
                        <div class="w-9 h-9 rounded-lg bg-fb-50 text-fb-600 flex items-center justify-center mb-3">⏱️</div>
                        <h3 class="font-semibold text-gray-800">Timed, lockdown quizzes</h3>
                        <p class="text-sm text-gray-500 mt-1.5">Lecturers schedule a quiz once; the server owns the countdown, auto-submits and grades on time.</p>
                    </div>
                    <div class="bg-white rounded-xl p-5 border border-gray-100">
                        <div class="w-9 h-9 rounded-lg bg-fb-50 text-fb-600 flex items-center justify-center mb-3">🏷️</div>
                        <h3 class="font-semibold text-gray-800">Auto-classified topics</h3>
                        <p class="text-sm text-gray-500 mt-1.5">New topics are labelled automatically, and recommended back to members based on what they've engaged with.</p>
                    </div>
                    <div class="bg-white rounded-xl p-5 border border-gray-100">
                        <div class="w-9 h-9 rounded-lg bg-fb-50 text-fb-600 flex items-center justify-center mb-3">💻</div>
                        <h3 class="font-semibold text-gray-800">Works offline, too</h3>
                        <p class="text-sm text-gray-500 mt-1.5">The desktop client caches your groups locally and syncs queued replies the moment you're back online.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Roles -->
        <section class="max-w-6xl mx-auto px-6 py-16">
            <h2 class="text-2xl font-bold text-gray-900 text-center">Built around three roles</h2>
            <div class="mt-10 grid md:grid-cols-3 gap-6">
                <div class="border border-gray-100 rounded-xl p-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-fb-600">Student</p>
                    <p class="mt-2 text-sm text-gray-600">Join groups, ask and answer questions, chat in real time, sit quizzes, and track your participation grade.</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-fb-600">Lecturer</p>
                    <p class="mt-2 text-sm text-gray-600">Schedule quizzes with a start time and duration, review results the moment they close, and grade participation.</p>
                </div>
                <div class="border border-gray-100 rounded-xl p-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-fb-600">Admin</p>
                    <p class="mt-2 text-sm text-gray-600">See per-group statistics, moderate flagged content, and manage member warnings and access.</p>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="bg-fb-600">
            <div class="max-w-4xl mx-auto px-6 py-14 text-center">
                <h2 class="text-2xl sm:text-3xl font-bold text-white">Ready to bring order to your group's discussions?</h2>
                <p class="mt-3 text-fb-100">Free to join — an admin will confirm your group membership.</p>
                <div class="mt-7">
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-6 py-3 rounded-md bg-white text-fb-700 font-semibold hover:bg-fb-50">Go to your dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="px-6 py-3 rounded-md bg-white text-fb-700 font-semibold hover:bg-fb-50">Create your account</a>
                    @endauth
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-gray-100">
        <div class="max-w-6xl mx-auto px-6 py-8 text-sm text-gray-400 flex flex-col sm:flex-row justify-between gap-2">
            <span>&copy; {{ date('Y') }} Smart Discussion Forum — Group 28, Makerere University CoCIS.</span>
            <span>Built with Laravel &amp; a Java desktop client.</span>
        </div>
    </footer>
</body>
</html>
