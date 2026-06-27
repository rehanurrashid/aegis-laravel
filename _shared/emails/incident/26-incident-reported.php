<?php
/**
 * Template 26 — Critical Incident Reported
 * Trigger:    UC-PRV-090 / UC-SS-030 / UC-XP-004 (incident reported by SS or system)
 * Recipient:  Continuity Steward(s)
 * Gate:       [UNGATED]
 * Subject:    Action required — Critical incident reported for {{practitioner_name}}
 * Preheader:  {{ss_name}} has reported a {{incident_type}}. Please review and verify immediately.
 * Merge tags: $data['cs_name'], $data['practitioner_name'], $data['ss_name'],
 *             $data['incident_type'], $data['incident_time'], $data['incident_id'],
 *             $data['verify_url'], $data['email_title'], $data['ungated']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']     = true;
$data['gate_key']    = '[UNGATED]';
$data['email_title'] ??= 'Action required — Critical incident reported';
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
                Action required — Critical incident reported
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['cs_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                a critical incident has been reported for
                <strong><?= htmlspecialchars($data['practitioner_name'] ?? 'your practitioner', ENT_QUOTES, 'UTF-8') ?></strong>
                by
                <?= htmlspecialchars($data['ss_name'] ?? 'a Support Steward', ENT_QUOTES, 'UTF-8') ?>.
                As the Continuity Steward, you are required to verify this incident before
                the plan can be activated.
              </p>

              <!-- Incident details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fdf2f2;border-left:3px solid #c0392b;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <?php if (!empty($data['incident_type'])): ?>
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Incident type:</strong>
                      <?= htmlspecialchars($data['incident_type'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($data['incident_time'])): ?>
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Reported:</strong>
                      <?= htmlspecialchars($data['incident_time'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($data['incident_id'])): ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Reference:</strong>
                      <?= htmlspecialchars($data['incident_id'], ENT_QUOTES, 'UTF-8') ?>
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
                    <a href="<?= htmlspecialchars($data['verify_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review and verify incident
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you are unable to take action, contact
                <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>
                immediately.
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
Action required — Critical incident reported

Dear {{cs_name}},

A critical incident has been reported for {{practitioner_name}} by {{ss_name}}. As the Continuity Steward, you are required to verify this incident before the plan can be activated.

Incident type: {{incident_type}}
Reported: {{incident_time}}
Reference: {{incident_id}}

Review and verify incident: {{verify_url}}

If you are unable to take action, contact support@aegis.devlet.tech immediately.

© {{year}} Aegis · A MA'AT product
*/
?>
