<?php
/**
 * Template 40 — Payout Released
 * Trigger:    UC-ADM-045 / UC-XP-012 (Stripe payout released to BP)
 * Recipient:  Business Partner
 * Gate:       notify_payment
 * Subject:    Your payout has been released
 * Preheader:  {{payout_amount}} has been sent to your connected payout account.
 * Merge tags: $data['bp_name'], $data['payout_amount'], $data['payout_ref'],
 *             $data['released_at'], $data['portal_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_payment';
$data['email_title'] ??= 'Your payout has been released';
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
                Payout released
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['bp_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                your payout has been released
                <?php if (!empty($data['released_at'])): ?>
                  on <?= htmlspecialchars($data['released_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
                and is on its way to your connected payout account.
              </p>

              <!-- Payout details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <?php if (!empty($data['payout_amount'])): ?>
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Payout amount:</strong>
                      <?= htmlspecialchars($data['payout_amount'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($data['payout_ref'])): ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Reference:</strong>
                      <?= htmlspecialchars($data['payout_ref'], ENT_QUOTES, 'UTF-8') ?>
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
                    <a href="<?= htmlspecialchars($data['portal_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View payout history
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Arrival time depends on your banking provider. If funds do not arrive within
                5 business days, contact
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
Payout released

Dear {{bp_name}},

Your payout has been released on {{released_at}} and is on its way to your connected payout account.

Payout amount: {{payout_amount}}
Reference: {{payout_ref}}

View payout history: {{portal_url}}

If funds do not arrive within 5 business days, contact support@aegis.devlet.tech.

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
