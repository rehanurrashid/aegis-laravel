<?php
/**
 * Template 11 (CS) — Plan Ready Notification — Continuity Steward variant
 * Trigger:    UC-PRV-036 / UC-XP-008 (fan-out after Practitioner signs)
 * Recipient:  Continuity Steward
 * Gate:       notify_plan_change
 * Subject:    A Continuity Plan is ready for your countersignature
 * Preheader:  {{practitioner_name}} has signed their plan. Your countersignature is required.
 * Merge tags: $data['cs_name'], $data['practitioner_name'], $data['plan_title'],
 *             $data['sign_url'], $data['unsubscribe_token'], $data['email_title']
 *
 * §A.3 correction: two role-targeted variants required (CS + SS).
 * This is the CS variant — CS must countersign; next action is the signing step.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_plan_change';
$data['email_title'] ??= 'A Continuity Plan is ready for your countersignature';
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
                A plan is ready for your countersignature
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['cs_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <?= htmlspecialchars($data['practitioner_name'] ?? 'Your practitioner', ENT_QUOTES, 'UTF-8') ?>
                has signed their Continuity Plan
                <?php if (!empty($data['plan_title'])): ?>
                  <strong><?= htmlspecialchars($data['plan_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                and designated you as their Continuity Steward.
                Your countersignature is required before the plan becomes fully active.
              </p>

              <!-- Next-action box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Please review the plan carefully before countersigning. Once countersigned,
                      you take on the responsibilities outlined in your Continuity Steward agreement.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['sign_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review and countersign
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you have questions about your responsibilities, contact
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
A plan is ready for your countersignature

Dear {{cs_name}},

{{practitioner_name}} has signed their Continuity Plan {{plan_title}} and designated you as their Continuity Steward. Your countersignature is required before the plan becomes fully active.

Please review the plan carefully before countersigning. Once countersigned, you take on the responsibilities outlined in your Continuity Steward agreement.

Review and countersign: {{sign_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
