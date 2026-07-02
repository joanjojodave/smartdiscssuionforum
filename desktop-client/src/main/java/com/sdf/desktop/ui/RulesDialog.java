package com.sdf.desktop.ui;

import org.json.JSONArray;
import org.json.JSONObject;

import javax.swing.*;
import java.awt.*;

/** Onboarding step: a pending member must read and accept the group rules before participating (requirement #5). */
public class RulesDialog extends JDialog {

    public RulesDialog(Window owner, MainFrame main, JSONObject group) {
        super(owner, "Group rules - " + group.getString("name"), ModalityType.APPLICATION_MODAL);
        setSize(480, 400);
        setLocationRelativeTo(owner);
        setLayout(new BorderLayout());

        JTextArea rulesArea = new JTextArea("Loading rules...");
        rulesArea.setEditable(false);
        rulesArea.setLineWrap(true);
        rulesArea.setWrapStyleWord(true);
        add(new JScrollPane(rulesArea), BorderLayout.CENTER);

        JButton agree = new JButton("I agree - join the group");
        JButton decline = new JButton("I decline");
        JPanel buttons = new JPanel(new FlowLayout(FlowLayout.CENTER));
        buttons.add(agree);
        buttons.add(decline);
        add(buttons, BorderLayout.SOUTH);
        agree.setEnabled(false);
        decline.setEnabled(false);

        new SwingWorker<String, Void>() {
            @Override
            protected String doInBackground() throws Exception {
                JSONObject response = main.api.get("/groups");
                JSONArray groups = response.getJSONArray("groups");
                for (int i = 0; i < groups.length(); i++) {
                    JSONObject g = groups.getJSONObject(i);
                    if (g.getInt("id") == group.getInt("id")) {
                        return g.optString("rules", "(no rules text provided)");
                    }
                }
                return "(rules not found)";
            }

            @Override
            protected void done() {
                try {
                    rulesArea.setText(get());
                    agree.setEnabled(true);
                    decline.setEnabled(true);
                } catch (Exception e) {
                    rulesArea.setText("Could not load rules: " + e.getMessage());
                }
            }
        }.execute();

        agree.addActionListener(e -> decide(main, group, "agree"));
        decline.addActionListener(e -> decide(main, group, "decline"));
    }

    private void decide(MainFrame main, JSONObject group, String decision) {
        try {
            JSONObject body = new JSONObject().put("decision", decision);
            JSONObject response = main.api.post("/groups/" + group.getInt("id") + "/rules", body);
            main.store.updateMyGroupStatus(group.getInt("id"), response.optString("status", decision));
            JOptionPane.showMessageDialog(this, decision.equals("agree")
                    ? "Welcome to the group!" : "You declined the group rules.");
            dispose();
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(this, "Could not submit decision: " + ex.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
        }
    }
}
