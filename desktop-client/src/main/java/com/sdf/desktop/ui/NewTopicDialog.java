package com.sdf.desktop.ui;

import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;

/**
 * Topic creation goes straight to the server (unlike replies and chat
 * messages, it is not queued for offline replay) since it also triggers
 * server-side topic classification -- there is nothing meaningful to show
 * locally until that round-trip completes.
 */
public class NewTopicDialog extends JDialog {

    public NewTopicDialog(Window owner, MainFrame main, int groupId) {
        super(owner, "New topic", ModalityType.APPLICATION_MODAL);
        setSize(480, 380);
        setLocationRelativeTo(owner);
        setLayout(new BorderLayout(8, 8));

        JTextField titleField = new JTextField();
        JTextField categoryField = new JTextField();
        JTextArea bodyArea = new JTextArea();
        bodyArea.setLineWrap(true);
        bodyArea.setWrapStyleWord(true);
        JCheckBox isQuestion = new JCheckBox("This is a question I need answered");

        JPanel form = new JPanel(new GridBagLayout());
        GridBagConstraints c = new GridBagConstraints();
        c.insets = new Insets(4, 4, 4, 4);
        c.fill = GridBagConstraints.HORIZONTAL;

        c.gridx = 0; c.gridy = 0; c.weightx = 0;
        form.add(new JLabel("Title"), c);
        c.gridx = 1; c.weightx = 1;
        form.add(titleField, c);

        c.gridx = 0; c.gridy = 1; c.weightx = 0;
        form.add(new JLabel("Category (optional)"), c);
        c.gridx = 1; c.weightx = 1;
        form.add(categoryField, c);

        c.gridx = 0; c.gridy = 2; c.gridwidth = 2;
        form.add(isQuestion, c);

        add(form, BorderLayout.NORTH);
        add(new JScrollPane(bodyArea), BorderLayout.CENTER);

        if (!main.isOnline()) {
            JOptionPane.showMessageDialog(this, "You need to be online to start a new topic.");
        }

        JButton post = new JButton("Post topic");
        post.setEnabled(main.isOnline());
        JPanel south = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        south.add(post);
        add(south, BorderLayout.SOUTH);

        post.addActionListener(e -> {
            if (titleField.getText().isBlank() || bodyArea.getText().isBlank()) {
                JOptionPane.showMessageDialog(this, "Title and message are required.");
                return;
            }
            try {
                JSONObject body = new JSONObject();
                body.put("title", titleField.getText().trim());
                body.put("body", bodyArea.getText().trim());
                if (!categoryField.getText().isBlank()) body.put("category", categoryField.getText().trim());
                body.put("is_question", isQuestion.isSelected());

                main.api.post("/groups/" + groupId + "/topics", body);
                main.runSync(true);
                dispose();
            } catch (Exception ex) {
                JOptionPane.showMessageDialog(this, "Could not post topic: " + ex.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
            }
        });
    }
}
