package com.sdf.desktop.ui;

import org.json.JSONArray;
import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;

public class QuizReportDialog extends JDialog {

    public QuizReportDialog(Window owner, MainFrame main, int quizId) {
        super(owner, "Quiz report", ModalityType.APPLICATION_MODAL);
        setSize(480, 400);
        setLocationRelativeTo(owner);
        setLayout(new BorderLayout());

        String[] columns = {"Student", "Status", "Score"};
        ReadOnlyTableModel model = new ReadOnlyTableModel(columns, 0);
        JTable table = new JTable(model);
        add(new JScrollPane(table), BorderLayout.CENTER);

        new SwingWorker<JSONObject, Void>() {
            Exception error;

            @Override
            protected JSONObject doInBackground() {
                try {
                    return main.api.get("/quizzes/" + quizId + "/report");
                } catch (Exception e) {
                    error = e;
                    return null;
                }
            }

            @Override
            protected void done() {
                if (error != null) {
                    JOptionPane.showMessageDialog(QuizReportDialog.this, "Could not load report: " + error.getMessage());
                    return;
                }
                try {
                    JSONObject report = get();
                    setTitle("Report - " + report.getJSONObject("quiz").getString("title"));
                    int totalMarks = report.getInt("total_marks");
                    JSONArray attempts = report.getJSONArray("attempts");
                    for (int i = 0; i < attempts.length(); i++) {
                        JSONObject a = attempts.getJSONObject(i);
                        model.addRow(new Object[]{
                                a.getString("user"),
                                a.getString("status"),
                                a.getDouble("score") + " / " + totalMarks
                        });
                    }
                } catch (Exception e) {
                    JOptionPane.showMessageDialog(QuizReportDialog.this, "Error: " + e.getMessage());
                }
            }
        }.execute();
    }

    private static class ReadOnlyTableModel extends javax.swing.table.DefaultTableModel {
        ReadOnlyTableModel(String[] columns, int rows) {
            super(columns, rows);
        }

        @Override
        public boolean isCellEditable(int row, int column) {
            return false;
        }
    }
}
