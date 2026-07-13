package com.sdf.desktop.ui;

import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;
import java.sql.SQLException;
import java.util.List;

public class GroupsPanel extends JPanel {

    private final MainFrame main;

    private final DefaultListModel<JSONObject> groupListModel = new DefaultListModel<>();
    private final JList<JSONObject> groupList = new JList<>(groupListModel);

    private final DefaultListModel<JSONObject> topicListModel = new DefaultListModel<>();
    private final JList<JSONObject> topicList = new JList<>(topicListModel);

    private final JButton joinButton = new JButton("Join group");
    private final JButton rulesButton = new JButton("Review rules");
    private final JButton newTopicButton = new JButton("+ New topic");
    private final JLabel groupInfoLabel = new JLabel(" ");

    public GroupsPanel(MainFrame main) {
        this.main = main;
        setLayout(new BorderLayout());

        groupList.setCellRenderer((list, value, index, isSelected, hasFocus) -> {
            String status = value.optString("my_status", null);
            String label = value.getString("name") + (status != null ? "  [" + status + "]" : "  [not joined]");
            JLabel l = new JLabel(label);
            l.setOpaque(true);
            l.setBackground(isSelected ? list.getSelectionBackground() : list.getBackground());
            l.setForeground(isSelected ? list.getSelectionForeground() : list.getForeground());
            l.setBorder(BorderFactory.createEmptyBorder(4, 8, 4, 8));
            return l;
        });

        topicList.setCellRenderer((list, value, index, isSelected, hasFocus) -> {
            StringBuilder sb = new StringBuilder("<html>").append(escape(value.getString("title")));
            if (value.optBoolean("has_unanswered", false)) sb.append(" <font color='red'>[unanswered]</font>");
            if (value.optBoolean("is_resolved", false)) sb.append(" <font color='green'>[resolved]</font>");
            sb.append("<br><small>").append(value.optString("category", "")).append(" &middot; ")
                    .append(value.optInt("posts_count", 0)).append(" post(s)</small></html>");
            JLabel l = new JLabel(sb.toString());
            l.setOpaque(true);
            l.setBackground(isSelected ? list.getSelectionBackground() : list.getBackground());
            l.setForeground(isSelected ? list.getSelectionForeground() : list.getForeground());
            l.setBorder(BorderFactory.createEmptyBorder(4, 8, 4, 8));
            return l;
        });

        JPanel groupSide = new JPanel(new BorderLayout());
        groupSide.setBorder(BorderFactory.createTitledBorder("Groups"));
        groupSide.add(new JScrollPane(groupList), BorderLayout.CENTER);
        JPanel groupButtons = new JPanel(new GridLayout(2, 1));
        groupButtons.add(joinButton);
        groupButtons.add(rulesButton);
        groupSide.add(groupButtons, BorderLayout.SOUTH);
        groupSide.setPreferredSize(new Dimension(260, 0));

        JPanel topicSide = new JPanel(new BorderLayout());
        topicSide.setBorder(BorderFactory.createTitledBorder("Topics"));
        topicSide.add(groupInfoLabel, BorderLayout.NORTH);
        topicSide.add(new JScrollPane(topicList), BorderLayout.CENTER);
        topicSide.add(newTopicButton, BorderLayout.SOUTH);

        JSplitPane split = new JSplitPane(JSplitPane.HORIZONTAL_SPLIT, groupSide, topicSide);
        split.setDividerLocation(260);
        add(split, BorderLayout.CENTER);

        groupList.addListSelectionListener(e -> {
            if (!e.getValueIsAdjusting()) onGroupSelected();
        });
        topicList.addMouseListener(new java.awt.event.MouseAdapter() {
            @Override
            public void mouseClicked(java.awt.event.MouseEvent e) {
                if (e.getClickCount() == 2) openSelectedTopic();
            }
        });

        joinButton.addActionListener(e -> joinSelectedGroup());
        rulesButton.addActionListener(e -> reviewRules());
        newTopicButton.addActionListener(e -> newTopic());

        Brand.primary(newTopicButton);
        setButtonsEnabled(false);
    }

    public void reloadFromCache() {
        JSONObject selected = groupList.getSelectedValue();
        groupListModel.clear();
        try {
            List<JSONObject> groups = main.store.getGroups();
            for (JSONObject g : groups) groupListModel.addElement(g);
        } catch (SQLException e) {
            groupInfoLabel.setText("Could not load groups: " + e.getMessage());
        }
        if (selected != null) {
            for (int i = 0; i < groupListModel.size(); i++) {
                if (groupListModel.get(i).getInt("id") == selected.getInt("id")) {
                    groupList.setSelectedIndex(i);
                    break;
                }
            }
        }
        onGroupSelected();
    }

    private void onGroupSelected() {
        JSONObject g = groupList.getSelectedValue();
        topicListModel.clear();
        if (g == null) {
            setButtonsEnabled(false);
            groupInfoLabel.setText(" ");
            return;
        }

        String status = g.optString("my_status", null);
        boolean isActive = "active".equals(status);
        joinButton.setEnabled(status == null);
        rulesButton.setEnabled("pending".equals(status));
        newTopicButton.setEnabled(isActive);

        groupInfoLabel.setText("<html>" + escape(g.optString("description", "")) + "</html>");

        if (isActive) {
            try {
                for (JSONObject t : main.store.getTopicsForGroup(g.getInt("id"))) {
                    topicListModel.addElement(t);
                }
            } catch (SQLException e) {
                groupInfoLabel.setText("Could not load topics: " + e.getMessage());
            }
        }
    }

    private void setButtonsEnabled(boolean enabled) {
        joinButton.setEnabled(enabled);
        rulesButton.setEnabled(enabled);
        newTopicButton.setEnabled(enabled);
    }

    private void joinSelectedGroup() {
        JSONObject g = groupList.getSelectedValue();
        if (g == null) return;
        if (!main.isOnline()) {
            JOptionPane.showMessageDialog(this, "You need to be online to request to join a group.");
            return;
        }
        try {
            main.api.post("/groups/" + g.getInt("id") + "/join", new JSONObject());
            main.store.updateMyGroupStatus(g.getInt("id"), "pending");
            reloadFromCache();
            JOptionPane.showMessageDialog(this, "Join request sent. Review and accept the group rules to activate your membership.");
        } catch (Exception e) {
            JOptionPane.showMessageDialog(this, "Could not join: " + e.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
        }
    }

    private void reviewRules() {
        JSONObject g = groupList.getSelectedValue();
        if (g == null) return;
        if (!main.isOnline()) {
            JOptionPane.showMessageDialog(this, "You need to be online to accept group rules.");
            return;
        }
        new RulesDialog(SwingUtilities.getWindowAncestor(this), main, g).setVisible(true);
        reloadFromCache();
    }

    private void openSelectedTopic() {
        JSONObject t = topicList.getSelectedValue();
        if (t == null) return;
        new TopicDetailDialog(SwingUtilities.getWindowAncestor(this), main, t.getInt("id")).setVisible(true);
        reloadFromCache();
    }

    private void newTopic() {
        JSONObject g = groupList.getSelectedValue();
        if (g == null) return;
        new NewTopicDialog(SwingUtilities.getWindowAncestor(this), main, g.getInt("id")).setVisible(true);
        reloadFromCache();
    }

    private static String escape(String s) {
        return s == null ? "" : s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;");
    }
}
