package com.sdf.desktop.ui;

import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;

public class ProfilePanel extends JPanel {

    private final MainFrame main;
    private final JTextField nameField = new JTextField(24);
    private final JTextField emailField = new JTextField(24);
    private final JLabel roleLabel = new JLabel();
    private final JLabel statusLabel = new JLabel(" ");

    public ProfilePanel(MainFrame main) {
        this.main = main;
        setLayout(new GridBagLayout());
        GridBagConstraints c = new GridBagConstraints();
        c.insets = new Insets(8, 8, 8, 8);
        c.anchor = GridBagConstraints.WEST;

        c.gridx = 0;
        c.gridy = 0;
        add(new JLabel("Name"), c);
        c.gridx = 1;
        add(nameField, c);

        c.gridx = 0;
        c.gridy = 1;
        add(new JLabel("Email"), c);
        c.gridx = 1;
        add(emailField, c);

        c.gridx = 0;
        c.gridy = 2;
        add(new JLabel("Role"), c);
        c.gridx = 1;
        add(roleLabel, c);

        JButton saveButton = new JButton("Save changes");
        saveButton.addActionListener(e -> save());
        Brand.primary(saveButton);
        c.gridx = 1;
        c.gridy = 3;
        add(saveButton, c);

        c.gridy = 4;
        add(statusLabel, c);

        nameField.setText(main.user.optString("name", ""));
        emailField.setText(main.user.optString("email", ""));
        roleLabel.setText(capitalize(main.user.optString("role", "")));
    }

    public void load() {
        if (!main.isOnline()) return;
        try {
            JSONObject p = main.api.get("/profile");
            nameField.setText(p.optString("name", ""));
            emailField.setText(p.optString("email", ""));
            roleLabel.setText(capitalize(p.optString("role", "")));
            statusLabel.setText(" ");
        } catch (Exception ignored) {
            // keep whatever was last shown
        }
    }

    private void save() {
        if (!main.isOnline()) {
            JOptionPane.showMessageDialog(this, "You need to be online to update your profile.");
            return;
        }
        try {
            JSONObject body = new JSONObject();
            body.put("name", nameField.getText().trim());
            body.put("email", emailField.getText().trim());
            main.api.patch("/profile", body);
            statusLabel.setForeground(new Color(0, 128, 0));
            statusLabel.setText("Saved.");
        } catch (Exception e) {
            statusLabel.setForeground(Color.RED);
            statusLabel.setText("Could not save: " + e.getMessage());
        }
    }

    private static String capitalize(String s) {
        return s == null || s.isEmpty() ? s : Character.toUpperCase(s.charAt(0)) + s.substring(1);
    }
}
