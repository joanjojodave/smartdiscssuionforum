# Smart Discussion Forum — Desktop Client

Java Swing desktop client for the Smart Discussion Forum, per the assignment's "Java GUI for the desktop application" requirement. Talks to the Laravel backend's JSON API (`../routes/api.php`) and works offline against a local SQLite cache when there's no connection.

## Requirements

- JDK 17+
- Maven 3.9+
- The Laravel backend running and reachable (see the main `README.md`)

## Build & run

```bash
mvn package
java -jar target/smart-discussion-forum-desktop.jar
```

By default it points at `http://127.0.0.1:8010/api`. Override with:

```bash
java -Dsdf.apiBaseUrl=http://your-server:8000/api -jar target/smart-discussion-forum-desktop.jar
```

(The server URL field on the login screen also overrides it at runtime.)

Demo accounts: same as the web app — e.g. `student1@sdf.test` / `password`.

## What it does

- **Login** — Sanctum token auth (`POST /api/login`), token + last session cached locally so **"Continue offline"** can reopen the app without a network connection, showing whatever was last synced.
- **Groups & Discussions** — browse groups, request to join, review and accept/decline group rules (onboarding), browse topics (unanswered-question / resolved badges), read threaded replies, reply or mark an accepted answer, start a new topic.
- **Group Chat** — per-group message list with a member-exclusion picker for the next message sent (requirement #3).
- **Quizzes** — list with status; opening an *open* quiz starts the attempt and shows a lockdown countdown identical in spirit to the web app's — the countdown value comes from the server (`start_at + duration_minutes`, independent of when this client started the attempt) and the server re-validates on submit, so a wrong or tampered system clock on the desktop can't grant extra time. A *closed* quiz shows the performance report.
- **Offline sync** — a background timer (every 15s, plus "Sync now") pushes anything queued while offline (`outbox` table) and pulls everything changed since the last sync (`/api/sync/pull`). Replies and chat messages composed offline get a temporary negative local id and show "(sending...)" until the next successful sync resolves them to the server's real row.

## What's intentionally out of scope for v1

- **Starting a new topic requires being online** — unlike replies and chat messages, it isn't queued in the outbox, since topic creation also triggers server-side topic classification and there's nothing meaningful to render locally before that round-trip completes.
- **No live push** — the desktop client polls on a timer rather than holding a WebSocket connection open (the web app uses Reverb for that); acceptable per the design document, which only requires the desktop client to support "offline access" and "sync when online," not live push.
- **No PDF export UI** — already covered by the web client per the assignment ("exported to PDF by anyone"); could be added by hitting `GET /topics/{id}/export` and saving the response.

## Project layout

```
src/main/java/com/sdf/desktop/
  Main.java                 entry point
  Config.java                API base URL, local cache file location
  api/ApiClient.java          HTTP client (java.net.http) + Sanctum bearer token
  store/LocalStore.java       SQLite cache + outbox queue (JDBC)
  sync/SyncService.java       push outbox, then pull changes
  ui/                         Swing screens (login, main window, groups, topics, chat, quizzes)
```
