package com.sdf.desktop.sync;

import com.sdf.desktop.api.ApiClient;
import com.sdf.desktop.api.ApiException;
import com.sdf.desktop.api.OfflineException;
import com.sdf.desktop.store.LocalStore;
import org.json.JSONArray;
import org.json.JSONObject;

import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.sql.SQLException;
import java.time.Instant;
import java.util.List;

/**
 * Drives the offline-first workflow described in SDD 5.11: push whatever
 * was queued while offline, then pull anything new since the last
 * successful sync. Both steps are safe to call opportunistically -- if the
 * server is unreachable, callers just keep working off the local cache.
 */
public class SyncService {

    private final ApiClient api;
    private final LocalStore store;

    public SyncService(ApiClient api, LocalStore store) {
        this.api = api;
        this.store = store;
    }

    /** @return true if a full sync round-trip succeeded (i.e. we are "online"). */
    public boolean syncNow() {
        try {
            pushOutbox();
            pull();
            return true;
        } catch (OfflineException e) {
            return false;
        } catch (ApiException | SQLException e) {
            System.err.println("Sync error: " + e.getMessage());
            return false;
        }
    }

    private void pushOutbox() throws OfflineException, ApiException, SQLException {
        List<JSONObject> pending = store.getPendingOutbox();
        if (pending.isEmpty()) return;

        JSONObject body = new JSONObject();
        body.put("actions", new JSONArray(pending));

        JSONObject response = api.post("/sync/push", body);
        JSONArray results = response.getJSONArray("results");
        for (int i = 0; i < results.length(); i++) {
            JSONObject r = results.getJSONObject(i);
            if ("created".equals(r.optString("status"))) {
                store.resolveOutboxEntry(r.getString("client_ref"));
            }
        }
    }

    private void pull() throws OfflineException, ApiException, SQLException {
        String since = store.getLastSyncedAt();
        JSONObject response = api.get("/sync/pull?since=" + URLEncoder.encode(since, StandardCharsets.UTF_8));

        store.upsertGroups(response.getJSONArray("groups"));
        store.upsertTopics(response.getJSONArray("topics"));
        store.upsertPosts(response.getJSONArray("posts"));
        store.upsertMessages(response.getJSONArray("messages"));
        store.upsertQuizzes(response.getJSONArray("quizzes"));

        store.setLastSyncedAt(response.optString("synced_at", Instant.now().toString()));
    }
}
