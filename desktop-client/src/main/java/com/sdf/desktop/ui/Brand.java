package com.sdf.desktop.ui;

import javax.imageio.ImageIO;
import javax.swing.*;
import javax.swing.plaf.basic.BasicButtonUI;
import java.awt.*;

/**
 * Lightweight visual-identity helpers -- a small shared palette plus card/pill/
 * avatar builders, applied directly to specific components rather than a full
 * look-and-feel (FlatLaf was tried and rendered text fields at near-zero size
 * in this environment, so plain Swing + manual styling is what's actually
 * shipped).
 */
public final class Brand {

    // Core brand color, same Facebook blue (fb-600/700/50) used across the web app.
    public static final Color ACCENT = new Color(0x18, 0x77, 0xF2);
    public static final Color ACCENT_DARK = new Color(0x16, 0x6F, 0xE5);
    public static final Color ACCENT_LIGHT = new Color(0xE7, 0xF3, 0xFF);

    // Status colors, reused for badges/pills across panels so the same status
    // always reads the same color everywhere (active/open/resolved = green, etc).
    public static final Color SUCCESS = new Color(0x15, 0x80, 0x3D);
    public static final Color SUCCESS_BG = new Color(0xDC, 0xFC, 0xE7);
    public static final Color WARNING = new Color(0x92, 0x40, 0x0E);
    public static final Color WARNING_BG = new Color(0xFF, 0xED, 0xD5);
    public static final Color DANGER = new Color(0xB9, 0x1C, 0x1C);
    public static final Color DANGER_BG = new Color(0xFE, 0xE2, 0xE2);
    public static final Color INFO = new Color(0x1D, 0x4E, 0xD8);
    public static final Color INFO_BG = new Color(0xDB, 0xEA, 0xFE);
    public static final Color NEUTRAL = new Color(0x47, 0x55, 0x69);
    public static final Color NEUTRAL_BG = new Color(0xF1, 0xF5, 0xF9);

    // Extra hues used only to color-code tab icons.
    public static final Color TEAL = new Color(0x0D, 0x94, 0x88);
    public static final Color VIOLET = new Color(0x7C, 0x3A, 0xED);

    // Page chrome.
    public static final Color PAGE_BG = new Color(0xF0, 0xF2, 0xF5);
    public static final Color CARD_BG = Color.WHITE;
    public static final Color BORDER = new Color(0xE5, 0xE7, 0xEB);
    public static final Color TEXT = new Color(0x11, 0x18, 0x27);
    public static final Color TEXT_MUTED = new Color(0x6B, 0x72, 0x80);

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

    /** Styles a button as a secondary action (white background, indigo outline/text). */
    public static void secondary(JButton button) {
        button.setUI(new BasicButtonUI());
        button.setBackground(Color.WHITE);
        button.setForeground(ACCENT);
        button.setOpaque(true);
        button.setBorderPainted(true);
        button.setFocusPainted(false);
        button.setFont(button.getFont().deriveFont(Font.BOLD));
        button.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(ACCENT, 1),
                BorderFactory.createEmptyBorder(7, 16, 7, 16)));
    }

    /** Styles a button for a destructive action (white background, red outline/text). */
    public static void danger(JButton button) {
        button.setUI(new BasicButtonUI());
        button.setBackground(Color.WHITE);
        button.setForeground(DANGER);
        button.setOpaque(true);
        button.setBorderPainted(true);
        button.setFocusPainted(false);
        button.setFont(button.getFont().deriveFont(Font.BOLD));
        button.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(DANGER, 1),
                BorderFactory.createEmptyBorder(7, 16, 7, 16)));
    }

    /** A white "card" container with a light border and comfortable padding. */
    public static JPanel card() {
        JPanel card = new JPanel();
        card.setBackground(CARD_BG);
        card.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(BORDER, 1),
                BorderFactory.createEmptyBorder(14, 16, 14, 16)));
        card.setAlignmentX(Component.LEFT_ALIGNMENT);
        return card;
    }

    /** Bold section heading used at the top of a card. */
    public static JLabel sectionTitle(String text) {
        JLabel label = new JLabel(text);
        label.setFont(label.getFont().deriveFont(Font.BOLD, 14f));
        label.setForeground(ACCENT_DARK);
        label.setAlignmentX(Component.LEFT_ALIGNMENT);
        return label;
    }

    /** Small rounded-looking status badge (e.g. "active", "open", "blacklisted"). */
    public static JLabel pill(String text, Color bg, Color fg) {
        JLabel label = new JLabel(text);
        label.setOpaque(true);
        label.setBackground(bg);
        label.setForeground(fg);
        label.setFont(label.getFont().deriveFont(Font.BOLD, 11f));
        label.setBorder(BorderFactory.createEmptyBorder(3, 9, 3, 9));
        return label;
    }

    /** A filled circular avatar with the user's initials, drawn without any image assets. */
    public static JComponent avatar(String name, int size) {
        String initials = initials(name);
        JComponent circle = new JComponent() {
            @Override
            protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setColor(ACCENT);
                g2.fillOval(0, 0, getWidth() - 1, getHeight() - 1);
                g2.setColor(Color.WHITE);
                g2.setFont(getFont().deriveFont(Font.BOLD, size * 0.4f));
                FontMetrics fm = g2.getFontMetrics();
                int tx = (getWidth() - fm.stringWidth(initials)) / 2;
                int ty = (getHeight() - fm.getHeight()) / 2 + fm.getAscent();
                g2.drawString(initials, tx, ty);
                g2.dispose();
            }
        };
        circle.setPreferredSize(new Dimension(size, size));
        return circle;
    }

    private static String initials(String name) {
        if (name == null || name.isBlank()) return "?";
        String[] parts = name.trim().split("\\s+");
        String result = parts[0].substring(0, 1);
        if (parts.length > 1) result += parts[parts.length - 1].substring(0, 1);
        return result.toUpperCase();
    }

    /** A small filled-circle tab icon, used to color-code each tab at a glance. */
    public static Icon dot(Color color) {
        return new Icon() {
            @Override
            public void paintIcon(Component c, Graphics g, int x, int y) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setColor(color);
                g2.fillOval(x + 1, y + 3, 10, 10);
                g2.dispose();
            }

            @Override
            public int getIconWidth() {
                return 14;
            }

            @Override
            public int getIconHeight() {
                return 16;
            }
        };
    }

    private Brand() {}
}
