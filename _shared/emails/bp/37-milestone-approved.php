<?php
/**
 * Template 37 — Milestone Approved
 * Trigger:    UC-PRV-135 (Practitioner approves milestone)
 * Recipient:  Business Partner
 * Gate:       notify_payment
 * Subject:    Your milestone has been approved — payment is being processed
 * Preheader:  {{practitioner_name}} has approved {{milestone_title}}. Payment is on its way.
 * Merge tags: $data['bp_name'], $data['practitioner_name'], $data['milestone_title'],
 *             $data['approved_at'], $data['payout_eta'], $data['portal_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_payment';
$data['email_title'] ??= 'Your milestone has been approved';
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
                Milestone approved — payment processing
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['bp_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <?= htmlspecialchars($data['practitioner_name'] ?? 'The practitioner', ENT_QUOTES, 'UTF-8') ?>
                has approved your milestone
                <?php if (!empty($data['milestone_title'])): ?>
                  <strong><?= htmlspecialchars($data['milestone_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                <?php if (!empty($data['approved_at'])): ?>
                  on <?= htmlspecialchars($data['approved_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                Payment is being processed.
              </p>

              <!-- Payment info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <?php if (!empty($data['payout_eta'])): ?>
                        Estimated payout date:
                        <strong><?= htmlspecialchars($data['payout_eta'], ENT_QUOTES, 'UTF-8') ?></strong>.
                      <?php else: ?>
                        You will receive a separate notification once the payout has been released.
                      <?php endif; ?>
                      Funds are transferred to your connected payout account via Stripe.
                    </p>
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
                      View payment status
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Payment timelines depend on your Stripe Connect account and banking provider.
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
Milestone approved — payment processing

Dear {{bp_name}},

{{practitioner_name}} has approved your milestone {{milestone_title}} on {{approved_at}}. Payment is being processed.

Estimated payout date: {{payout_eta}}. Funds are transferred to your connected payout account via Stripe.

View payment status: {{portal_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
