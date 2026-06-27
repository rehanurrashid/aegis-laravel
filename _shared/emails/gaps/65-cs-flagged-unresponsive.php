<?php
/**
 * Template 65 — CS Flagged Unresponsive
 * Trigger:    UC-XP-016 (SS flags CS as unresponsive — warning before activation)
 * Recipient:  Practitioner
 * Gate:       notify_practitioner_cs_unresponsive
 * Subject:    Your Continuity Steward may be unresponsive
 * Preheader:  {{ss_name}} has flagged {{cs_name}} as unresponsive on your plan.
 * Merge tags: $data['practitioner_name'], $data['cs_name'], $data['ss_name'],
 *             $data['flagged_at'], $data['stewards_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 *
 * NOTE: This is the WARNING sent to the Practitioner BEFORE any alternate activation.
 * Alternate CS activation (UC-XP-019) → Template 25. Do not conflate.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_practitioner_cs_unresponsive';
$data['email_title'] ??= 'Your Continuity Steward may be unresponsive';
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
                Your Continuity Steward may be unresponsive
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['practitioner_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <strong><?= htmlspecialchars($data['ss_name'] ?? 'Your Support Steward', ENT_QUOTES, 'UTF-8') ?></strong>
                has flagged
                <strong><?= htmlspecialchars($data['cs_name'] ?? 'your Continuity Steward', ENT_QUOTES, 'UTF-8') ?></strong>
                as potentially unresponsive
                <?php if (!empty($data['flagged_at'])): ?>
                  on <?= htmlspecialchars($data['flagged_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                No action has been taken yet — this is an early warning for your awareness.
              </p>

              <!-- Warning box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      If your Steward remains unreachable, Aegis may activate your designated
                      alternate Continuity Steward. We recommend attempting direct contact with
                      <?= htmlspecialchars($data['cs_name'] ?? 'your Steward', ENT_QUOTES, 'UTF-8') ?>
                      and reviewing your steward designations in your portal.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['stewards_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review steward designations
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Contact <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>
                if you need guidance on next steps.
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
Your Continuity Steward may be unresponsive

Dear {{practitioner_name}},

{{ss_name}} has flagged {{cs_name}} as potentially unresponsive on {{flagged_at}}. No action has been taken yet — this is an early warning.

If your Steward remains unreachable, Aegis may activate your designated alternate. We recommend contacting {{cs_name}} directly and reviewing your steward designations.

Review steward designations: {{stewards_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
