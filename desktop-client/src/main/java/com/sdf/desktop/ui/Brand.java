package com.sdf.desktop.ui;

import javax.imageio.ImageIO;
import javax.swing.*;
import javax.swing.plaf.basic.BasicButtonUI;
import java.awt.*;

/**
 * Lightweight visual-identity helpers -- the same indigo-600 used across the
 * web app's buttons/branding, applied directly to specific components rather
 * than a full look-and-feel (FlatLaf was tried and rendered text fields at
 * near-zero size in this environment, so plain Swing + manual styling is
 * what's actually shipped).
 */
public final class Brand {

    public static final Color ACCENT = new Color(0x4F, 0x46, 0xE5);

    public static Image icon() {
        try {
            return ImageIO.read(Brand.class.getResourceAsStream("/app-icon.png"));
        } catch (Exception e) {
            return null;
        }
    }

    /** Styles a button as the primary call-to-action for its screen (solid indigo, white bold text). */
    public static void primary(JButton button) {
        button.setUI(new BasicButtonUI());
        button.setBackground(ACCENT);
        button.setForeground(Color.WHITE);
        button.setOpaque(true);
        button.setBorderPainted(false);
        button.setFocusPainted(false);
        button.setFont(button.getFont().deriveFont(Font.BOLD));
        button.setBorder(BorderFactory.createEmptyBorder(8, 18, 8, 18));
    }

    private Brand() {}
}
