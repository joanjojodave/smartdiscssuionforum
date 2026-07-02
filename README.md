# Smart Discussion Forum

A managed discussion and learning platform for academic groups, delivered through **two clients sharing one Laravel backend**, per the assignment brief: a Laravel/Breeze/Alpine.js web app, and a Java Swing desktop app with offline support. Implements every functional requirement from the recess assignment brief and the accompanying design document.

## Repository layout

- **`/` (this Laravel app)** — the backend (MySQL/SQLite, REST + session auth) and the web client (Blade + Alpine.js + Reverb for real-time chat).
- **`/desktop-client`** — the Java Swing desktop client (Maven project), talking to the same backend over the JSON API in `routes/api.php`, with a local SQLite cache for offline browsing and an outbox queue for offline-composed posts/messages. See `desktop-client/README.md`.

Topic classification, content moderation/relevance scoring, and topic recommendation are implemented as **local, rule-based services** (`App\Services\TopicClassifierService`, `ModerationService`, `RecommendationService`) that fulfil the same request/response contract the SDD assigns to a separate Python/FastAPI + scikit-learn ML microservice (`POST /classify`, `/moderate`, `/recommend`). They work standalone with zero extra infrastructure; swapping in a real HTTP call to a trained model service later doesn't require touching any caller.

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
| 8 | Realtime web chat, offline desktop sync | Laravel Reverb + Echo for realtime web chat. The desktop client (`/desktop-client`) does offline-first chat/discussions via a local SQLite cache and an outbox queue, syncing through `/api/sync/pull` and `/api/sync/push` |
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
- `laravel/sanctum` — token auth for the JSON API (`routes/api.php`), consumed by the desktop client
- `laravel/reverb` + `laravel-echo`/`pusher-js` — real-time WebSocket broadcasting for chat, posts, and quiz timing events
- `barryvdh/laravel-dompdf` — topic-thread PDF export
- `alpinejs` — chat UI, quiz lockdown countdown, dynamic quiz-question builder

## JSON API (for the desktop client)

`routes/api.php` exposes the same functionality as the web app over a token-authenticated REST API:

- `POST /api/login` → `{ token, user }`, then send `Authorization: Bearer <token>` on every subsequent request.
- Groups: `GET /api/groups`, `POST /api/groups/{id}/join`, `POST /api/groups/{id}/rules`, `GET /api/groups/{id}/topics`, `GET /api/groups/{id}/members`.
- Discussions: `POST /api/groups/{id}/topics`, `GET /api/topics/{id}`, `POST /api/topics/{id}/posts`, `POST /api/posts/{id}/mark-answer`.
- Chat: `GET|POST /api/groups/{id}/messages`.
- Quizzes: `GET /api/quizzes`, `GET /api/quizzes/{id}`, `POST /api/quizzes/{id}/start`, `POST /api/quizzes/{id}/submit`, `GET /api/quizzes/{id}/report`.
- Sync: `GET /api/sync/pull?since=<ISO8601>` (everything changed for the caller's groups), `POST /api/sync/push` (replay a batch of offline-queued posts/messages, matched back by `client_ref`).

## Desktop client

See [`desktop-client/README.md`](desktop-client/README.md) for the Java Swing app: build/run instructions, offline-sync design, and what's in scope for v1.

## Notes on design decisions

- **Roles**: `admin`, `lecturer`, `member` on the `users` table, enforced via the `role` middleware and `EnsureActiveGroupMember` (blocks pending/declined/blacklisted members from posting).
- **Quiz timing is server-authoritative**: a quiz's deadline is `start_at + duration_minutes`, independent of when an individual member starts their attempt, matching "late joiners get no extra time." The scheduled command `app:auto-submit-expired-quizzes` guarantees attempts get graded even if nobody has the tab open when time runs out.
- **Notifications** use Laravel's built-in database + mail notification channels (mail driver defaults to `log` in `.env` — point it at a real SMTP provider for actual delivery).
- **"Accepted answer" only ever marks the reply**, not the question post it answers — `Topic::hasUnansweredQuestions()` / `Post::isUnanswered()` determine resolution by checking whether a question has any reply flagged `is_answer`, not by flipping a flag on the question itself.
