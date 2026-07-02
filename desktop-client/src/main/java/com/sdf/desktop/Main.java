package com.sdf.desktop;

import com.sdf.desktop.api.ApiClient;
import com.sdf.desktop.store.LocalStore;
import com.sdf.desktop.ui.LoginFrame;

import javax.swing.SwingUtilities;
import javax.swing.UIManager;

public class Main {
    public static void main(String[] args) {
        try {
            UIManager.setLookAndFeel(UIManager.getSystemLookAndFeelClassName());
        } catch (Exception ignored) {
            // fall back to the default cross-platform look and feel
        }

        LocalStore store = new LocalStore();
        try {
            store.open();
        } catch (Exception e) {
            System.err.println("Could not open local cache database: " + e.getMessage());
            e.printStackTrace();
            System.exit(1);
        }

        ApiClient api = new ApiClient();

        SwingUtilities.invokeLater(() -> new LoginFrame(api, store).setVisible(true));
    }
}
