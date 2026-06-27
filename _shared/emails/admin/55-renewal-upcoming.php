<?php
/**
 * Template 55 — Subscription Renewal Upcoming
 * Trigger:    Billing scheduler — 7 days before renewal date
 * Recipient:  Subscriber (Practitioner)
 * Gate:       notify_payment
 * Subject:    Your Aegis subscription renews in 7 days
 * Preheader:  Your {{plan_label}} plan renews on {{renewal_date}} for {{amount}}.
 * Merge tags: $data['practitioner_name'], $data['plan_label'], $data['amount'],
 *             $data['renewal_date'], $data['billing_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_payment';
$data['email_title'] ??= 'Your Aegis subscription renews soon';
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
                Your subscription renews in 7 days
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['practitioner_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                your Aegis
                <?php if (!empty($data['plan_label'])): ?>
                  <strong><?= htmlspecialchars($data['plan_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                subscription will automatically renew on
                <strong><?= htmlspecialchars($data['renewal_date'] ?? 'your renewal date', ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if (!empty($data['amount'])): ?>
                  for <strong><?= htmlspecialchars($data['amount'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>.
              </p>

              <!-- Renewal info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Renewal is automatic — no action is needed if your payment details
                      are current. If you wish to make changes to your plan or billing
                      information, please do so before the renewal date.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['billing_url'] ?? AEGIS_SITE_URL . '/billing', ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Manage billing
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                You will receive a payment receipt after your renewal is processed.
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
Your subscription renews in 7 days

Dear {{practitioner_name}},

Your Aegis {{plan_label}} subscription will automatically renew on {{renewal_date}} for {{amount}}.

No action is needed if your payment details are current. Make any changes before the renewal date.

Manage billing: {{billing_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
