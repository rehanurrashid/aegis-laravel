<?php
/**
 * Template 10 — Plan Signed Confirmation
 * Trigger:    UC-PRV-036 (Practitioner signs continuity plan)
 * Recipient:  Practitioner
 * Gate:       notify_plan_change
 * Subject:    Your Continuity Plan has been signed
 * Preheader:  Your plan is now active. Your Continuity Steward has been notified.
 * Merge tags: $data['practitioner_name'], $data['plan_title'], $data['signed_at'],
 *             $data['plan_url'], $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_plan_change';
$data['email_title'] ??= 'Your Continuity Plan has been signed';
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
                Your Continuity Plan is now active
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['practitioner_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                your Continuity Plan
                <?php if (!empty($data['plan_title'])): ?>
                  <strong><?= htmlspecialchars($data['plan_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                has been signed
                <?php if (!empty($data['signed_at'])): ?>
                  on <?= htmlspecialchars($data['signed_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                Your designated Continuity Steward has been notified and the plan is now in effect.
              </p>

              <!-- Info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Your plan will require annual re-attestation to remain active. Aegis will
                      remind you 30 days, 7 days, and on the day it is due.
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
                      View my Continuity Plan
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you have questions about your plan, visit your portal or contact support.
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
Your Continuity Plan is now active

Dear {{practitioner_name}},

Your Continuity Plan {{plan_title}} has been signed on {{signed_at}}. Your designated Continuity Steward has been notified and the plan is now in effect.

Your plan will require annual re-attestation to remain active. Aegis will remind you 30 days, 7 days, and on the day it is due.

View my Continuity Plan: {{plan_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
