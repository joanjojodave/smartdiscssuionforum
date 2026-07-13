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
        tabs.addTab("Dashboard", dashboardPanel);
        tabs.addTab("Groups & Discussions", groupsPanel);
        tabs.addTab("Group Chat", messagesPanel);
        tabs.addTab("Quizzes", quizzesPanel);
        tabs.addTab("Notifications", notificationsPanel);
        if ("admin".equals(user.optString("role"))) {
            tabs.addTab("Admin - Members", adminMembersPanel);
        }
        tabs.addTab("Profile", profilePanel);
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

    private JPanel buildStatusBar() {
        JPanel bar = new JPanel(new BorderLayout());
        bar.setBorder(BorderFactory.createEmptyBorder(4, 10, 4, 10));

        JPanel left = new JPanel(new FlowLayout(FlowLayout.LEFT, 10, 0));
        left.add(statusLabel);
        left.add(pendingLabel);

        JPanel right = new JPanel(new FlowLayout(FlowLayout.RIGHT, 10, 0));
        JButton syncNowButton = new JButton("Sync now");
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

        if (online) {
            statusLabel.setForeground(new Color(0, 128, 0));
            statusLabel.setText("● Online");
        } else {
            statusLabel.setForeground(Color.RED);
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
