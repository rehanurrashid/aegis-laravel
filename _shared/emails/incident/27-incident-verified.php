<?php
/**
 * Template 27 — Critical Incident Verified by CS
 * Trigger:    UC-CS-041 / UC-XP-005 (CS verifies the incident)
 * Recipient:  SS + Practitioner (fan-out — recipient_name resolves per recipient)
 * Gate:       [UNGATED]
 * Subject:    Critical incident verified — continuity plan is now active
 * Preheader:  {{cs_name}} has verified the incident. The Continuity Plan is now in effect.
 * Merge tags: $data['recipient_name'], $data['cs_name'], $data['practitioner_name'],
 *             $data['incident_id'], $data['verified_at'], $data['incident_url'],
 *             $data['email_title'], $data['ungated']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']     = true;
$data['gate_key']    = '[UNGATED]';
$data['email_title'] ??= 'Critical incident verified — plan now active';
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
                Incident verified — Continuity Plan now active
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['recipient_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <?= htmlspecialchars($data['cs_name'] ?? 'The Continuity Steward', ENT_QUOTES, 'UTF-8') ?>
                has verified the critical incident reported for
                <strong><?= htmlspecialchars($data['practitioner_name'] ?? 'the practitioner', ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if (!empty($data['verified_at'])): ?>
                  on <?= htmlspecialchars($data['verified_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                The Continuity Plan is now in effect.
              </p>

              <!-- Status box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      The plan is now guiding all continuity activity. Tasks have been
                      assigned and the relevant parties have been notified.
                    </p>
                    <?php if (!empty($data['incident_id'])): ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;color:#8a8378;line-height:1.5;">
                      Reference: <?= htmlspecialchars($data['incident_id'], ENT_QUOTES, 'UTF-8') ?>
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
                    <a href="<?= htmlspecialchars($data['incident_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View incident details
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Contact <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>
                if you have questions about next steps.
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
Incident verified — Continuity Plan now active

Dear {{recipient_name}},

{{cs_name}} has verified the critical incident reported for {{practitioner_name}} on {{verified_at}}. The Continuity Plan is now in effect.

The plan is now guiding all continuity activity. Tasks have been assigned and the relevant parties have been notified.

Reference: {{incident_id}}

View incident details: {{incident_url}}

© {{year}} Aegis · A MA'AT product
*/
?>
