package com.sdf.desktop.ui;

import com.sdf.desktop.Config;
import com.sdf.desktop.api.ApiClient;
import com.sdf.desktop.store.LocalStore;
import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;

/**
 * Entry screen. Offers a normal online login, and -- if a previous session
 * was cached locally -- an "open offline" path so the member can still
 * browse whatever was last synced without connectivity (SDD requirement #8).
 */
public class LoginFrame extends JFrame {

    private final ApiClient api;
    private final LocalStore store;

    private final JTextField serverField = new JTextField(Config.apiBaseUrl, 28);
    private final JTextField emailField = new JTextField(20);
    private final JPasswordField passwordField = new JPasswordField(20);
    private final JLabel statusLabel = new JLabel(" ");
    private final JButton loginButton = new JButton("Sign in");
    private final JButton offlineButton = new JButton("Continue offline");

    public LoginFrame(ApiClient api, LocalStore store) {
        super("Smart Discussion Forum - Sign in");
        this.api = api;
        this.store = store;

        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setIconImage(Brand.icon());
        setLayout(new GridBagLayout());
        setSize(420, 320);
        setLocationRelativeTo(null);

        GridBagConstraints c = new GridBagConstraints();
        c.insets = new Insets(6, 10, 6, 10);
        c.gridx = 0;
        c.gridy = 0;
        c.anchor = GridBagConstraints.WEST;

        GridBagConstraints labelC = (GridBagConstraints) c.clone();
        labelC.weightx = 0;
        labelC.fill = GridBagConstraints.NONE;

        GridBagConstraints fieldC = (GridBagConstraints) c.clone();
        fieldC.weightx = 1.0;
        fieldC.fill = GridBagConstraints.HORIZONTAL;

        JLabel title = new JLabel("Smart Discussion Forum", SwingConstants.CENTER);
        title.setFont(title.getFont().deriveFont(Font.BOLD, 18f));
        title.setForeground(Brand.ACCENT);
        add(title, gbc(labelC, 0, 0, 2));
        add(new JLabel("Server URL"), gbc(labelC, 0, 1, 1));
        add(serverField, gbc(fieldC, 1, 1, 1));
        add(new JLabel("Email"), gbc(labelC, 0, 2, 1));
        add(emailField, gbc(fieldC, 1, 2, 1));
        add(new JLabel("Password"), gbc(labelC, 0, 3, 1));
        add(passwordField, gbc(fieldC, 1, 3, 1));

        Brand.primary(loginButton);
        JPanel buttons = new JPanel(new FlowLayout(FlowLayout.CENTER));
        buttons.add(loginButton);
        buttons.add(offlineButton);
        add(buttons, gbc(labelC, 0, 4, 2));

        statusLabel.setForeground(Color.RED);
        add(statusLabel, gbc(labelC, 0, 5, 2));

        loginButton.addActionListener(e -> doLogin());
        offlineButton.addActionListener(e -> tryOfflineOpen());
        getRootPane().setDefaultButton(loginButton);

        refreshOfflineAvailability();
    }

    private GridBagConstraints gbc(GridBagConstraints c, int x, int y, int width) {
        GridBagConstraints copy = (GridBagConstraints) c.clone();
        copy.gridx = x;
        copy.gridy = y;
        copy.gridwidth = width;
        return copy;
    }

    private void refreshOfflineAvailability() {
        try {
            offlineButton.setEnabled(store.loadSession() != null);
        } catch (Exception e) {
            offlineButton.setEnabled(false);
        }
    }

    private void doLogin() {
        Config.apiBaseUrl = serverField.getText().trim();
        String email = emailField.getText().trim();
        String password = new String(passwordField.getPassword());

        if (email.isEmpty() || password.isEmpty()) {
            statusLabel.setText("Enter your email and password.");
            return;
        }

        setControlsEnabled(false);
        statusLabel.setForeground(Color.DARK_GRAY);
        statusLabel.setText("Signing in...");

        new SwingWorker<JSONObject, Void>() {
            Exception error;

            @Override
            protected JSONObject doInBackground() {
                try {
                    return api.login(email, password);
                } catch (Exception e) {
                    error = e;
                    return null;
                }
            }

            @Override
            protected void done() {
                setControlsEnabled(true);
                if (error != null) {
                    statusLabel.setForeground(Color.RED);
                    statusLabel.setText(error.getMessage());
                    return;
                }
                try {
                    JSONObject result = get();
                    store.saveSession(result.getString("token"), result.getJSONObject("user"));
                    openMainWindow(result.getJSONObject("user"), true);
                } catch (Exception e) {
                    statusLabel.setForeground(Color.RED);
                    statusLabel.setText("Login succeeded but saving the session failed: " + e.getMessage());
                }
            }
        }.execute();
    }

    private void tryOfflineOpen() {
        try {
            JSONObject session = store.loadSession();
            if (session == null) {
                statusLabel.setText("No cached session available yet -- sign in online at least once.");
                return;
            }
            api.setToken(session.getString("token"));
            JSONObject user = new JSONObject();
            user.put("id", session.getInt("user_id"));
            user.put("name", session.getString("user_name"));
            user.put("email", session.getString("user_email"));
            user.put("role", session.getString("user_role"));
            openMainWindow(user, false);
        } catch (Exception e) {
            statusLabel.setForeground(Color.RED);
            statusLabel.setText("Could not open offline: " + e.getMessage());
        }
    }

    private void openMainWindow(JSONObject user, boolean startedOnline) {
        new MainFrame(api, store, user, startedOnline).setVisible(true);
        dispose();
    }

    private void setControlsEnabled(boolean enabled) {
        loginButton.setEnabled(enabled);
        offlineButton.setEnabled(enabled && isOfflineAvailable());
        serverField.setEnabled(enabled);
        emailField.setEnabled(enabled);
        passwordField.setEnabled(enabled);
    }

    private boolean isOfflineAvailable() {
        try {
            return store.loadSession() != null;
        } catch (Exception e) {
            return false;
        }
    }
}
