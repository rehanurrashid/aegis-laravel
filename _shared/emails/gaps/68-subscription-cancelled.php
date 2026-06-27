<?php
/**
 * Template 68 — Subscription Cancelled (Confirmation)
 * Trigger:    UC-PRV-145 (cancel_subscription — Practitioner cancels their plan)
 * Recipient:  Practitioner
 * Gate:       [UNGATED] (billing confirmation — always sends)
 * Subject:    Your Aegis subscription has been cancelled
 * Preheader:  Your {{tier_label}} plan ends {{access_until_date}}. Your data remains safe.
 * Merge tags: $data['practitioner_name'], $data['tier_label'], $data['access_until_date'],
 *             $data['reactivate_url'], $data['email_title'], $data['ungated']
 *
 * NOTE: Distinct from downgrade (T52). Cancellation ends the subscription entirely.
 * Access continues through the paid period; data is retained per retention policy.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']     = true;
$data['gate_key']    = '[UNGATED]';
$data['email_title'] ??= 'Your Aegis subscription has been cancelled';
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
                Your subscription has been cancelled
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['practitioner_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                your Aegis
                <?php if (!empty($data['tier_label'])): ?>
                  <strong><?= htmlspecialchars($data['tier_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                subscription has been cancelled. We are sorry to see you go.
              </p>

              <!-- Access and data info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <?php if (!empty($data['access_until_date'])): ?>
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Access continues until:</strong>
                      <?= htmlspecialchars($data['access_until_date'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Your data, plan records, and documents remain safe and accessible
                      until your paid period ends. After that, your data is retained
                      per our retention policy before being permanently deleted.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['reactivate_url'] ?? AEGIS_SITE_URL . '/billing', ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Reactivate my subscription
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you cancelled by mistake or change your mind, you can reactivate
                at any time before your access period ends.
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
Your subscription has been cancelled

Dear {{practitioner_name}},

Your Aegis {{tier_label}} subscription has been cancelled. We are sorry to see you go.

Access continues until: {{access_until_date}}
Your data remains safe and accessible until your paid period ends.

Reactivate my subscription: {{reactivate_url}}

If you cancelled by mistake, you can reactivate before your access period ends.

© {{year}} Aegis · A MA'AT product
*/
?>
