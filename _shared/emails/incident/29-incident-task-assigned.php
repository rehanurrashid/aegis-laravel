<?php
/**
 * Template 29 — Incident Task Assigned
 * Trigger:    UC-CS-031 (task assigned to CS during active incident)
 * Recipient:  Continuity Steward
 * Gate:       notify_task
 * Subject:    A task has been assigned to you — {{task_title}}
 * Preheader:  A new task requires your attention on {{practitioner_name}}'s incident.
 * Merge tags: $data['cs_name'], $data['practitioner_name'], $data['task_title'],
 *             $data['task_due_date'], $data['task_note'], $data['incident_id'],
 *             $data['task_url'], $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_task';
$data['email_title'] ??= 'Incident task assigned to you';
?>
<?php include __DIR__ . '/../_email_head.php'; ?>

<body style="margin:0;padding:0;background-color:#fffdf7;font-family:'Georgia','Times New Roman',serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
         style="background-color:#fffdf7;">
    <tr>
      <td align="center" style="padding:32px 16px;">

        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;
                      border:1px solid #e4dfd7;">

          <!-- Header -->
          <tr>
            <td style="padding:28px 40px 20px;border-bottom:1px solid #e4dfd7;">
              <span style="font-family:'Georgia','Times New Roman',serif;font-size:22px;
                           font-weight:700;color:#2d2a26;letter-spacing:-0.3px;">Aegis</span>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:32px 40px;">

              <h1 style="margin:0 0 16px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                A task has been assigned to you
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['cs_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                a task has been assigned to you as part of the active continuity response
                for
                <strong><?= htmlspecialchars($data['practitioner_name'] ?? 'your practitioner', ENT_QUOTES, 'UTF-8') ?></strong>.
              </p>

              <!-- Task details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;font-weight:600;color:#2d2a26;line-height:1.4;">
                      <?= htmlspecialchars($data['task_title'] ?? 'Assigned task', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php if (!empty($data['task_due_date'])): ?>
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Due:</strong>
                      <?= htmlspecialchars($data['task_due_date'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($data['task_note'])): ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <?= htmlspecialchars($data['task_note'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['task_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View task
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                <?php if (!empty($data['incident_id'])): ?>
                  Incident reference: <?= htmlspecialchars($data['incident_id'], ENT_QUOTES, 'UTF-8') ?>.
                <?php endif; ?>
                All task updates are logged against the incident record.
              </p>

            </td>
          </tr>

          <?php include __DIR__ . '/../_email_foot.php'; ?>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>

<?php
/*
Plain text version:
---
A task has been assigned to you

Dear {{cs_name}},

A task has been assigned to you as part of the active continuity response for {{practitioner_name}}.

Task: {{task_title}}
Due: {{task_due_date}}
Note: {{task_note}}

View task: {{task_url}}

Incident reference: {{incident_id}}. All task updates are logged against the incident record.

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
