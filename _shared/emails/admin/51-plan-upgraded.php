<?php
/**
 * Template 51 — Plan Upgraded
 * Trigger:    UC-PRV-003 (Practitioner upgrades subscription tier)
 * Recipient:  Practitioner
 * Gate:       notify_payment
 * Subject:    Your Aegis plan has been upgraded
 * Preheader:  Welcome to {{new_tier_label}}. Your new features are available now.
 * Merge tags: $data['practitioner_name'], $data['old_tier_label'], $data['new_tier_label'],
 *             $data['upgraded_at'], $data['portal_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
require_once __DIR__ . '/../../pricing_data.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_payment';
$data['email_title'] ??= 'Your Aegis plan has been upgraded';
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
                Your plan has been upgraded
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['practitioner_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                your Aegis subscription has been upgraded
                <?php if (!empty($data['old_tier_label']) && !empty($data['new_tier_label'])): ?>
                  from <strong><?= htmlspecialchars($data['old_tier_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                  to <strong><?= htmlspecialchars($data['new_tier_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php elseif (!empty($data['new_tier_label'])): ?>
                  to <strong><?= htmlspecialchars($data['new_tier_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                <?php if (!empty($data['upgraded_at'])): ?>
                  on <?= htmlspecialchars($data['upgraded_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                Your new features are available immediately.
              </p>

              <!-- Upgrade confirmation box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Your billing cycle has been updated. A payment receipt has been sent
                      separately. Visit your portal to explore everything included in your
                      new plan.
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
                      Go to my portal
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Questions about your plan? Contact
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
Your plan has been upgraded

Dear {{practitioner_name}},

Your Aegis subscription has been upgraded from {{old_tier_label}} to {{new_tier_label}} on {{upgraded_at}}. Your new features are available immediately.

A payment receipt has been sent separately.

Go to my portal: {{portal_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
