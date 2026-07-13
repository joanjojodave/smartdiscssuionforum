package com.sdf.desktop.ui;

import org.json.JSONArray;
import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;

/**
 * Role-based landing summary (student participation/grade/recommendations,
 * or lecturer/admin group + quiz overview), backed by GET /api/dashboard.
 */
public class DashboardPanel extends JPanel {

    private final MainFrame main;
    private final JEditorPane content = new JEditorPane();

    public DashboardPanel(MainFrame main) {
        this.main = main;
        setLayout(new BorderLayout(8, 8));

        content.setContentType("text/html");
        content.setEditable(false);

        JPanel top = new JPanel(new BorderLayout());
        top.add(new JLabel("Dashboard"), BorderLayout.WEST);
        JButton refreshButton = new JButton("Refresh");
        refreshButton.addActionListener(e -> load());
        top.add(refreshButton, BorderLayout.EAST);

        add(top, BorderLayout.NORTH);
        add(new JScrollPane(content), BorderLayout.CENTER);
    }

    public void load() {
        if (!main.isOnline()) {
            content.setText(wrap("<p>You need to be online to load the dashboard.</p>"));
            return;
        }
        try {
            JSONObject data = main.api.get("/dashboard");
            content.setText(wrap(render(data)));
            content.setCaretPosition(0);
        } catch (Exception e) {
            content.setText(wrap("<p>Could not load dashboard: " + escape(e.getMessage()) + "</p>"));
        }
    }

    private String render(JSONObject data) {
        String role = data.optString("role", "student");
        StringBuilder sb = new StringBuilder();

        if ("student".equals(role)) {
            sb.append("<h2>Your participation</h2>");
            sb.append("<p style='font-size:20px'><b>").append(data.optInt("score", 0))
                    .append("%</b> &middot; Grade <b>").append(escape(data.optString("grade", "-"))).append("</b></p>");

            sb.append("<h3>Your groups</h3><ul>");
            appendEach(sb, data.optJSONArray("memberships"), m ->
                    "<li>" + escape(m.optString("group_name")) + " &mdash; " + escape(m.optString("status")) + "</li>");
            sb.append("</ul>");

            sb.append("<h3>Quizzes</h3><ul>");
            appendEach(sb, data.optJSONArray("announcements"), q ->
                    "<li>" + escape(q.optString("title")) + " &middot; " + MainFrame.formatInstant(q.optString("start_at", "")) + "</li>");
            sb.append("</ul>");

            sb.append("<h3>Recommended for you</h3><ul>");
            appendEach(sb, data.optJSONArray("recommendations"), t ->
                    "<li>" + escape(t.optString("title")) + " <small>(" + escape(t.optString("category", "")) + ")</small></li>");
            sb.append("</ul>");
        } else {
            String label = "admin".equals(role) ? "Admin" : "Lecturer";
            sb.append("<h2>").append(label).append(" overview</h2>");

            sb.append("<h3>Groups</h3><ul>");
            appendEach(sb, data.optJSONArray("groups"), g ->
                    "<li>" + escape(g.optString("name")) + " &middot; " + g.optInt("members_count", 0) + " member(s)</li>");
            sb.append("</ul>");

            sb.append("<h3>Quizzes</h3><ul>");
            appendEach(sb, data.optJSONArray("quizzes"), q ->
                    "<li>" + escape(q.optString("title")) + " &middot; " + escape(q.optString("status", ""))
                            + " &middot; " + MainFrame.formatInstant(q.optString("start_at", "")) + "</li>");
            sb.append("</ul>");
        }

        return sb.toString();
    }

    private interface Renderer {
        String render(JSONObject o);
    }

    private void appendEach(StringBuilder sb, JSONArray arr, Renderer r) {
        if (arr == null || arr.isEmpty()) {
            sb.append("<li><i>Nothing here yet.</i></li>");
            return;
        }
        for (int i = 0; i < arr.length(); i++) {
            sb.append(r.render(arr.getJSONObject(i)));
        }
    }

    private String wrap(String body) {
        return "<html><body style='font-family:Segoe UI,sans-serif;padding:14px;color:#1f2937'>"
                + "<style>h2{color:#4F46E5} h3{margin-bottom:2px}</style>" + body + "</body></html>";
    }

    private static String escape(String s) {
        return s == null ? "" : s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;");
    }
}
