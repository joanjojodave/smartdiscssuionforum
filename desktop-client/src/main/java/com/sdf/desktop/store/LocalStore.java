package com.sdf.desktop.store;

import com.sdf.desktop.Config;
import org.json.JSONArray;
import org.json.JSONObject;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.time.Instant;
import java.util.ArrayList;
import java.util.List;
import java.util.UUID;

/**
 * Local SQLite cache + outbox queue backing the desktop client's
 * offline-first behaviour (SDD 5.11 Synchronization Component). Whatever
 * was last pulled from the server is readable here even with zero
 * connectivity; anything created while offline is queued in {@code outbox}
 * with a negative placeholder id until {@link com.sdf.desktop.sync.SyncService}
 * successfully replays it.
 */
public class LocalStore {

    private Connection conn;
    private long nextLocalId = -1;

    public void open() throws SQLException {
        Config.APP_DIR.mkdirs();
        conn = DriverManager.getConnection("jdbc:sqlite:" + Config.LOCAL_DB_FILE.getAbsolutePath());
        try (Statement st = conn.createStatement()) {
            st.execute("CREATE TABLE IF NOT EXISTS session (id INTEGER PRIMARY KEY CHECK (id = 1), token TEXT, user_id INTEGER, user_name TEXT, user_email TEXT, user_role TEXT, last_synced_at TEXT)");
            st.execute("CREATE TABLE IF NOT EXISTS groups_cache (id INTEGER PRIMARY KEY, name TEXT, description TEXT, my_status TEXT)");
            st.execute("CREATE TABLE IF NOT EXISTS topics_cache (id INTEGER PRIMARY KEY, group_id INTEGER, title TEXT, category TEXT, is_resolved INTEGER, has_unanswered INTEGER, posts_count INTEGER, author TEXT, created_at TEXT, updated_at TEXT)");
            st.execute("CREATE TABLE IF NOT EXISTS posts_cache (id INTEGER PRIMARY KEY, topic_id INTEGER, parent_post_id INTEGER, author TEXT, author_id INTEGER, body TEXT, is_question INTEGER, is_answer INTEGER, created_at TEXT, updated_at TEXT, pending INTEGER DEFAULT 0)");
            st.execute("CREATE TABLE IF NOT EXISTS messages_cache (id INTEGER PRIMARY KEY, group_id INTEGER, sender_id INTEGER, sender TEXT, body TEXT, sent_at TEXT, pending INTEGER DEFAULT 0)");
            st.execute("CREATE TABLE IF NOT EXISTS quizzes_cache (id INTEGER PRIMARY KEY, group_id INTEGER, title TEXT, start_at TEXT, duration_minutes INTEGER, target_category TEXT, status TEXT)");
            st.execute("CREATE TABLE IF NOT EXISTS outbox (client_ref TEXT PRIMARY KEY, type TEXT, payload TEXT, local_id INTEGER, created_at TEXT)");
        }
    }

    public void close() {
        try {
            if (conn != null) conn.close();
        } catch (SQLException ignored) {}
    }

    // ---------------------------------------------------------------- session

    public void saveSession(String token, JSONObject user) throws SQLException {
        try (PreparedStatement ps = conn.prepareStatement(
                "INSERT INTO session (id, token, user_id, user_name, user_email, user_role, last_synced_at) VALUES (1, ?, ?, ?, ?, ?, ?) " +
                        "ON CONFLICT(id) DO UPDATE SET token = excluded.token, user_id = excluded.user_id, user_name = excluded.user_name, user_email = excluded.user_email, user_role = excluded.user_role")) {
            ps.setString(1, token);
            ps.setInt(2, user.getInt("id"));
            ps.setString(3, user.getString("name"));
            ps.setString(4, user.getString("email"));
            ps.setString(5, user.getString("role"));
            ps.setString(6, "1970-01-01T00:00:00Z");
            ps.executeUpdate();
        }
    }

    public JSONObject loadSession() throws SQLException {
        try (Statement st = conn.createStatement();
             ResultSet rs = st.executeQuery("SELECT * FROM session WHERE id = 1")) {
            if (!rs.next()) return null;
            JSONObject o = new JSONObject();
            o.put("token", rs.getString("token"));
            o.put("user_id", rs.getInt("user_id"));
            o.put("user_name", rs.getString("user_name"));
            o.put("user_email", rs.getString("user_email"));
            o.put("user_role", rs.getString("user_role"));
            o.put("last_synced_at", rs.getString("last_synced_at"));
            return o;
        }
    }

    public void clearSession() throws SQLException {
        try (Statement st = conn.createStatement()) {
            st.execute("DELETE FROM session WHERE id = 1");
        }
    }

    public String getLastSyncedAt() throws SQLException {
        JSONObject s = loadSession();
        return s != null ? s.optString("last_synced_at", "1970-01-01T00:00:00Z") : "1970-01-01T00:00:00Z";
    }

    public void setLastSyncedAt(String timestamp) throws SQLException {
        try (PreparedStatement ps = conn.prepareStatement("UPDATE session SET last_synced_at = ? WHERE id = 1")) {
            ps.setString(1, timestamp);
            ps.executeUpdate();
        }
    }

    // ---------------------------------------------------------------- groups

    public void upsertGroups(JSONArray groups) throws SQLException {
        try (PreparedStatement ps = conn.prepareStatement(
                "INSERT INTO groups_cache (id, name, description, my_status) VALUES (?, ?, ?, ?) " +
                        "ON CONFLICT(id) DO UPDATE SET name = excluded.name, description = excluded.description, my_status = COALESCE(excluded.my_status, groups_cache.my_status)")) {
            for (int i = 0; i < groups.length(); i++) {
                JSONObject g = groups.getJSONObject(i);
                ps.setInt(1, g.getInt("id"));
                ps.setString(2, g.getString("name"));
                ps.setString(3, g.optString("description", ""));
                ps.setString(4, g.optString("my_status", null));
                ps.executeUpdate();
            }
        }
    }

    public void updateMyGroupStatus(int groupId, String status) throws SQLException {
        try (PreparedStatement ps = conn.prepareStatement("UPDATE groups_cache SET my_status = ? WHERE id = ?")) {
            ps.setString(1, status);
            ps.setInt(2, groupId);
            ps.executeUpdate();
        }
    }

    public List<JSONObject> getGroups() throws SQLException {
        List<JSONObject> out = new ArrayList<>();
        try (Statement st = conn.createStatement();
             ResultSet rs = st.executeQuery("SELECT * FROM groups_cache ORDER BY name")) {
            while (rs.next()) {
                JSONObject o = new JSONObject();
                o.put("id", rs.getInt("id"));
                o.put("name", rs.getString("name"));
                o.put("description", rs.getString("description"));
                o.put("my_status", rs.getString("my_status"));
                out.add(o);
            }
        }
        return out;
    }

    // ---------------------------------------------------------------- topics

    public void upsertTopics(JSONArray topics) throws SQLException {
        try (PreparedStatement ps = conn.prepareStatement(
                "INSERT INTO topics_cache (id, group_id, title, category, is_resolved, has_unanswered, posts_count, author, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?) " +
                        "ON CONFLICT(id) DO UPDATE SET title=excluded.title, category=excluded.category, is_resolved=excluded.is_resolved, has_unanswered=excluded.has_unanswered, posts_count=excluded.posts_count, updated_at=excluded.updated_at")) {
            for (int i = 0; i < topics.length(); i++) {
                JSONObject t = topics.getJSONObject(i);
                ps.setInt(1, t.getInt("id"));
                ps.setInt(2, t.getInt("group_id"));
                ps.setString(3, t.getString("title"));
                ps.setString(4, t.optString("category", ""));
                ps.setInt(5, t.optBoolean("is_resolved", false) ? 1 : 0);
                ps.setInt(6, t.optBoolean("has_unanswered", false) ? 1 : 0);
                ps.setInt(7, t.optInt("posts_count", 0));
                ps.setString(8, t.optString("author", ""));
                ps.setString(9, t.optString("created_at", Instant.now().toString()));
                ps.setString(10, t.optString("updated_at", Instant.now().toString()));
                ps.executeUpdate();
            }
        }
    }

    public List<JSONObject> getTopicsForGroup(int groupId) throws SQLException {
        List<JSONObject> out = new ArrayList<>();
        try (PreparedStatement ps = conn.prepareStatement("SELECT * FROM topics_cache WHERE group_id = ? ORDER BY updated_at DESC")) {
            ps.setInt(1, groupId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) out.add(topicRow(rs));
            }
        }
        return out;
    }

    public JSONObject getTopic(int topicId) throws SQLException {
        try (PreparedStatement ps = conn.prepareStatement("SELECT * FROM topics_cache WHERE id = ?")) {
            ps.setInt(1, topicId);
            try (ResultSet rs = ps.executeQuery()) {
                return rs.next() ? topicRow(rs) : null;
            }
        }
    }

    private JSONObject topicRow(ResultSet rs) throws SQLException {
        JSONObject o = new JSONObject();
        o.put("id", rs.getInt("id"));
        o.put("group_id", rs.getInt("group_id"));
        o.put("title", rs.getString("title"));
        o.put("category", rs.getString("category"));
        o.put("is_resolved", rs.getInt("is_resolved") == 1);
        o.put("has_unanswered", rs.getInt("has_unanswered") == 1);
        o.put("posts_count", rs.getInt("posts_count"));
        o.put("author", rs.getString("author"));
        return o;
    }

    // ---------------------------------------------------------------- posts

    public void upsertPosts(JSONArray posts) throws SQLException {
        try (PreparedStatement ps = conn.prepareStatement(
                "INSERT INTO posts_cache (id, topic_id, parent_post_id, author, author_id, body, is_question, is_answer, created_at, updated_at, pending) VALUES (?,?,?,?,?,?,?,?,?,?,0) " +
                        "ON CONFLICT(id) DO UPDATE SET body=excluded.body, is_answer=excluded.is_answer, updated_at=excluded.updated_at, pending=0")) {
            for (int i = 0; i < posts.length(); i++) {
                JSONObject p = posts.getJSONObject(i);
                ps.setInt(1, p.getInt("id"));
                ps.setInt(2, p.getInt("topic_id"));
                if (p.isNull("parent_post_id") || !p.has("parent_post_id")) ps.setNull(3, java.sql.Types.INTEGER);
                else ps.setInt(3, p.getInt("parent_post_id"));
                ps.setString(4, p.optString("author", ""));
                ps.setInt(5, p.optInt("author_id", 0));
                ps.setString(6, p.getString("body"));
                ps.setInt(7, p.optBoolean("is_question", false) ? 1 : 0);
                ps.setInt(8, p.optBoolean("is_answer", false) ? 1 : 0);
                ps.setString(9, p.optString("created_at", Instant.now().toString()));
                ps.setString(10, p.optString("updated_at", Instant.now().toString()));
                ps.executeUpdate();
            }
        }
    }

    public List<JSONObject> getPostsForTopic(int topicId) throws SQLException {
        List<JSONObject> out = new ArrayList<>();
        try (PreparedStatement ps = conn.prepareStatement("SELECT * FROM posts_cache WHERE topic_id = ? ORDER BY created_at")) {
            ps.setInt(1, topicId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    JSONObject o = new JSONObject();
                    o.put("id", rs.getInt("id"));
                    o.put("topic_id", rs.getInt("topic_id"));
                    Object parent = rs.getObject("parent_post_id");
                    o.put("parent_post_id", parent == null ? JSONObject.NULL : parent);
                    o.put("author", rs.getString("author"));
                    o.put("author_id", rs.getInt("author_id"));
                    o.put("body", rs.getString("body"));
                    o.put("is_question", rs.getInt("is_question") == 1);
                    o.put("is_answer", rs.getInt("is_answer") == 1);
                    o.put("pending", rs.getInt("pending") == 1);
                    out.add(o);
                }
            }
        }
        return out;
    }

    /** Inserts a placeholder row (negative id) for a reply composed while offline, and queues it. */
    public long queueOutboxPost(int topicId, Integer parentPostId, String body, boolean isQuestion, JSONObject sessionUser) throws SQLException {
        long localId = nextLocalId--;
        String clientRef = UUID.randomUUID().toString();

        try (PreparedStatement ps = conn.prepareStatement(
                "INSERT INTO posts_cache (id, topic_id, parent_post_id, author, author_id, body, is_question, is_answer, created_at, updated_at, pending) VALUES (?,?,?,?,?,?,?,0,?,?,1)")) {
            ps.setLong(1, localId);
            ps.setInt(2, topicId);
            if (parentPostId == null) ps.setNull(3, java.sql.Types.INTEGER); else ps.setInt(3, parentPostId);
            ps.setString(4, sessionUser.optString("user_name", "You"));
            ps.setInt(5, sessionUser.optInt("user_id", 0));
            ps.setString(6, body);
            ps.setInt(7, isQuestion ? 1 : 0);
            String now = Instant.now().toString();
            ps.setString(8, now);
            ps.setString(9, now);
            ps.executeUpdate();
        }

        JSONObject payload = new JSONObject();
        payload.put("type", "post");
        payload.put("client_ref", clientRef);
        payload.put("topic_id", topicId);
        if (parentPostId != null) payload.put("parent_post_id", parentPostId);
        payload.put("body", body);
        payload.put("is_question", isQuestion);
        enqueue(clientRef, "post", payload, localId);

        return localId;
    }

    // ---------------------------------------------------------------- messages

    public void upsertMessages(JSONArray messages) throws SQLException {
        try (PreparedStatement ps = conn.prepareStatement(
                "INSERT INTO messages_cache (id, group_id, sender_id, sender, body, sent_at, pending) VALUES (?,?,?,?,?,?,0) " +
                        "ON CONFLICT(id) DO UPDATE SET body=excluded.body, pending=0")) {
            for (int i = 0; i < messages.length(); i++) {
                JSONObject m = messages.getJSONObject(i);
                ps.setInt(1, m.getInt("id"));
                ps.setInt(2, m.getInt("group_id"));
                ps.setInt(3, m.optInt("sender_id", 0));
                ps.setString(4, m.optString("sender", ""));
                ps.setString(5, m.getString("body"));
                ps.setString(6, m.optString("sent_at", Instant.now().toString()));
                ps.executeUpdate();
            }
        }
    }

    public List<JSONObject> getMessagesForGroup(int groupId) throws SQLException {
        List<JSONObject> out = new ArrayList<>();
        try (PreparedStatement ps = conn.prepareStatement("SELECT * FROM messages_cache WHERE group_id = ? ORDER BY id")) {
            ps.setInt(1, groupId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    JSONObject o = new JSONObject();
                    o.put("id", rs.getInt("id"));
                    o.put("sender_id", rs.getInt("sender_id"));
                    o.put("sender", rs.getString("sender"));
                    o.put("body", rs.getString("body"));
                    o.put("pending", rs.getInt("pending") == 1);
                    out.add(o);
                }
            }
        }
        return out;
    }

    public long queueOutboxMessage(int groupId, String body, List<Integer> excludeIds, JSONObject sessionUser) throws SQLException {
        long localId = nextLocalId--;
        String clientRef = UUID.randomUUID().toString();

        try (PreparedStatement ps = conn.prepareStatement(
                "INSERT INTO messages_cache (id, group_id, sender_id, sender, body, sent_at, pending) VALUES (?,?,?,?,?,?,1)")) {
            ps.setLong(1, localId);
            ps.setInt(2, groupId);
            ps.setInt(3, sessionUser.optInt("user_id", 0));
            ps.setString(4, sessionUser.optString("user_name", "You"));
            ps.setString(5, body);
            ps.setString(6, Instant.now().toString());
            ps.executeUpdate();
        }

        JSONObject payload = new JSONObject();
        payload.put("type", "message");
        payload.put("client_ref", clientRef);
        payload.put("group_id", groupId);
        payload.put("body", body);
        payload.put("exclude", excludeIds == null ? new JSONArray() : new JSONArray(excludeIds));
        enqueue(clientRef, "message", payload, localId);

        return localId;
    }

    // ---------------------------------------------------------------- quizzes

    public void upsertQuizzes(JSONArray quizzes) throws SQLException {
        try (PreparedStatement ps = conn.prepareStatement(
                "INSERT INTO quizzes_cache (id, group_id, title, start_at, duration_minutes, target_category, status) VALUES (?,?,?,?,?,?,?) " +
                        "ON CONFLICT(id) DO UPDATE SET status=excluded.status")) {
            for (int i = 0; i < quizzes.length(); i++) {
                JSONObject q = quizzes.getJSONObject(i);
                ps.setInt(1, q.getInt("id"));
                ps.setInt(2, q.getInt("group_id"));
                ps.setString(3, q.getString("title"));
                ps.setString(4, q.getString("start_at"));
                ps.setInt(5, q.getInt("duration_minutes"));
                ps.setString(6, q.optString("target_category", ""));
                ps.setString(7, q.optString("status", "scheduled"));
                ps.executeUpdate();
            }
        }
    }

    public List<JSONObject> getQuizzes() throws SQLException {
        List<JSONObject> out = new ArrayList<>();
        try (Statement st = conn.createStatement();
             ResultSet rs = st.executeQuery("SELECT * FROM quizzes_cache ORDER BY start_at DESC")) {
            while (rs.next()) {
                JSONObject o = new JSONObject();
                o.put("id", rs.getInt("id"));
                o.put("group_id", rs.getInt("group_id"));
                o.put("title", rs.getString("title"));
                o.put("start_at", rs.getString("start_at"));
                o.put("duration_minutes", rs.getInt("duration_minutes"));
                o.put("status", rs.getString("status"));
                out.add(o);
            }
        }
        return out;
    }

    // ---------------------------------------------------------------- outbox

    private void enqueue(String clientRef, String type, JSONObject payload, long localId) throws SQLException {
        try (PreparedStatement ps = conn.prepareStatement(
                "INSERT INTO outbox (client_ref, type, payload, local_id, created_at) VALUES (?, ?, ?, ?, ?)")) {
            ps.setString(1, clientRef);
            ps.setString(2, type);
            ps.setString(3, payload.toString());
            ps.setLong(4, localId);
            ps.setString(5, Instant.now().toString());
            ps.executeUpdate();
        }
    }

    public List<JSONObject> getPendingOutbox() throws SQLException {
        List<JSONObject> out = new ArrayList<>();
        try (Statement st = conn.createStatement();
             ResultSet rs = st.executeQuery("SELECT * FROM outbox ORDER BY created_at")) {
            while (rs.next()) {
                out.add(new JSONObject(rs.getString("payload")));
            }
        }
        return out;
    }

    public int getPendingOutboxCount() throws SQLException {
        try (Statement st = conn.createStatement();
             ResultSet rs = st.executeQuery("SELECT COUNT(*) c FROM outbox")) {
            return rs.next() ? rs.getInt("c") : 0;
        }
    }

    /**
     * Called once the sync service has successfully replayed a queued
     * action on the server: removes the outbox entry and the temporary
     * negative-id placeholder row (the next pull will bring down the real
     * server-authored row in its place).
     */
    public void resolveOutboxEntry(String clientRef) throws SQLException {
        Long localId = null;
        try (PreparedStatement sel = conn.prepareStatement("SELECT local_id FROM outbox WHERE client_ref = ?")) {
            sel.setString(1, clientRef);
            try (ResultSet rs = sel.executeQuery()) {
                if (rs.next()) localId = rs.getLong("local_id");
            }
        }

        try (PreparedStatement del = conn.prepareStatement("DELETE FROM outbox WHERE client_ref = ?")) {
            del.setString(1, clientRef);
            del.executeUpdate();
        }

        if (localId != null) {
            try (PreparedStatement delLocal = conn.prepareStatement("DELETE FROM posts_cache WHERE id = ?")) {
                delLocal.setLong(1, localId);
                delLocal.executeUpdate();
            }
            try (PreparedStatement delLocalMsg = conn.prepareStatement("DELETE FROM messages_cache WHERE id = ?")) {
                delLocalMsg.setLong(1, localId);
                delLocalMsg.executeUpdate();
            }
        }
    }
}
