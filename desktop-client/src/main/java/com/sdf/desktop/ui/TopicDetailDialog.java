package com.sdf.desktop.ui;

import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

public class TopicDetailDialog extends JDialog {

    private final MainFrame main;
    private final int topicId;
    private final JPanel threadPanel = new JPanel();
    private final JTextArea replyArea = new JTextArea(3, 40);
    private Integer replyingToPostId = null;
    private final JLabel replyingToLabel = new JLabel(" ");

    public TopicDetailDialog(Window owner, MainFrame main, int topicId) {
        super(owner, "Topic", ModalityType.APPLICATION_MODAL);
        this.main = main;
        this.topicId = topicId;
        setSize(640, 560);
        setLocationRelativeTo(owner);
        setLayout(new BorderLayout(6, 6));

        threadPanel.setLayout(new BoxLayout(threadPanel, BoxLayout.Y_AXIS));
        add(new JScrollPane(threadPanel), BorderLayout.CENTER);

        JPanel replyPanel = new JPanel(new BorderLayout(4, 4));
        replyPanel.setBorder(BorderFactory.createTitledBorder("Reply"));
        replyArea.setLineWrap(true);
        replyArea.setWrapStyleWord(true);
        replyPanel.add(replyingToLabel, BorderLayout.NORTH);
        replyPanel.add(new JScrollPane(replyArea), BorderLayout.CENTER);

        JButton sendButton = new JButton("Post reply");
        JButton clearReplyTarget = new JButton("Reply to whole topic");
        JPanel buttonRow = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        buttonRow.add(clearReplyTarget);
        buttonRow.add(sendButton);
        replyPanel.add(buttonRow, BorderLayout.SOUTH);
        add(replyPanel, BorderLayout.SOUTH);

        clearReplyTarget.addActionListener(e -> setReplyTarget(null, null));
        sendButton.addActionListener(e -> sendReply());

        try {
            JSONObject topic = main.store.getTopic(topicId);
            setTitle(topic != null ? topic.getString("title") : "Topic");
        } catch (SQLException ignored) {}

        refreshThread();
    }

    private void refreshThread() {
        threadPanel.removeAll();
        try {
            List<JSONObject> posts = main.store.getPostsForTopic(topicId);
            List<JSONObject> roots = new ArrayList<>();
            for (JSONObject p : posts) {
                if (p.isNull("parent_post_id")) roots.add(p);
            }
            for (JSONObject root : roots) {
                threadPanel.add(buildPostCard(root, false));
                for (JSONObject p : posts) {
                    if (!p.isNull("parent_post_id") && p.getInt("parent_post_id") == root.getInt("id")) {
                        threadPanel.add(buildPostCard(p, true));
                    }
                }
                threadPanel.add(Box.createVerticalStrut(6));
            }
        } catch (SQLException e) {
            threadPanel.add(new JLabel("Could not load posts: " + e.getMessage()));
        }
        threadPanel.revalidate();
        threadPanel.repaint();
    }

    private JPanel buildPostCard(JSONObject post, boolean indent) {
        JPanel card = new JPanel(new BorderLayout());
        card.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createEmptyBorder(2, indent ? 30 : 4, 2, 4),
                BorderFactory.createLineBorder(Color.LIGHT_GRAY)));

        StringBuilder header = new StringBuilder(post.optString("author", "Unknown"));
        if (post.optBoolean("is_question", false)) header.append("  [question]");
        if (post.optBoolean("is_answer", false)) header.append("  [accepted answer]");
        if (post.optBoolean("pending", false)) header.append("  (sending...)");

        JLabel headerLabel = new JLabel(header.toString());
        headerLabel.setFont(headerLabel.getFont().deriveFont(Font.BOLD, 12f));

        JTextArea body = new JTextArea(post.optString("body", ""));
        body.setEditable(false);
        body.setLineWrap(true);
        body.setWrapStyleWord(true);
        body.setOpaque(false);

        JPanel top = new JPanel(new BorderLayout());
        top.add(headerLabel, BorderLayout.WEST);

        if (!indent) {
            JButton replyBtn = new JButton("Reply");
            replyBtn.addActionListener(e -> setReplyTarget(post.getInt("id"), post.optString("author", "")));
            top.add(replyBtn, BorderLayout.EAST);
        } else if (!post.optBoolean("is_answer", false)) {
            JButton markAnswer = new JButton("Mark as answer");
            markAnswer.addActionListener(e -> markAsAnswer(post.getInt("id")));
            markAnswer.setEnabled(main.isOnline());
            top.add(markAnswer, BorderLayout.EAST);
        }

        card.add(top, BorderLayout.NORTH);
        card.add(body, BorderLayout.CENTER);
        return card;
    }

    private void setReplyTarget(Integer postId, String authorName) {
        this.replyingToPostId = postId;
        replyingToLabel.setText(postId == null ? "Replying to the topic" : "Replying to " + authorName);
    }

    private void sendReply() {
        String text = replyArea.getText().trim();
        if (text.isEmpty()) return;

        try {
            if (main.isOnline()) {
                JSONObject body = new JSONObject();
                body.put("body", text);
                if (replyingToPostId != null) body.put("parent_post_id", replyingToPostId);
                main.api.post("/topics/" + topicId + "/posts", body);
                main.runSync(true);
            } else {
                main.store.queueOutboxPost(topicId, replyingToPostId, text, false, sessionUser());
            }
            replyArea.setText("");
            refreshThread();
        } catch (Exception e) {
            JOptionPane.showMessageDialog(this, "Could not post reply: " + e.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
        }
    }

    private void markAsAnswer(int postId) {
        try {
            main.api.post("/posts/" + postId + "/mark-answer", new JSONObject());
            main.runSync(true);
            refreshThread();
        } catch (Exception e) {
            JOptionPane.showMessageDialog(this, "Could not mark as answer: " + e.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
        }
    }

    private JSONObject sessionUser() {
        JSONObject u = new JSONObject();
        u.put("user_id", main.user.getInt("id"));
        u.put("user_name", main.user.getString("name"));
        return u;
    }
}
