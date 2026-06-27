<?php
/**
 * Template 52 — Plan Downgraded
 * Trigger:    UC-PRV-004 / UC-XP-020 (Practitioner downgrades subscription tier)
 * Recipient:  Practitioner
 * Gate:       notify_payment
 * Subject:    Your Aegis plan will be downgraded at your next renewal
 * Preheader:  Your {{old_tier_label}} plan continues until {{downgrade_effective_date}}.
 * Merge tags: $data['practitioner_name'], $data['old_tier_label'], $data['new_tier_label'],
 *             $data['downgrade_effective_date'], $data['feature_loss_summary'],
 *             $data['portal_url'], $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
require_once __DIR__ . '/../../pricing_data.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_payment';
$data['email_title'] ??= 'Your Aegis plan will be downgraded';
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
                Your plan change is scheduled
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['practitioner_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                you have requested a plan change
                <?php if (!empty($data['old_tier_label']) && !empty($data['new_tier_label'])): ?>
                  from <strong><?= htmlspecialchars($data['old_tier_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                  to <strong><?= htmlspecialchars($data['new_tier_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>.
                Your current plan remains active until your billing period ends
                <?php if (!empty($data['downgrade_effective_date'])): ?>
                  on <strong><?= htmlspecialchars($data['downgrade_effective_date'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>.
              </p>

              <!-- Impact info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <?php if (!empty($data['feature_loss_summary'])): ?>
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      Features affected at renewal
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <?= htmlspecialchars($data['feature_loss_summary'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php else: ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Some features available on your current plan may not be available after
                      the change takes effect. Review your portal for details.
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
                      Manage my plan
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                You can reverse this change before your billing period ends by upgrading
                from your portal.
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
Your plan change is scheduled

Dear {{practitioner_name}},

You have requested a plan change from {{old_tier_label}} to {{new_tier_label}}. Your current plan remains active until {{downgrade_effective_date}}.

Features affected at renewal: {{feature_loss_summary}}

You can reverse this change before your billing period ends by upgrading from your portal.

Manage my plan: {{portal_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
