<?php
/**
 * Template 48 — Support Ticket Resolved
 * Trigger:    UC-ADM-055 (admin marks ticket as resolved)
 * Recipient:  Ticket submitter
 * Gate:       [UNGATED]
 * Subject:    Your support ticket #{{ticket_id}} has been resolved
 * Preheader:  We have marked your ticket as resolved. Let us know if you need anything else.
 * Merge tags: $data['recipient_name'], $data['ticket_id'], $data['ticket_subject'],
 *             $data['resolved_at'], $data['resolution_note'], $data['ticket_url'],
 *             $data['email_title'], $data['ungated']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']     = true;
$data['gate_key']    = '[UNGATED]';
$data['email_title'] ??= 'Your support ticket has been resolved';
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
                Your support ticket has been resolved
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['recipient_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                your support ticket
                <?php if (!empty($data['ticket_id'])): ?>
                  <strong>#<?= htmlspecialchars($data['ticket_id'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                has been marked as resolved
                <?php if (!empty($data['resolved_at'])): ?>
                  on <?= htmlspecialchars($data['resolved_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
              </p>

              <!-- Resolution box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <?php if (!empty($data['resolution_note'])): ?>
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      Resolution summary
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <?= htmlspecialchars($data['resolution_note'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php else: ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      If your issue is not fully resolved, you can reopen this ticket or
                      submit a new request from your portal.
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
                    <a href="<?= htmlspecialchars($data['ticket_url'] ?? AEGIS_SITE_URL . '/support', ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View ticket
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you need further assistance, contact
                <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>.
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
Your support ticket has been resolved

Dear {{recipient_name}},

Your support ticket #{{ticket_id}} has been marked as resolved on {{resolved_at}}.

Resolution: {{resolution_note}}

If your issue is not fully resolved, you can reopen this ticket or submit a new request.

View ticket: {{ticket_url}}

© {{year}} Aegis · A MA'AT product
*/
?>
