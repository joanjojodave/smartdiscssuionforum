package com.sdf.desktop.ui;

import org.json.JSONArray;
import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;

/**
 * Role-based landing summary (student participation/grade/recommendations,
 * or lecturer/admin group + quiz overview), backed by GET /api/dashboard.
 * Built from real Swing components (stat tiles + cards) rather than an HTML
 * dump so it reads as a proper dashboard rather than a wall of bullet points.
 */
public class DashboardPanel extends JPanel {

    private final MainFrame main;
    private final JPanel content = new JPanel();
    private final JLabel heading = new JLabel("Dashboard");
    private final JScrollPane scroll;

    public DashboardPanel(MainFrame main) {
        this.main = main;
        setLayout(new BorderLayout());
        setBackground(Brand.PAGE_BG);

        content.setLayout(new BoxLayout(content, BoxLayout.Y_AXIS));
        content.setBackground(Brand.PAGE_BG);
        content.setBorder(BorderFactory.createEmptyBorder(16, 16, 16, 16));

        JPanel top = new JPanel(new BorderLayout());
        top.setBackground(Brand.PAGE_BG);
        top.setBorder(BorderFactory.createEmptyBorder(14, 16, 0, 16));
        heading.setFont(heading.getFont().deriveFont(Font.BOLD, 20f));
        heading.setForeground(Brand.TEXT);
        top.add(heading, BorderLayout.WEST);
        JButton refreshButton = new JButton("Refresh");
        Brand.secondary(refreshButton);
        refreshButton.addActionListener(e -> load());
        top.add(refreshButton, BorderLayout.EAST);

        scroll = new JScrollPane(content);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(Brand.PAGE_BG);
        scroll.getVerticalScrollBar().setUnitIncrement(16);

        add(top, BorderLayout.NORTH);
        add(scroll, BorderLayout.CENTER);
    }

    public void load() {
        heading.setText("Welcome back, " + main.user.optString("name", ""));
        if (!main.isOnline()) {
            renderMessage("You need to be online to load the dashboard.");
            return;
        }
        try {
            JSONObject data = main.api.get("/dashboard");
            String role = data.optString("role", "student");
            content.removeAll();
            if ("student".equals(role)) {
                renderStudent(data);
            } else {
                renderStaff(data, role);
            }
            content.revalidate();
            content.repaint();
            scroll.getVerticalScrollBar().setValue(0);
        } catch (Exception e) {
            renderMessage("Could not load dashboard: " + e.getMessage());
        }
    }

    private void renderMessage(String text) {
        content.removeAll();
        JLabel label = new JLabel(text);
        label.setForeground(Brand.TEXT_MUTED);
        label.setAlignmentX(Component.LEFT_ALIGNMENT);
        content.add(label);
        content.revalidate();
        content.repaint();
    }

    private void renderStudent(JSONObject data) {
        int score = data.optInt("score", 0);
        String grade = data.optString("grade", "-");
        JSONArray memberships = data.optJSONArray("memberships");
        JSONArray announcements = data.optJSONArray("announcements");
        JSONArray recommendations = data.optJSONArray("recommendations");

        Color scoreAccent = score >= 70 ? Brand.SUCCESS : score >= 40 ? Brand.WARNING : Brand.DANGER;

        content.add(statRow(
                statTile("Participation score", score + "%", scoreAccent),
                statTile("Grade", grade, Brand.ACCENT),
                statTile("Groups joined", String.valueOf(length(memberships)), Brand.INFO),
                statTile("Upcoming quizzes", String.valueOf(length(announcements)), Brand.VIOLET)));
        content.add(Box.createVerticalStrut(16));

        content.add(sectionCard("Your groups", memberships, m ->
                row(m.optString("group_name"), statusPill(m.optString("status", "")))));
        content.add(Box.createVerticalStrut(12));

        content.add(sectionCard("Quizzes", announcements, q ->
                row(q.optString("title"), subtle(MainFrame.formatInstant(q.optString("start_at", ""))))));
        content.add(Box.createVerticalStrut(12));

        content.add(sectionCard("Recommended for you", recommendations, t ->
                row(t.optString("title"), subtle(t.optString("category", "")))));
    }

    private void renderStaff(JSONObject data, String role) {
        JSONArray groups = data.optJSONArray("groups");
        JSONArray quizzes = data.optJSONArray("quizzes");

        int totalMembers = 0;
        if (groups != null) {
            for (int i = 0; i < groups.length(); i++) totalMembers += groups.getJSONObject(i).optInt("members_count", 0);
        }
        int openQuizzes = 0;
        if (quizzes != null) {
            for (int i = 0; i < quizzes.length(); i++) if ("open".equals(quizzes.getJSONObject(i).optString("status"))) openQuizzes++;
        }

        heading.setText("Welcome back, " + main.user.optString("name", "") + " (" + capitalize(role) + ")");

        content.add(statRow(
                statTile("Groups", String.valueOf(length(groups)), Brand.INFO),
                statTile("Total members", String.valueOf(totalMembers), Brand.ACCENT),
                statTile("Quizzes", String.valueOf(length(quizzes)), Brand.VIOLET),
                statTile("Open now", String.valueOf(openQuizzes), Brand.SUCCESS)));
        content.add(Box.createVerticalStrut(16));

        content.add(sectionCard("Groups", groups, g ->
                row(g.optString("name"), subtle(g.optInt("members_count", 0) + " member(s)"))));
        content.add(Box.createVerticalStrut(12));

        content.add(sectionCard("Quizzes", quizzes, q -> {
            JPanel trailing = new JPanel(new FlowLayout(FlowLayout.RIGHT, 8, 0));
            trailing.setBackground(Brand.CARD_BG);
            trailing.add(subtle(MainFrame.formatInstant(q.optString("start_at", ""))));
            trailing.add(statusPill(q.optString("status", "")));
            return row(q.optString("title"), trailing);
        }));
    }

    // -- small component builders --------------------------------------------------

    private JPanel statRow(JPanel... tiles) {
        JPanel row = new JPanel(new GridLayout(1, tiles.length, 12, 0));
        row.setBackground(Brand.PAGE_BG);
        row.setAlignmentX(Component.LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 72));
        for (JPanel tile : tiles) row.add(tile);
        return row;
    }

    private JPanel statTile(String label, String value, Color accent) {
        JPanel tile = new JPanel();
        tile.setLayout(new BoxLayout(tile, BoxLayout.Y_AXIS));
        tile.setBackground(Brand.CARD_BG);
        tile.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 4, 0, 0, accent),
                BorderFactory.createEmptyBorder(10, 14, 10, 14)));

        JLabel valueLabel = new JLabel(value);
        valueLabel.setFont(valueLabel.getFont().deriveFont(Font.BOLD, 22f));
        valueLabel.setForeground(Brand.TEXT);
        JLabel captionLabel = new JLabel(label);
        captionLabel.setForeground(Brand.TEXT_MUTED);
        captionLabel.setFont(captionLabel.getFont().deriveFont(12f));

        tile.add(valueLabel);
        tile.add(captionLabel);
        return tile;
    }

    private interface RowBuilder {
        JPanel build(JSONObject item);
    }

    private JPanel sectionCard(String title, JSONArray items, RowBuilder builder) {
        JPanel card = Brand.card();
        card.setLayout(new BoxLayout(card, BoxLayout.Y_AXIS));
        card.setMaximumSize(new Dimension(Integer.MAX_VALUE, Integer.MAX_VALUE));
        card.add(Brand.sectionTitle(title));
        card.add(Box.createVerticalStrut(8));

        if (items == null || items.isEmpty()) {
            JLabel empty = new JLabel("Nothing here yet.");
            empty.setForeground(Brand.TEXT_MUTED);
            empty.setFont(empty.getFont().deriveFont(Font.ITALIC));
            empty.setAlignmentX(Component.LEFT_ALIGNMENT);
            card.add(empty);
        } else {
            for (int i = 0; i < items.length(); i++) {
                JPanel r = builder.build(items.getJSONObject(i));
                if (i < items.length() - 1) {
                    r.setBorder(BorderFactory.createCompoundBorder(
                            BorderFactory.createMatteBorder(0, 0, 1, 0, Brand.BORDER), r.getBorder()));
                }
                card.add(r);
            }
        }
        return card;
    }

    private JPanel row(String primaryText, JComponent trailing) {
        JPanel r = new JPanel(new BorderLayout(8, 0));
        r.setBackground(Brand.CARD_BG);
        r.setBorder(BorderFactory.createEmptyBorder(7, 0, 7, 0));
        r.setAlignmentX(Component.LEFT_ALIGNMENT);
        r.setMaximumSize(new Dimension(Integer.MAX_VALUE, 34));

        JLabel label = new JLabel(primaryText);
        label.setForeground(Brand.TEXT);
        r.add(label, BorderLayout.CENTER);
        if (trailing != null) r.add(trailing, BorderLayout.EAST);
        return r;
    }

    private JLabel subtle(String text) {
        JLabel label = new JLabel(text);
        label.setForeground(Brand.TEXT_MUTED);
        label.setFont(label.getFont().deriveFont(12f));
        return label;
    }

    private JLabel statusPill(String status) {
        return switch (status.toLowerCase()) {
            case "active", "open", "resolved" -> Brand.pill(status, Brand.SUCCESS_BG, Brand.SUCCESS);
            case "pending", "scheduled" -> Brand.pill(status, Brand.WARNING_BG, Brand.WARNING);
            case "blacklisted", "closed" -> Brand.pill(status, Brand.DANGER_BG, Brand.DANGER);
            default -> Brand.pill(status.isEmpty() ? "-" : status, Brand.NEUTRAL_BG, Brand.NEUTRAL);
        };
    }

    private static int length(JSONArray arr) {
        return arr == null ? 0 : arr.length();
    }

    private static String capitalize(String s) {
        return s == null || s.isEmpty() ? s : Character.toUpperCase(s.charAt(0)) + s.substring(1);
    }
}
