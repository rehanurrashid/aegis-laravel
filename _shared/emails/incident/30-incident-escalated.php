<?php
/**
 * Template 30 — Incident Escalation Requested
 * Trigger:    Incident oversight escalation → Aegis admin team
 * Recipient:  Aegis support/admin team (internal)
 * Gate:       [UNGATED]
 * Subject:    Incident escalation requested — {{incident_id}}
 * Preheader:  An active incident for {{practitioner_name}} has been escalated for oversight.
 * Merge tags: $data['practitioner_name'], $data['cs_name'], $data['incident_id'],
 *             $data['escalated_by'], $data['escalation_reason'], $data['escalated_at'],
 *             $data['admin_url'], $data['email_title'], $data['ungated']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']     = true;
$data['gate_key']    = '[UNGATED]';
$data['email_title'] ??= 'Incident escalation requested';
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
                Incident escalation requires oversight
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                An active incident has been escalated to the Aegis team for oversight.
                Please review the incident record and take appropriate action.
              </p>

              <!-- Escalation details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fdf2f2;border-left:3px solid #c0392b;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <?php if (!empty($data['incident_id'])): ?>
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Reference:</strong>
                      <?= htmlspecialchars($data['incident_id'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Practitioner:</strong>
                      <?= htmlspecialchars($data['practitioner_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php if (!empty($data['cs_name'])): ?>
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Continuity Steward:</strong>
                      <?= htmlspecialchars($data['cs_name'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($data['escalated_by'])): ?>
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Escalated by:</strong>
                      <?= htmlspecialchars($data['escalated_by'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($data['escalated_at'])): ?>
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Escalated:</strong>
                      <?= htmlspecialchars($data['escalated_at'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($data['escalation_reason'])): ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Reason:</strong>
                      <?= htmlspecialchars($data['escalation_reason'], ENT_QUOTES, 'UTF-8') ?>
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
                    <a href="<?= htmlspecialchars($data['admin_url'] ?? AEGIS_SITE_URL . '/admin', ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review in admin portal
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                This is an internal Aegis notification. Do not forward to external parties.
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
Incident escalation requires oversight

An active incident has been escalated to the Aegis team for oversight.

Reference: {{incident_id}}
Practitioner: {{practitioner_name}}
Continuity Steward: {{cs_name}}
Escalated by: {{escalated_by}}
Escalated: {{escalated_at}}
Reason: {{escalation_reason}}

Review in admin portal: {{admin_url}}

This is an internal Aegis notification. Do not forward to external parties.

© {{year}} Aegis · A MA'AT product
*/
?>
