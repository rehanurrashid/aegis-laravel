<?php
/**
 * Template 09 — Account Closure Confirmation
 * Trigger:    UC-ADM-027 (admin-initiated closure) / self-close request
 * Recipient:  User (any role)
 * Gate:       [UNGATED]
 * Subject:    Your Aegis account has been closed
 * Preheader:  Account closure confirmed. Your data will be retained per our retention policy.
 * Merge tags: $data['recipient_name'], $data['closed_at'], $data['data_retention_days'],
 *             $data['export_url'], $data['reopen_url'],
 *             $data['email_title'], $data['ungated']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']     = true;
$data['gate_key']    = '[UNGATED]';
$data['email_title'] ??= 'Your Aegis account has been closed';
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
                Account closure confirmed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['recipient_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                your Aegis account has been closed
                <?php if (!empty($data['closed_at'])): ?>
                  on <?= htmlspecialchars($data['closed_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                We are sorry to see you go.
              </p>

              <!-- Retention info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Your data will be retained for
                      <?= htmlspecialchars((string)($data['data_retention_days'] ?? '30'), ENT_QUOTES, 'UTF-8') ?>
                      days in accordance with our data retention policy, after which it will be
                      permanently deleted. You may export your data before that window closes.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <?php if (!empty($data['export_url'])): ?>
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['export_url'], ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Export my data
                    </a>
                  </td>
                </tr>
              </table>
              <?php endif; ?>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you believe this closure was made in error, contact us at
                <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>.
                <?php if (!empty($data['reopen_url'])): ?>
                  You may also
                  <a href="<?= htmlspecialchars($data['reopen_url'], ENT_QUOTES, 'UTF-8') ?>"
                     style="color:#8a8378;">request account reactivation</a>.
                <?php endif; ?>
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
Account closure confirmed

Dear {{recipient_name}},

Your Aegis account has been closed on {{closed_at}}. We are sorry to see you go.

Your data will be retained for {{data_retention_days}} days, after which it will be permanently deleted. You may export your data before that window closes.

Export my data: {{export_url}}

If this closure was made in error, contact support@aegis.devlet.tech or visit: {{reopen_url}}

© {{year}} Aegis · A MA'AT product
*/
?>
