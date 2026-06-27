<?php
/**
 * Template 08 — Account Locked Notice
 * Trigger:    UC-PRV-002 lockout (too many failed attempts) / UC-ADM-023 (admin lock)
 * Recipient:  User (any role)
 * Gate:       [UNGATED]
 * Subject:    Your Aegis account has been locked
 * Preheader:  Access to your account is temporarily suspended. Here is how to regain access.
 * Merge tags: $data['recipient_name'], $data['lock_reason'], $data['locked_at'],
 *             $data['unlock_url'], $data['support_url'],
 *             $data['email_title'], $data['ungated']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']     = true;
$data['gate_key']    = '[UNGATED]';
$data['email_title'] ??= 'Your Aegis account has been locked';
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
                Account access suspended
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['recipient_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                your Aegis account has been locked
                <?php if (!empty($data['locked_at'])): ?>
                  on <?= htmlspecialchars($data['locked_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                <?php if (!empty($data['lock_reason'])): ?>
                  Reason: <?= htmlspecialchars($data['lock_reason'], ENT_QUOTES, 'UTF-8') ?>.
                <?php endif; ?>
              </p>

              <!-- Alert box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fdf2f2;border-left:3px solid #c0392b;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      While your account is locked, no one — including you — can access it.
                      Your data and continuity plan remain safe and unchanged.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['unlock_url'] ?? ($data['support_url'] ?? AEGIS_SITE_URL . '/support'), ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Request account unlock
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                You can also reach our support team at
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
Account access suspended

Dear {{recipient_name}},

Your Aegis account has been locked on {{locked_at}}. Reason: {{lock_reason}}.

While your account is locked, no one can access it. Your data remains safe.

Request account unlock: {{unlock_url}}

Contact support at support@aegis.devlet.tech.

© {{year}} Aegis · A MA'AT product
*/
?>
