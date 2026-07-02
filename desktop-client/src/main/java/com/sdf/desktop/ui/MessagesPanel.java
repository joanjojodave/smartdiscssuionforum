package com.sdf.desktop.ui;

import org.json.JSONArray;
import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

public class MessagesPanel extends JPanel {

    private final MainFrame main;
    private final JComboBox<JSONObject> groupCombo = new JComboBox<>();
    private final DefaultListModel<JSONObject> messageModel = new DefaultListModel<>();
    private final JList<JSONObject> messageList = new JList<>(messageModel);
    private final JTextField inputField = new JTextField();
    private final List<Integer> excludedUserIds = new ArrayList<>();
    private final JLabel excludeStatusLabel = new JLabel(" ");

    public MessagesPanel(MainFrame main) {
        this.main = main;
        setLayout(new BorderLayout(6, 6));

        groupCombo.setRenderer(new DefaultListCellRenderer() {
            @Override
            public Component getListCellRendererComponent(JList<?> list, Object value, int index, boolean isSelected, boolean cellHasFocus) {
                super.getListCellRendererComponent(list, value, index, isSelected, cellHasFocus);
                if (value instanceof JSONObject g) setText(g.getString("name"));
                return this;
            }
        });

        messageList.setCellRenderer((list, value, index, isSelected, hasFocus) -> {
            String prefix = value.optBoolean("pending", false) ? " (sending...)" : "";
            JLabel l = new JLabel("<html><b>" + escape(value.optString("sender", "")) + ":</b> "
                    + escape(value.optString("body", "")) + prefix + "</html>");
            l.setBorder(BorderFactory.createEmptyBorder(3, 6, 3, 6));
            return l;
        });

        JPanel top = new JPanel(new BorderLayout());
        top.add(new JLabel("Group: "), BorderLayout.WEST);
        top.add(groupCombo, BorderLayout.CENTER);
        JButton excludeButton = new JButton("Exclude members...");
        top.add(excludeButton, BorderLayout.EAST);

        add(top, BorderLayout.NORTH);
        add(new JScrollPane(messageList), BorderLayout.CENTER);

        JPanel bottom = new JPanel(new BorderLayout(4, 2));
        bottom.add(excludeStatusLabel, BorderLayout.NORTH);
        JPanel sendRow = new JPanel(new BorderLayout(4, 4));
        sendRow.add(inputField, BorderLayout.CENTER);
        JButton sendButton = new JButton("Send");
        sendRow.add(sendButton, BorderLayout.EAST);
        bottom.add(sendRow, BorderLayout.SOUTH);
        add(bottom, BorderLayout.SOUTH);

        groupCombo.addActionListener(e -> reloadMessages());
        sendButton.addActionListener(e -> sendMessage());
        inputField.addActionListener(e -> sendMessage());
        excludeButton.addActionListener(e -> pickExcludedMembers());
    }

    public void reloadGroupList() {
        JSONObject selected = (JSONObject) groupCombo.getSelectedItem();
        groupCombo.removeAllItems();
        try {
            for (JSONObject g : main.store.getGroups()) {
                if ("active".equals(g.optString("my_status", null))) {
                    groupCombo.addItem(g);
                }
            }
        } catch (SQLException ignored) {}

        if (selected != null) {
            for (int i = 0; i < groupCombo.getItemCount(); i++) {
                if (groupCombo.getItemAt(i).getInt("id") == selected.getInt("id")) {
                    groupCombo.setSelectedIndex(i);
                    break;
                }
            }
        }
        reloadMessages();
    }

    private void reloadMessages() {
        messageModel.clear();
        JSONObject g = (JSONObject) groupCombo.getSelectedItem();
        if (g == null) return;
        try {
            for (JSONObject m : main.store.getMessagesForGroup(g.getInt("id"))) {
                messageModel.addElement(m);
            }
        } catch (SQLException ignored) {}
    }

    private void sendMessage() {
        JSONObject g = (JSONObject) groupCombo.getSelectedItem();
        String text = inputField.getText().trim();
        if (g == null || text.isEmpty()) return;

        try {
            if (main.isOnline()) {
                JSONObject body = new JSONObject();
                body.put("body", text);
                body.put("exclude", new JSONArray(excludedUserIds));
                main.api.post("/groups/" + g.getInt("id") + "/messages", body);
                main.runSync(true);
            } else {
                JSONObject sessionUser = new JSONObject();
                sessionUser.put("user_id", main.user.getInt("id"));
                sessionUser.put("user_name", main.user.getString("name"));
                main.store.queueOutboxMessage(g.getInt("id"), text, excludedUserIds, sessionUser);
            }
            inputField.setText("");
            excludedUserIds.clear();
            excludeStatusLabel.setText(" ");
            reloadMessages();
        } catch (Exception e) {
            JOptionPane.showMessageDialog(this, "Could not send message: " + e.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
        }
    }

    private void pickExcludedMembers() {
        JSONObject g = (JSONObject) groupCombo.getSelectedItem();
        if (g == null) return;
        if (!main.isOnline()) {
            JOptionPane.showMessageDialog(this, "You need to be online to load the member list.");
            return;
        }
        try {
            JSONObject response = main.api.get("/groups/" + g.getInt("id") + "/members");
            JSONArray members = response.getJSONArray("members");
            if (members.isEmpty()) {
                JOptionPane.showMessageDialog(this, "No other active members in this group.");
                return;
            }
            JPanel panel = new JPanel(new GridLayout(0, 1));
            List<JCheckBox> boxes = new ArrayList<>();
            for (int i = 0; i < members.length(); i++) {
                JSONObject m = members.getJSONObject(i);
                JCheckBox cb = new JCheckBox(m.getString("name"));
                cb.putClientProperty("userId", m.getInt("id"));
                cb.setSelected(excludedUserIds.contains(m.getInt("id")));
                boxes.add(cb);
                panel.add(cb);
            }
            int result = JOptionPane.showConfirmDialog(this, panel, "Exclude from next message",
                    JOptionPane.OK_CANCEL_OPTION, JOptionPane.PLAIN_MESSAGE);
            if (result == JOptionPane.OK_OPTION) {
                excludedUserIds.clear();
                for (JCheckBox cb : boxes) {
                    if (cb.isSelected()) excludedUserIds.add((Integer) cb.getClientProperty("userId"));
                }
                excludeStatusLabel.setText(excludedUserIds.isEmpty() ? " " : excludedUserIds.size() + " member(s) will be excluded from your next message");
            }
        } catch (Exception e) {
            JOptionPane.showMessageDialog(this, "Could not load members: " + e.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
        }
    }

    private static String escape(String s) {
        return s == null ? "" : s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;");
    }
}
