<?php
/**
 * Template 11 (SS) — Plan Ready Notification — Support Steward variant
 * Trigger:    UC-PRV-036 / UC-XP-008 (fan-out after Practitioner signs)
 * Recipient:  Support Steward
 * Gate:       notify_plan_change
 * Subject:    You have been included in a Continuity Plan
 * Preheader:  {{practitioner_name}} has activated their plan. Review your standby role.
 * Merge tags: $data['ss_name'], $data['practitioner_name'], $data['plan_title'],
 *             $data['plan_url'], $data['unsubscribe_token'], $data['email_title']
 *
 * §A.3 correction: two role-targeted variants required (CS + SS).
 * This is the SS variant — SS is notified for awareness; no countersign required.
 * Next action is to review the plan and understand standby responsibilities.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_plan_change';
$data['email_title'] ??= 'You have been included in a Continuity Plan';
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
                You have been included in a Continuity Plan
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['ss_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <?= htmlspecialchars($data['practitioner_name'] ?? 'A practitioner', ENT_QUOTES, 'UTF-8') ?>
                has signed and activated their Continuity Plan
                <?php if (!empty($data['plan_title'])): ?>
                  <strong><?= htmlspecialchars($data['plan_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>,
                in which you are designated as a Support Steward.
              </p>

              <!-- Standby role info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      As a Support Steward, you play a standby role in this plan. No immediate
                      action is required. You will be notified if a critical incident is reported
                      and your involvement is needed.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['plan_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review the plan
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Familiarizing yourself with the plan now means you can act quickly if you are
                ever called upon.
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
You have been included in a Continuity Plan

Dear {{ss_name}},

{{practitioner_name}} has signed and activated their Continuity Plan {{plan_title}}, in which you are designated as a Support Steward.

As a Support Steward, you play a standby role in this plan. No immediate action is required. You will be notified if a critical incident is reported and your involvement is needed.

Review the plan: {{plan_url}}

Familiarizing yourself with the plan now means you can act quickly if you are ever called upon.

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
