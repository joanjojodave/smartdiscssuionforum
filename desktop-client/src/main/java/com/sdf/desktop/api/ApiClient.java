package com.sdf.desktop.api;

import com.sdf.desktop.Config;
import org.json.JSONObject;

import java.io.IOException;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;

/**
 * Thin REST client for the Laravel API (routes/api.php), authenticated with
 * a Sanctum bearer token obtained via {@link #login}. Every call can throw
 * {@link OfflineException} (no connectivity at all, distinct from a real
 * HTTP error) so callers can decide to fall back to the local cache instead
 * of surfacing a hard failure -- this is what makes the offline-first
 * workflow possible.
 */
public class ApiClient {

    private final HttpClient http = HttpClient.newBuilder()
            .connectTimeout(Duration.ofSeconds(5))
            .build();

    private String token;

    public void setToken(String token) {
        this.token = token;
    }

    public boolean isAuthenticated() {
        return token != null;
    }

    public JSONObject login(String email, String password) throws ApiException, OfflineException {
        JSONObject body = new JSONObject();
        body.put("email", email);
        body.put("password", password);
        body.put("device_name", "desktop-" + System.getProperty("user.name", "user"));

        JSONObject response = request("POST", "/login", body, false);
        this.token = response.getString("token");
        return response;
    }

    public void logout() {
        try {
            request("POST", "/logout", null, true);
        } catch (Exception ignored) {
            // best-effort; local session is cleared regardless by the caller
        }
        this.token = null;
    }

    public JSONObject get(String path) throws ApiException, OfflineException {
        return request("GET", path, null, true);
    }

    public JSONObject post(String path, JSONObject body) throws ApiException, OfflineException {
        return request("POST", path, body, true);
    }

    public JSONObject patch(String path, JSONObject body) throws ApiException, OfflineException {
        return request("PATCH", path, body, true);
    }

    private JSONObject request(String method, String path, JSONObject body, boolean authenticated)
            throws ApiException, OfflineException {
        try {
            HttpRequest.Builder builder = HttpRequest.newBuilder()
                    .uri(URI.create(Config.apiBaseUrl + path))
                    .header("Accept", "application/json")
                    .header("Content-Type", "application/json")
                    .timeout(Duration.ofSeconds(15));

            if (authenticated) {
                if (token == null) {
                    throw new ApiException(401, "Not logged in");
                }
                builder.header("Authorization", "Bearer " + token);
            }

            String json = body != null ? body.toString() : "{}";

            builder.method(method, HttpRequest.BodyPublishers.ofString(json));

            HttpResponse<String> response = http.send(builder.build(), HttpResponse.BodyHandlers.ofString());

            String responseBody = response.body() == null || response.body().isBlank() ? "{}" : response.body();
            JSONObject json1 = new JSONObject(responseBody);

            if (response.statusCode() >= 400) {
                String message = json1.optString("message", "Request failed with status " + response.statusCode());
                throw new ApiException(response.statusCode(), message);
            }

            return json1;
        } catch (IOException | InterruptedException e) {
            throw new OfflineException(e);
        }
    }
}
