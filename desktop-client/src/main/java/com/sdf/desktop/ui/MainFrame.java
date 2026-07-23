package com.sdf.desktop.ui;

import com.sdf.desktop.Config;
import com.sdf.desktop.api.ApiClient;
import com.sdf.desktop.store.LocalStore;
import com.sdf.desktop.sync.SyncService;
import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;
import java.time.format.DateTimeFormatter;
import java.time.Instant;
import java.time.ZoneId;

/**
 * Main application window: tabs for discussions, group chat and quizzes,
 * plus a status bar that reflects online/offline state and drives periodic
 * background sync (SDD 5.11).
 */
public class MainFrame extends JFrame {

    public final ApiClient api;
    public final LocalStore store;
    public final JSONObject user;

    private final JLabel statusLabel = new JLabel();
    private final JLabel pendingLabel = new JLabel();
    private final javax.swing.Timer syncTimer;

    private final DashboardPanel dashboardPanel;
    private final GroupsPanel groupsPanel;
    private final MessagesPanel messagesPanel;
    private final QuizzesPanel quizzesPanel;
    private final NotificationsPanel notificationsPanel;
    private final ProfilePanel profilePanel;
    private final AdminMembersPanel adminMembersPanel;

    private volatile boolean online = false;

    public MainFrame(ApiClient api, LocalStore store, JSONObject user, boolean startedOnline) {
        super("Smart Discussion Forum - " + user.getString("name") + " (" + user.getString("role") + ")");
        this.api = api;
        this.store = store;
        this.user = user;
        this.online = startedOnline;

        setDefaultCloseOperation(JFrame.DISPOSE_ON_CLOSE);
        setIconImage(Brand.icon());
        setSize(1000, 680);
        setLocationRelativeTo(null);
        setLayout(new BorderLayout());

        dashboardPanel = new DashboardPanel(this);
        groupsPanel = new GroupsPanel(this);
        messagesPanel = new MessagesPanel(this);
        quizzesPanel = new QuizzesPanel(this);
        notificationsPanel = new NotificationsPanel(this);
        profilePanel = new ProfilePanel(this);
        adminMembersPanel = new AdminMembersPanel(this);

        JTabbedPane tabs = new JTabbedPane();
        tabs.setFont(tabs.getFont().deriveFont(Font.BOLD, 13f));
        tabs.addTab("Dashboard", Brand.dot(Brand.ACCENT), dashboardPanel);
        tabs.addTab("Groups & Discussions", Brand.dot(Brand.INFO), groupsPanel);
        tabs.addTab("Group Chat", Brand.dot(Brand.TEAL), messagesPanel);
        tabs.addTab("Quizzes", Brand.dot(Brand.VIOLET), quizzesPanel);
        tabs.addTab("Notifications", Brand.dot(Brand.WARNING), notificationsPanel);
        if ("admin".equals(user.optString("role"))) {
            tabs.addTab("Admin - Members", Brand.dot(Brand.DANGER), adminMembersPanel);
        }
        tabs.addTab("Profile", Brand.dot(Brand.NEUTRAL), profilePanel);

        add(buildHeaderBar(), BorderLayout.NORTH);
        add(tabs, BorderLayout.CENTER);
        add(buildStatusBar(), BorderLayout.SOUTH);

        loadFromCache();

        syncTimer = new javax.swing.Timer(Config.SYNC_INTERVAL_MS, e -> runSync(false));
        syncTimer.setInitialDelay(200);
        syncTimer.start();

        addWindowListener(new java.awt.event.WindowAdapter() {
            @Override
            public void windowClosed(java.awt.event.WindowEvent e) {
                syncTimer.stop();
                store.close();
            }
        });
    }

    private JPanel buildHeaderBar() {
        JPanel bar = new JPanel(new BorderLayout());
        bar.setBackground(Color.WHITE);
        bar.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 0, 1, 0, Brand.BORDER),
                BorderFactory.createEmptyBorder(10, 18, 10, 18)));

        JLabel title = new JLabel("Smart Discussion Forum");
        title.setFont(title.getFont().deriveFont(Font.BOLD, 17f));
        title.setForeground(Brand.ACCENT);
        bar.add(title, BorderLayout.WEST);

        JPanel userInfo = new JPanel();
        userInfo.setOpaque(false);
        userInfo.setLayout(new BoxLayout(userInfo, BoxLayout.Y_AXIS));
        JLabel nameLabel = new JLabel(user.getString("name"));
        nameLabel.setFont(nameLabel.getFont().deriveFont(Font.BOLD, 13f));
        nameLabel.setAlignmentX(Component.RIGHT_ALIGNMENT);
        JLabel roleBadge = Brand.pill(capitalize(user.optString("role", "")), Brand.ACCENT_LIGHT, Brand.ACCENT_DARK);
        roleBadge.setAlignmentX(Component.RIGHT_ALIGNMENT);
        userInfo.add(nameLabel);
        userInfo.add(Box.createVerticalStrut(3));
        userInfo.add(roleBadge);

        JPanel right = new JPanel(new FlowLayout(FlowLayout.RIGHT, 12, 0));
        right.setOpaque(false);
        right.add(userInfo);
        right.add(Brand.avatar(user.getString("name"), 36));
        bar.add(right, BorderLayout.EAST);

        return bar;
    }

    private static String capitalize(String s) {
        return s == null || s.isEmpty() ? s : Character.toUpperCase(s.charAt(0)) + s.substring(1);
    }

    private JPanel buildStatusBar() {
        JPanel bar = new JPanel(new BorderLayout());
        bar.setBackground(Brand.PAGE_BG);
        bar.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(1, 0, 0, 0, Brand.BORDER),
                BorderFactory.createEmptyBorder(6, 14, 6, 14)));

        JPanel left = new JPanel(new FlowLayout(FlowLayout.LEFT, 10, 0));
        left.setOpaque(false);
        left.add(statusLabel);
        pendingLabel.setForeground(Brand.TEXT_MUTED);
        left.add(pendingLabel);

        JPanel right = new JPanel(new FlowLayout(FlowLayout.RIGHT, 10, 0));
        right.setOpaque(false);
        JButton syncNowButton = new JButton("Sync now");
        Brand.secondary(syncNowButton);
        syncNowButton.addActionListener(e -> runSync(true));
        JButton logoutButton = new JButton("Log out");
        logoutButton.addActionListener(e -> logout());

        right.add(syncNowButton);
        right.add(logoutButton);

        bar.add(left, BorderLayout.WEST);
        bar.add(right, BorderLayout.EAST);
        updateStatusLabel();
        return bar;
    }

    /** Loads whatever is already cached locally so the UI is populated instantly, even offline. */
    private void loadFromCache() {
        groupsPanel.reloadFromCache();
        messagesPanel.reloadGroupList();
        quizzesPanel.reloadFromCache();

        // These tabs have no offline cache of their own -- they're simple
        // online-only reads that no-op when there's no connection yet.
        dashboardPanel.load();
        notificationsPanel.load();
        profilePanel.load();
        if ("admin".equals(user.optString("role"))) {
            adminMembersPanel.load();
        }
    }

    public void runSync(boolean manual) {
        statusLabel.setText("Syncing...");
        new SwingWorker<Boolean, Void>() {
            @Override
            protected Boolean doInBackground() {
                // The dedicated /groups endpoint carries membership status for
                // every group (including ones not yet joined), which the
                // generic sync/pull intentionally omits (it only reports groups
                // the user already actively belongs to).
                try {
                    if (api.isAuthenticated()) {
                        JSONObject groups = api.get("/groups");
                        store.upsertGroups(groups.getJSONArray("groups"));
                    }
                } catch (Exception ignored) {
                    // handled by the syncNow() result below
                }
                return new SyncService(api, store).syncNow();
            }

            @Override
            protected void done() {
                try {
                    online = get();
                } catch (Exception e) {
                    online = false;
                }
                updateStatusLabel();
                loadFromCache();
            }
        }.execute();
    }

    private void updateStatusLabel() {
        try {
            int pending = store.getPendingOutboxCount();
            pendingLabel.setText(pending > 0 ? pending + " change(s) waiting to sync" : "");
        } catch (Exception ignored) {}

        statusLabel.setOpaque(true);
        statusLabel.setFont(statusLabel.getFont().deriveFont(Font.BOLD, 11f));
        statusLabel.setBorder(BorderFactory.createEmptyBorder(3, 9, 3, 9));
        if (online) {
            statusLabel.setBackground(Brand.SUCCESS_BG);
            statusLabel.setForeground(Brand.SUCCESS);
            statusLabel.setText("● Online");
        } else {
            statusLabel.setBackground(Brand.DANGER_BG);
            statusLabel.setForeground(Brand.DANGER);
            statusLabel.setText("● Offline - showing last synced data");
        }
    }

    public boolean isOnline() {
        return online;
    }

    private void logout() {
        api.logout();
        try {
            store.clearSession();
        } catch (Exception ignored) {}
        syncTimer.stop();
        dispose();
        SwingUtilities.invokeLater(() -> new LoginFrame(new ApiClient(), store).setVisible(true));
    }

    public static String formatInstant(String iso) {
        try {
            return DateTimeFormatter.ofPattern("dd MMM yyyy, HH:mm")
                    .withZone(ZoneId.systemDefault())
                    .format(Instant.parse(iso));
        } catch (Exception e) {
            return iso;
        }
    }
}
