package com.sdf.desktop.ui;

import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;
import java.sql.SQLException;

public class QuizzesPanel extends JPanel {

    private final MainFrame main;
    private final DefaultListModel<JSONObject> model = new DefaultListModel<>();
    private final JList<JSONObject> quizList = new JList<>(model);

    public QuizzesPanel(MainFrame main) {
        this.main = main;
        setLayout(new BorderLayout(6, 6));

        quizList.setCellRenderer((list, value, index, isSelected, hasFocus) -> {
            String label = String.format("<html><b>%s</b> &middot; %s &middot; starts %s &middot; %d min &middot; %s</html>",
                    escape(value.getString("title")),
                    escape(groupName(value.optInt("group_id", 0))),
                    MainFrame.formatInstant(value.getString("start_at")),
                    value.getInt("duration_minutes"),
                    statusHtml(value.getString("status")));
            JLabel l = new JLabel(label);
            l.setOpaque(true);
            l.setBorder(BorderFactory.createEmptyBorder(6, 8, 6, 8));
            l.setBackground(isSelected ? list.getSelectionBackground() : list.getBackground());
            l.setForeground(isSelected ? list.getSelectionForeground() : list.getForeground());
            return l;
        });

        add(new JScrollPane(quizList), BorderLayout.CENTER);

        JButton openButton = new JButton("Open selected quiz");
        openButton.addActionListener(e -> openSelected());
        Brand.primary(openButton);
        JPanel southBar = new JPanel(new FlowLayout(FlowLayout.LEFT));
        southBar.add(openButton);
        add(southBar, BorderLayout.SOUTH);

        quizList.addMouseListener(new java.awt.event.MouseAdapter() {
            @Override
            public void mouseClicked(java.awt.event.MouseEvent e) {
                if (e.getClickCount() == 2) openSelected();
            }
        });
    }

    public void reloadFromCache() {
        model.clear();
        try {
            for (JSONObject q : main.store.getQuizzes()) model.addElement(q);
        } catch (SQLException ignored) {}
    }

    private String groupName(int groupId) {
        try {
            for (JSONObject g : main.store.getGroups()) {
                if (g.getInt("id") == groupId) return g.getString("name");
            }
        } catch (SQLException ignored) {}
        return "";
    }

    private void openSelected() {
        JSONObject q = quizList.getSelectedValue();
        if (q == null) return;

        if (!main.isOnline()) {
            JOptionPane.showMessageDialog(this, "You need to be online to open a quiz (the server is the timing authority).");
            return;
        }

        switch (q.getString("status")) {
            case "scheduled" -> JOptionPane.showMessageDialog(this,
                    "\"" + q.getString("title") + "\" opens at " + MainFrame.formatInstant(q.getString("start_at")) + ".\nCome back then to attempt it.");
            case "open" -> new QuizAttemptDialog(SwingUtilities.getWindowAncestor(this), main, q.getInt("id")).setVisible(true);
            case "closed" -> new QuizReportDialog(SwingUtilities.getWindowAncestor(this), main, q.getInt("id")).setVisible(true);
            default -> {}
        }
        reloadFromCache();
    }

    private static String statusHtml(String status) {
        String color = switch (status) {
            case "open" -> "#15803D";
            case "scheduled" -> "#92400E";
            case "closed" -> "#B91C1C";
            default -> "#475569";
        };
        return "<font color='" + color + "'><b>[" + status + "]</b></font>";
    }

    private static String escape(String s) {
        return s == null ? "" : s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;");
    }
}
