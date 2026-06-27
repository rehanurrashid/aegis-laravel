<?php
/**
 * Template 25 — Alternate CS Activated
 * Trigger:    UC-XP-019 (primary CS resigns or is formally replaced; alternate steps in)
 * Recipient:  Practitioner + Alternate CS (fan-out — recipient_name resolves per recipient)
 * Gate:       notify_activation
 * Subject:    Your Alternate Continuity Steward has been activated
 * Preheader:  {{alternate_cs_name}} is now your active Continuity Steward.
 * Merge tags: $data['recipient_name'], $data['practitioner_name'], $data['alternate_cs_name'],
 *             $data['former_cs_name'], $data['activated_at'], $data['plan_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 *
 * §A.3/§A.4 note: This template covers the activation event only (UC-XP-019).
 * The CS-unresponsive WARNING (UC-XP-016) is Template 65 — a separate, earlier event.
 * Do not conflate.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_activation';
$data['email_title'] ??= 'Your Alternate Continuity Steward has been activated';
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
                Alternate Continuity Steward activated
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['recipient_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <?= htmlspecialchars($data['alternate_cs_name'] ?? 'the designated alternate', ENT_QUOTES, 'UTF-8') ?>
                has been activated as the Continuity Steward
                <?php if (!empty($data['practitioner_name'])): ?>
                  for <?= htmlspecialchars($data['practitioner_name'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
                <?php if (!empty($data['activated_at'])): ?>
                  on <?= htmlspecialchars($data['activated_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                <?php if (!empty($data['former_cs_name'])): ?>
                  <?= htmlspecialchars($data['former_cs_name'], ENT_QUOTES, 'UTF-8') ?>
                  is no longer the active Steward on this plan.
                <?php endif; ?>
              </p>

              <!-- Status box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      The Continuity Plan remains active. The alternate Steward now holds all
                      responsibilities previously assigned to the primary Steward.
                      A countersignature renewal may be required — you will be notified if so.
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
                      View the plan
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Contact <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>
                if you have questions about this transition.
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
Alternate Continuity Steward activated

Dear {{recipient_name}},

{{alternate_cs_name}} has been activated as the Continuity Steward for {{practitioner_name}} on {{activated_at}}. {{former_cs_name}} is no longer the active Steward on this plan.

The plan remains active. The alternate Steward now holds all responsibilities. A countersignature renewal may be required.

View the plan: {{plan_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
