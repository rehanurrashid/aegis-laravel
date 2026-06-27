<?php
/**
 * Template 31 — Incident Closed
 * Trigger:    UC-XP-007 (incident formally closed by CS or admin)
 * Recipient:  All parties — CS, SS, Practitioner (fan-out — recipient_name per recipient)
 * Gate:       [UNGATED]
 * Subject:    Continuity incident closed — {{incident_id}}
 * Preheader:  The active incident for {{practitioner_name}} has been formally closed.
 * Merge tags: $data['recipient_name'], $data['practitioner_name'], $data['incident_id'],
 *             $data['closed_at'], $data['closed_by'], $data['closure_note'],
 *             $data['incident_url'], $data['email_title'], $data['ungated']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']     = true;
$data['gate_key']    = '[UNGATED]';
$data['email_title'] ??= 'Continuity incident closed';
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
                Continuity incident closed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['recipient_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                the continuity incident for
                <strong><?= htmlspecialchars($data['practitioner_name'] ?? 'the practitioner', ENT_QUOTES, 'UTF-8') ?></strong>
                has been formally closed
                <?php if (!empty($data['closed_at'])): ?>
                  on <?= htmlspecialchars($data['closed_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
                <?php if (!empty($data['closed_by'])): ?>
                  by <?= htmlspecialchars($data['closed_by'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
              </p>

              <!-- Closure summary box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <?php if (!empty($data['incident_id'])): ?>
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Reference:</strong>
                      <?= htmlspecialchars($data['incident_id'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($data['closure_note'])): ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <?= htmlspecialchars($data['closure_note'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php else: ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      All active tasks have been resolved and vault access has been revoked.
                      A full incident record is available in the portal.
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
                      View incident record
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                The incident record is retained for audit purposes. Contact
                <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>
                if you have questions about this closure.
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
Continuity incident closed

Dear {{recipient_name}},

The continuity incident for {{practitioner_name}} has been formally closed on {{closed_at}} by {{closed_by}}.

Reference: {{incident_id}}
{{closure_note}}

All active tasks have been resolved and vault access has been revoked.

View incident record: {{incident_url}}

© {{year}} Aegis · A MA'AT product
*/
?>
