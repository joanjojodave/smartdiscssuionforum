package com.sdf.desktop.ui;

import org.json.JSONArray;
import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;
import java.util.HashMap;
import java.util.Map;

/**
 * Lockdown quiz-taking interface (requirement #10). The countdown is purely
 * a display of what the server already computed (seconds_remaining from
 * start_at + duration_minutes) -- the server re-validates on submit, so a
 * clock-skewed or tampered client clock cannot grant extra time.
 */
public class QuizAttemptDialog extends JDialog {

    private final MainFrame main;
    private final int quizId;
    private final Map<Integer, ButtonGroup> answerGroups = new HashMap<>();
    private final Map<Integer, Map<String, JRadioButton>> optionButtons = new HashMap<>();
    private javax.swing.Timer countdownTimer;
    private int secondsRemaining;
    private final JLabel timerLabel = new JLabel("--:--", SwingConstants.CENTER);
    private boolean submitted = false;

    public QuizAttemptDialog(Window owner, MainFrame main, int quizId) {
        super(owner, "Quiz", ModalityType.APPLICATION_MODAL);
        this.main = main;
        this.quizId = quizId;
        setSize(600, 520);
        setLocationRelativeTo(owner);
        setDefaultCloseOperation(JDialog.DO_NOTHING_ON_CLOSE);
        addWindowListener(new java.awt.event.WindowAdapter() {
            @Override
            public void windowClosing(java.awt.event.WindowEvent e) {
                if (!submitted) {
                    int r = JOptionPane.showConfirmDialog(QuizAttemptDialog.this,
                            "The quiz is still in progress -- your timer keeps running even if you leave. Close anyway?",
                            "Quiz in progress", JOptionPane.YES_NO_OPTION);
                    if (r != JOptionPane.YES_OPTION) return;
                }
                if (countdownTimer != null) countdownTimer.stop();
                dispose();
            }
        });

        setLayout(new BorderLayout(6, 6));
        JPanel loading = new JPanel(new BorderLayout());
        loading.add(new JLabel("Loading quiz...", SwingConstants.CENTER), BorderLayout.CENTER);
        add(loading, BorderLayout.CENTER);

        loadAndStart();
    }

    private void loadAndStart() {
        new SwingWorker<JSONObject, Void>() {
            Exception error;

            @Override
            protected JSONObject doInBackground() {
                try {
                    JSONObject state = main.api.get("/quizzes/" + quizId);
                    if (state.isNull("attempt_status")) {
                        main.api.post("/quizzes/" + quizId + "/start", new JSONObject());
                        state = main.api.get("/quizzes/" + quizId);
                    }
                    return state;
                } catch (Exception e) {
                    error = e;
                    return null;
                }
            }

            @Override
            protected void done() {
                if (error != null) {
                    JOptionPane.showMessageDialog(QuizAttemptDialog.this, "Could not open quiz: " + error.getMessage());
                    dispose();
                    return;
                }
                try {
                    render(get());
                } catch (Exception e) {
                    JOptionPane.showMessageDialog(QuizAttemptDialog.this, "Error: " + e.getMessage());
                    dispose();
                }
            }
        }.execute();
    }

    private void render(JSONObject state) {
        getContentPane().removeAll();

        String attemptStatus = state.optString("attempt_status", null);
        if (!"in_progress".equals(attemptStatus)) {
            JPanel done = new JPanel(new BorderLayout());
            String msg = attemptStatus == null
                    ? "Could not start attempt."
                    : "You already " + ("auto_submitted".equals(attemptStatus) ? "had this quiz auto-submitted" : "submitted this quiz") + ".";
            done.add(new JLabel(msg, SwingConstants.CENTER), BorderLayout.CENTER);
            JButton close = new JButton("Close");
            close.addActionListener(e -> dispose());
            done.add(close, BorderLayout.SOUTH);
            add(done, BorderLayout.CENTER);
            revalidate();
            repaint();
            return;
        }

        secondsRemaining = state.optInt("seconds_remaining", 0);

        JPanel header = new JPanel(new BorderLayout());
        header.setBorder(BorderFactory.createEmptyBorder(8, 8, 8, 8));
        header.add(new JLabel("🔒 Lockdown mode - " + state.getString("title")), BorderLayout.WEST);
        timerLabel.setFont(timerLabel.getFont().deriveFont(Font.BOLD, 18f));
        header.add(timerLabel, BorderLayout.EAST);
        add(header, BorderLayout.NORTH);

        JPanel questionsPanel = new JPanel();
        questionsPanel.setLayout(new BoxLayout(questionsPanel, BoxLayout.Y_AXIS));

        JSONArray questions = state.optJSONArray("questions");
        if (questions != null) {
            for (int i = 0; i < questions.length(); i++) {
                JSONObject q = questions.getJSONObject(i);
                questionsPanel.add(buildQuestionCard(i + 1, q));
            }
        }
        add(new JScrollPane(questionsPanel), BorderLayout.CENTER);

        JButton submitButton = new JButton("Submit quiz");
        submitButton.addActionListener(e -> submit());
        JPanel south = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        south.add(submitButton);
        add(south, BorderLayout.SOUTH);

        revalidate();
        repaint();

        countdownTimer = new javax.swing.Timer(1000, e -> tick());
        countdownTimer.start();
        tick();
    }

    private JPanel buildQuestionCard(int number, JSONObject question) {
        JPanel card = new JPanel();
        card.setLayout(new BoxLayout(card, BoxLayout.Y_AXIS));
        card.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createEmptyBorder(4, 8, 4, 8),
                BorderFactory.createTitledBorder(number + ". (" + question.getInt("marks") + " mark(s))")));

        JLabel text = new JLabel("<html>" + question.getString("text") + "</html>");
        card.add(text);

        int questionId = question.getInt("id");
        ButtonGroup group = new ButtonGroup();
        Map<String, JRadioButton> buttons = new HashMap<>();
        JSONObject options = question.getJSONObject("options");
        for (String key : options.keySet()) {
            JRadioButton rb = new JRadioButton(key + ". " + options.getString(key));
            group.add(rb);
            buttons.put(key, rb);
            card.add(rb);
        }
        answerGroups.put(questionId, group);
        optionButtons.put(questionId, buttons);
        return card;
    }

    private void tick() {
        if (secondsRemaining <= 0) {
            countdownTimer.stop();
            timerLabel.setText("00:00");
            JOptionPane.showMessageDialog(this, "Time is up! Submitting automatically.");
            submit();
            return;
        }
        int m = secondsRemaining / 60;
        int s = secondsRemaining % 60;
        timerLabel.setText(String.format("%02d:%02d", m, s));
        timerLabel.setForeground(secondsRemaining < 60 ? Color.RED : Color.BLACK);
        secondsRemaining--;
    }

    private void submit() {
        if (submitted) return;
        submitted = true;
        if (countdownTimer != null) countdownTimer.stop();

        JSONObject answers = new JSONObject();
        for (Map.Entry<Integer, Map<String, JRadioButton>> entry : optionButtons.entrySet()) {
            for (Map.Entry<String, JRadioButton> opt : entry.getValue().entrySet()) {
                if (opt.getValue().isSelected()) {
                    answers.put(String.valueOf(entry.getKey()), opt.getKey());
                }
            }
        }

        new SwingWorker<JSONObject, Void>() {
            Exception error;

            @Override
            protected JSONObject doInBackground() {
                try {
                    JSONObject body = new JSONObject();
                    body.put("answers", answers);
                    return main.api.post("/quizzes/" + quizId + "/submit", body);
                } catch (Exception e) {
                    error = e;
                    return null;
                }
            }

            @Override
            protected void done() {
                if (error != null) {
                    JOptionPane.showMessageDialog(QuizAttemptDialog.this, "Could not submit: " + error.getMessage()
                            + "\nYour answers were NOT saved -- please reopen the quiz once you're back online.");
                    submitted = false;
                    return;
                }
                try {
                    JSONObject result = get();
                    JOptionPane.showMessageDialog(QuizAttemptDialog.this, "Submitted. Score: " + result.optDouble("score", 0));
                } catch (Exception ignored) {}
                dispose();
            }
        }.execute();
    }
}
