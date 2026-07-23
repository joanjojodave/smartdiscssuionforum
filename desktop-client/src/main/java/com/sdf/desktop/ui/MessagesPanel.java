package com.sdf.desktop.ui;

import org.json.JSONArray;
import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

/**
 * Group chat: a three-pane layout -- the user's active chats on the left,
 * the selected conversation in the middle (bubble-style, sent messages
 * right-aligned in the brand color), and a notifications + suggested-groups
 * rail on the right.
 */
public class MessagesPanel extends JPanel {

    private final MainFrame main;

    private final DefaultListModel<JSONObject> chatListModel = new DefaultListModel<>();
    private final JList<JSONObject> chatList = new JList<>(chatListModel);

    private final DefaultListModel<JSONObject> messageModel = new DefaultListModel<>();
    private final JList<JSONObject> messageList = new JList<>(messageModel);
    private final JTextField inputField = new JTextField();
    private final List<Integer> excludedUserIds = new ArrayList<>();
    private final JLabel excludeStatusLabel = new JLabel(" ");

    private final DefaultListModel<JSONObject> notificationModel = new DefaultListModel<>();
    private final JList<JSONObject> notificationList = new JList<>(notificationModel);

    private final DefaultListModel<JSONObject> suggestedModel = new DefaultListModel<>();
    private final JList<JSONObject> suggestedList = new JList<>(suggestedModel);
    private final JButton joinSuggestedButton = new JButton("Join selected group");

    public MessagesPanel(MainFrame main) {
        this.main = main;
        setLayout(new BorderLayout(10, 10));
        setBackground(Brand.PAGE_BG);
        setBorder(BorderFactory.createEmptyBorder(10, 10, 10, 10));

        chatList.setSelectionMode(ListSelectionModel.SINGLE_SELECTION);
        suggestedList.setSelectionMode(ListSelectionModel.SINGLE_SELECTION);
        chatList.setCellRenderer((list, value, index, isSelected, hasFocus) -> chatRow(value, isSelected));
        messageList.setCellRenderer((list, value, index, isSelected, hasFocus) -> messageBubble(value));
        notificationList.setCellRenderer((list, value, index, isSelected, hasFocus) -> notificationRow(value));
        suggestedList.setCellRenderer((list, value, index, isSelected, hasFocus) -> suggestedRow(value, isSelected));

        add(buildChatListPanel(), BorderLayout.WEST);
        add(buildConversationPanel(), BorderLayout.CENTER);
        add(buildSidebarPanel(), BorderLayout.EAST);

        chatList.addListSelectionListener(e -> {
            if (!e.getValueIsAdjusting()) reloadMessages();
        });
        joinSuggestedButton.addActionListener(e -> joinSelectedSuggestion());
    }

    private JPanel buildChatListPanel() {
        JPanel panel = Brand.card();
        panel.setLayout(new BorderLayout(0, 8));
        panel.setPreferredSize(new Dimension(220, 0));
        panel.add(Brand.sectionTitle("Chats"), BorderLayout.NORTH);
        chatList.setBackground(Brand.CARD_BG);
        chatList.setFixedCellHeight(44);
        panel.add(new JScrollPane(chatList), BorderLayout.CENTER);
        return panel;
    }

    private JPanel buildConversationPanel() {
        JPanel panel = Brand.card();
        panel.setLayout(new BorderLayout(0, 8));
        messageList.setBackground(Brand.CARD_BG);
        panel.add(new JScrollPane(messageList), BorderLayout.CENTER);

        JPanel bottom = new JPanel(new BorderLayout(4, 4));
        bottom.setBackground(Brand.CARD_BG);
        bottom.add(excludeStatusLabel, BorderLayout.NORTH);
        JPanel sendRow = new JPanel(new BorderLayout(6, 0));
        sendRow.setBackground(Brand.CARD_BG);
        sendRow.add(inputField, BorderLayout.CENTER);
        JButton excludeButton = new JButton("Exclude...");
        Brand.secondary(excludeButton);
        JButton sendButton = new JButton("Send");
        Brand.primary(sendButton);
        JPanel sendButtons = new JPanel(new FlowLayout(FlowLayout.RIGHT, 6, 0));
        sendButtons.setBackground(Brand.CARD_BG);
        sendButtons.add(excludeButton);
        sendButtons.add(sendButton);
        sendRow.add(sendButtons, BorderLayout.EAST);
        bottom.add(sendRow, BorderLayout.SOUTH);
        panel.add(bottom, BorderLayout.SOUTH);

        sendButton.addActionListener(e -> sendMessage());
        inputField.addActionListener(e -> sendMessage());
        excludeButton.addActionListener(e -> pickExcludedMembers());

        return panel;
    }

    private JPanel buildSidebarPanel() {
        JPanel panel = new JPanel();
        panel.setLayout(new BoxLayout(panel, BoxLayout.Y_AXIS));
        panel.setBackground(Brand.PAGE_BG);
        panel.setPreferredSize(new Dimension(260, 0));

        JPanel notifCard = Brand.card();
        notifCard.setLayout(new BorderLayout(0, 8));
        notifCard.add(Brand.sectionTitle("Notifications"), BorderLayout.NORTH);
        notificationList.setBackground(Brand.CARD_BG);
        JScrollPane notifScroll = new JScrollPane(notificationList);
        notifScroll.setPreferredSize(new Dimension(240, 220));
        notifCard.add(notifScroll, BorderLayout.CENTER);

        JPanel suggestCard = Brand.card();
        suggestCard.setLayout(new BorderLayout(0, 8));
        suggestCard.add(Brand.sectionTitle("Suggested groups"), BorderLayout.NORTH);
        suggestedList.setBackground(Brand.CARD_BG);
        JScrollPane suggestScroll = new JScrollPane(suggestedList);
        suggestScroll.setPreferredSize(new Dimension(240, 160));
        suggestCard.add(suggestScroll, BorderLayout.CENTER);
        Brand.secondary(joinSuggestedButton);
        suggestCard.add(joinSuggestedButton, BorderLayout.SOUTH);

        panel.add(notifCard);
        panel.add(Box.createVerticalStrut(10));
        panel.add(suggestCard);
        return panel;
    }

    // -- cell renderers ------------------------------------------------------------

    private JPanel chatRow(JSONObject group, boolean selected) {
        JPanel row = new JPanel(new BorderLayout(8, 0));
        row.setBackground(selected ? Brand.ACCENT : Brand.CARD_BG);
        row.setBorder(BorderFactory.createEmptyBorder(4, 6, 4, 6));
        row.add(Brand.avatar(group.optString("name", "?"), 30), BorderLayout.WEST);
        JLabel name = new JLabel(group.optString("name", ""));
        name.setForeground(selected ? Color.WHITE : Brand.TEXT);
        name.setFont(name.getFont().deriveFont(selected ? Font.BOLD : Font.PLAIN));
        row.add(name, BorderLayout.CENTER);
        return row;
    }

    private JPanel messageBubble(JSONObject message) {
        boolean sent = message.optInt("sender_id", -1) == main.user.optInt("id", -2);
        String prefix = message.optBoolean("pending", false) ? " (sending...)" : "";

        JLabel bubble = new JLabel("<html>"
                + (sent ? "" : "<b>" + escape(message.optString("sender", "")) + "</b><br>")
                + escape(message.optString("body", "")) + prefix + "</html>");
        bubble.setOpaque(true);
        bubble.setBackground(sent ? Brand.ACCENT : Brand.NEUTRAL_BG);
        bubble.setForeground(sent ? Color.WHITE : Brand.TEXT);
        bubble.setBorder(BorderFactory.createEmptyBorder(8, 12, 8, 12));

        JPanel row = new JPanel(new FlowLayout(sent ? FlowLayout.RIGHT : FlowLayout.LEFT, 0, 4));
        row.setBackground(Brand.CARD_BG);
        row.add(bubble);
        return row;
    }

    private JLabel notificationRow(JSONObject n) {
        boolean read = n.optBoolean("read", false);
        JLabel label = new JLabel("<html><div style='width:190px'>"
                + (read ? escape(n.optString("message", "")) : "<b>" + escape(n.optString("message", "")) + "</b>")
                + "<br><small>" + MainFrame.formatInstant(n.optString("created_at", "")) + "</small></div></html>");
        label.setOpaque(true);
        label.setBackground(read ? Brand.CARD_BG : Brand.ACCENT_LIGHT);
        label.setBorder(BorderFactory.createEmptyBorder(6, 6, 6, 6));
        return label;
    }

    private JPanel suggestedRow(JSONObject group, boolean selected) {
        JPanel row = new JPanel(new BorderLayout(8, 0));
        row.setBackground(selected ? Brand.ACCENT_LIGHT : Brand.CARD_BG);
        row.setBorder(BorderFactory.createEmptyBorder(4, 6, 4, 6));
        row.add(Brand.avatar(group.optString("name", "?"), 26), BorderLayout.WEST);
        JLabel name = new JLabel("<html>" + escape(group.optString("name", ""))
                + "<br><small>" + group.optInt("members_count", 0) + " member(s)</small></html>");
        row.add(name, BorderLayout.CENTER);
        return row;
    }

    // -- data loading ---------------------------------------------------------------

    public void reloadGroupList() {
        JSONObject selected = chatList.getSelectedValue();
        chatListModel.clear();
        suggestedModel.clear();
        try {
            for (JSONObject g : main.store.getGroups()) {
                String status = g.optString("my_status", null);
                if ("active".equals(status)) {
                    chatListModel.addElement(g);
                } else if (status == null) {
                    suggestedModel.addElement(g);
                }
            }
        } catch (SQLException ignored) {}

        if (selected != null) {
            for (int i = 0; i < chatListModel.size(); i++) {
                if (chatListModel.get(i).getInt("id") == selected.getInt("id")) {
                    chatList.setSelectedIndex(i);
                    break;
                }
            }
        }
        if (chatList.getSelectedValue() == null && !chatListModel.isEmpty()) {
            chatList.setSelectedIndex(0);
        }

        reloadMessages();
        loadNotifications();
    }

    private void reloadMessages() {
        messageModel.clear();
        JSONObject g = chatList.getSelectedValue();
        if (g == null) return;
        try {
            for (JSONObject m : main.store.getMessagesForGroup(g.getInt("id"))) {
                messageModel.addElement(m);
            }
        } catch (SQLException ignored) {}
    }

    private void loadNotifications() {
        notificationModel.clear();
        if (!main.isOnline()) return;
        try {
            JSONObject response = main.api.get("/notifications");
            JSONArray arr = response.getJSONArray("notifications");
            for (int i = 0; i < Math.min(arr.length(), 8); i++) {
                notificationModel.addElement(arr.getJSONObject(i));
            }
        } catch (Exception ignored) {
            // leave whatever was last shown
        }
    }

    private void sendMessage() {
        JSONObject g = chatList.getSelectedValue();
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
        JSONObject g = chatList.getSelectedValue();
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

    private void joinSelectedSuggestion() {
        JSONObject g = suggestedList.getSelectedValue();
        if (g == null) return;
        if (!main.isOnline()) {
            JOptionPane.showMessageDialog(this, "You need to be online to request to join a group.");
            return;
        }
        try {
            main.api.post("/groups/" + g.getInt("id") + "/join", new JSONObject());
            main.store.updateMyGroupStatus(g.getInt("id"), "pending");
            reloadGroupList();
            JOptionPane.showMessageDialog(this, "Join request sent. Review and accept the group rules to activate your membership.");
        } catch (Exception e) {
            JOptionPane.showMessageDialog(this, "Could not join: " + e.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
        }
    }

    private static String escape(String s) {
        return s == null ? "" : s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;");
    }
}
