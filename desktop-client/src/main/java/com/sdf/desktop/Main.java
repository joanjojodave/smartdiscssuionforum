package com.sdf.desktop;

import com.sdf.desktop.api.ApiClient;
import com.sdf.desktop.store.LocalStore;
import com.sdf.desktop.ui.LoginFrame;

import javax.swing.SwingUtilities;
import javax.swing.UIManager;
import java.awt.Color;

public class Main {
    /** Same indigo-600 used for the web app's branding, buttons, and links. */
    public static final Color BRAND_COLOR = new Color(0x4F, 0x46, 0xE5);

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
