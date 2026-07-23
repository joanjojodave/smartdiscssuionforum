package com.sdf.desktop.ui;

import org.json.JSONArray;
import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;

public class NotificationsPanel extends JPanel {

    private final MainFrame main;
    private final DefaultListModel<JSONObject> model = new DefaultListModel<>();
    private final JList<JSONObject> list = new JList<>(model);

    public NotificationsPanel(MainFrame main) {
        this.main = main;
        setLayout(new BorderLayout(6, 6));

        list.setCellRenderer((l, value, index, isSelected, hasFocus) -> {
            boolean read = value.optBoolean("read", false);
            String message = escape(value.optString("message", ""));
            JLabel label = new JLabel("<html>" + (read ? message : "<b>" + message + "</b>")
                    + "<br><small>" + MainFrame.formatInstant(value.optString("created_at", "")) + "</small></html>");
            label.setOpaque(true);
            label.setBorder(BorderFactory.createEmptyBorder(6, 8, 6, 8));
            if (isSelected) {
                label.setBackground(l.getSelectionBackground());
                label.setForeground(l.getSelectionForeground());
            } else {
                label.setBackground(read ? l.getBackground() : Brand.ACCENT_LIGHT);
                label.setForeground(l.getForeground());
            }
            return label;
        });

        JPanel top = new JPanel(new BorderLayout());
        top.setBorder(BorderFactory.createEmptyBorder(4, 4, 8, 4));
        JLabel heading = new JLabel("Notifications");
        heading.setFont(heading.getFont().deriveFont(Font.BOLD, 16f));
        heading.setForeground(Brand.TEXT);
        top.add(heading, BorderLayout.WEST);
        JPanel buttons = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        JButton markReadButton = new JButton("Mark as read");
        JButton refreshButton = new JButton("Refresh");
        Brand.primary(markReadButton);
        Brand.secondary(refreshButton);
        buttons.add(markReadButton);
        buttons.add(refreshButton);
        top.add(buttons, BorderLayout.EAST);

        add(top, BorderLayout.NORTH);
        add(new JScrollPane(list), BorderLayout.CENTER);

        markReadButton.addActionListener(e -> markSelectedRead());
        refreshButton.addActionListener(e -> load());
    }

    public void load() {
        if (!main.isOnline()) return;
        try {
            JSONObject response = main.api.get("/notifications");
            model.clear();
            JSONArray arr = response.getJSONArray("notifications");
            for (int i = 0; i < arr.length(); i++) {
                model.addElement(arr.getJSONObject(i));
            }
        } catch (Exception ignored) {
            // leave whatever was last shown
        }
    }

    private void markSelectedRead() {
        JSONObject n = list.getSelectedValue();
        if (n == null) return;
        if (!main.isOnline()) {
            JOptionPane.showMessageDialog(this, "You need to be online to update notifications.");
            return;
        }
        try {
            main.api.post("/notifications/" + n.getString("id") + "/read", new JSONObject());
            load();
        } catch (Exception e) {
            JOptionPane.showMessageDialog(this, "Could not update: " + e.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
        }
    }

    private static String escape(String s) {
        return s == null ? "" : s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;");
    }
}
