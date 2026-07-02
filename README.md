# Smart Discussion Forum (Laravel web app)

A managed discussion and learning platform for academic groups, built with Laravel 13, Breeze auth, Alpine.js, and Reverb (WebSocket) for real-time chat. Implements the functional requirements from the recess assignment brief and the accompanying design document.

## Scope of this repository

This repo contains the **Laravel web application** only, which per the design document is one of the platform's clients (the other being a Java desktop GUI) sitting in front of a shared backend. What's implemented here:

- Full web backend + REST-style Blade UI covering every functional requirement in the assignment brief (see mapping below).
- Topic classification, content moderation/relevance scoring, and topic recommendation are implemented as **local, rule-based services** (`App\Services\TopicClassifierService`, `ModerationService`, `RecommendationService`) that fulfil the same request/response contract the SDD assigns to the separate Python/FastAPI + scikit-learn ML microservice (`POST /classify`, `/moderate`, `/recommend`). They work standalone with zero extra infrastructure; swapping in a real HTTP call to a trained model service later doesn't require touching any caller.
- The Java desktop client and the standalone Python ML service described in the design document are **not** part of this repository — they're separate deliverables noted here so nothing is assumed to exist that doesn't.

## Requirement → implementation map

| # | Requirement | Where |
|---|---|---|
| 1 | Flooding / irrelevant content filtering | `ModerationService`, flags shown on posts, admin moderation queue |
| 2 | Unanswered question visibility | `Topic::hasUnansweredQuestions()`, `Post::isUnanswered()`, red badges in UI |
| 3 | Exclude members from a message | `MessageExclusion` model, checkbox picker in group chat |
| 4 | 2 warnings then timed blacklist | `InactivityMonitorService` + `app:check-inactive-members` scheduled command; admins can also restrict directly |
| 5 | Onboarding: accept rules to join | `GroupController@join/showRules/agreeRules`, `Membership.status` lifecycle |
| 6 | Topic view + PDF export | `TopicController@show/export` (barryvdh/laravel-dompdf) |
| 7 | Per-group admin statistics | `Admin\AdminController@dashboard/groupStats` |
| 8 | Realtime web chat, offline desktop sync | Laravel Reverb + Echo for realtime web chat. Offline sync is a **Java desktop client concern** (not in this repo); `messages.sync_status` and the sync API shape are already modeled in the schema for that client to use |
| 9 | Participation marks | `ParticipationGradingService`, `lecturer/participation` page |
| 10 | Quiz scheduling, server-authoritative lockdown timer, auto-submit, report | `QuizController`, `Lecturer\QuizManageController`, `app:auto-submit-expired-quizzes` scheduled command |
| 11 | ML topic classification + recommendation | `TopicClassifierService`, `RecommendationService` (see note above) |
| 12 | Forward post to social media | `ShareController` (share-intent links, no API keys required) |

## Requirements

- PHP 8.3+, Composer
- Node.js 20+ / npm
- SQLite (bundled, zero-config) — swap to MySQL/PostgreSQL in `.env` for production, matching the design document

## Setup

```bash
composer install
npm install

cp .env.example .env   # already done if you cloned this repo as-is
php artisan key:generate

php artisan migrate --seed
npm run build           # or `npm run dev` while developing

php artisan serve       # pick a free port, e.g. php artisan serve --port=8010
```

For real-time chat, also run the Reverb WebSocket server alongside `serve`:

```bash
php artisan reverb:start
```

Scheduled jobs (quiz auto-submit runs every minute, inactivity check runs daily — see `routes/console.php`) need the scheduler running in production:

```bash
php artisan schedule:work   # dev convenience; use a real cron entry in production
```

## Demo accounts (seeded, password: `password` for all)

| Role | Email |
|---|---|
| Admin | `admin@sdf.test` |
| Lecturer | `lecturer@sdf.test` |
| Member (student) | `student1@sdf.test` … `student7@sdf.test` |

The seeder creates two groups, several discussion topics (including one unanswered question and one flagged/irrelevant post), a closed quiz with graded attempts, an upcoming scheduled quiz, and sample group-chat messages (including one with a per-message exclusion).

## Key packages

- `laravel/breeze` — authentication scaffolding (Blade stack)
- `laravel/reverb` + `laravel-echo`/`pusher-js` — real-time WebSocket broadcasting for chat, posts, and quiz timing events
- `barryvdh/laravel-dompdf` — topic-thread PDF export
- `alpinejs` — chat UI, quiz lockdown countdown, dynamic quiz-question builder

## Notes on design decisions

- **Roles**: `admin`, `lecturer`, `member` on the `users` table, enforced via the `role` middleware and `EnsureActiveGroupMember` (blocks pending/declined/blacklisted members from posting).
- **Quiz timing is server-authoritative**: a quiz's deadline is `start_at + duration_minutes`, independent of when an individual member starts their attempt, matching "late joiners get no extra time." The scheduled command `app:auto-submit-expired-quizzes` guarantees attempts get graded even if nobody has the tab open when time runs out.
- **Notifications** use Laravel's built-in database + mail notification channels (mail driver defaults to `log` in `.env` — point it at a real SMTP provider for actual delivery).
