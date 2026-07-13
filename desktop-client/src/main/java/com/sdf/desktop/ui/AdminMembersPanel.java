package com.sdf.desktop.ui;

import org.json.JSONArray;
import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;

/**
 * Admin-only tab: warn, blacklist, or reinstate any member across any group
 * (requirement #4), backed by /api/admin/members and /api/admin/memberships/*.
 */
public class AdminMembersPanel extends JPanel {

    private static final JSONObject ALL_GROUPS = new JSONObject().put("id", 0).put("name", "All groups");

    private final MainFrame main;
    private final JComboBox<JSONObject> groupCombo = new JComboBox<>();
    private final DefaultListModel<JSONObject> model = new DefaultListModel<>();
    private final JList<JSONObject> list = new JList<>(model);
    private boolean groupsLoaded = false;

    public AdminMembersPanel(MainFrame main) {
        this.main = main;
        setLayout(new BorderLayout(6, 6));

        groupCombo.addItem(ALL_GROUPS);
        groupCombo.setRenderer(new DefaultListCellRenderer() {
            @Override
            public Component getListCellRendererComponent(JList<?> l, Object value, int index, boolean isSelected, boolean hasFocus) {
                super.getListCellRendererComponent(l, value, index, isSelected, hasFocus);
                if (value instanceof JSONObject g) setText(g.getString("name"));
                return this;
            }
        });

        list.setCellRenderer((l, value, index, isSelected, hasFocus) -> {
            String status = value.optString("status", "active");
            Color bg = switch (status) {
                case "blacklisted" -> new Color(0xFE, 0xE2, 0xE2);
                case "warned" -> new Color(0xFF, 0xED, 0xD5);
                default -> l.getBackground();
            };
            JLabel label = new JLabel("<html>" + escape(value.optString("user_name")) + " &middot; "
                    + escape(value.optString("group_name")) + "<br><small>" + status + " &middot; "
                    + value.optInt("warnings_count", 0) + " warning(s)</small></html>");
            label.setOpaque(true);
            label.setBorder(BorderFactory.createEmptyBorder(6, 8, 6, 8));
            if (isSelected) {
                label.setBackground(l.getSelectionBackground());
                label.setForeground(l.getSelectionForeground());
            } else {
                label.setBackground(bg);
            }
            return label;
        });

        JPanel top = new JPanel(new BorderLayout(6, 0));
        top.add(new JLabel("Group:"), BorderLayout.WEST);
        top.add(groupCombo, BorderLayout.CENTER);
        JButton refreshButton = new JButton("Refresh");
        refreshButton.addActionListener(e -> load());
        top.add(refreshButton, BorderLayout.EAST);

        JPanel bottom = new JPanel(new FlowLayout(FlowLayout.LEFT));
        JButton warnButton = new JButton("Warn");
        JButton blacklistButton = new JButton("Blacklist");
        JButton reinstateButton = new JButton("Reinstate");
        warnButton.addActionListener(e -> act("warn"));
        blacklistButton.addActionListener(e -> act("blacklist"));
        reinstateButton.addActionListener(e -> act("reinstate"));
        bottom.add(warnButton);
        bottom.add(blacklistButton);
        bottom.add(reinstateButton);

        add(top, BorderLayout.NORTH);
        add(new JScrollPane(list), BorderLayout.CENTER);
        add(bottom, BorderLayout.SOUTH);

        groupCombo.addActionListener(e -> load());
    }

    public void load() {
        if (!main.isOnline()) return;
        try {
            JSONObject selected = (JSONObject) groupCombo.getSelectedItem();
            int groupId = selected == null ? 0 : selected.getInt("id");
            String path = "/admin/members" + (groupId != 0 ? "?group_id=" + groupId : "");
            JSONObject response = main.api.get(path);

            if (!groupsLoaded) {
                JSONArray groups = response.getJSONArray("groups");
                for (int i = 0; i < groups.length(); i++) {
                    groupCombo.addItem(groups.getJSONObject(i));
                }
                groupsLoaded = true;
            }

            model.clear();
            JSONArray memberships = response.getJSONArray("memberships");
            for (int i = 0; i < memberships.length(); i++) {
                model.addElement(memberships.getJSONObject(i));
            }
        } catch (Exception e) {
            JOptionPane.showMessageDialog(this, "Could not load members: " + e.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
        }
    }

    private void act(String action) {
        JSONObject m = list.getSelectedValue();
        if (m == null) return;
        if (!main.isOnline()) {
            JOptionPane.showMessageDialog(this, "You need to be online to do that.");
            return;
        }
        try {
            main.api.post("/admin/memberships/" + m.getInt("id") + "/" + action, new JSONObject());
            load();
        } catch (Exception e) {
            JOptionPane.showMessageDialog(this, "Could not " + action + ": " + e.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
        }
    }

    private static String escape(String s) {
        return s == null ? "" : s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;");
    }
}
