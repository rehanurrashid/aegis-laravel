<?php
/**
 * Template 44 — Referral Received
 * Trigger:    UC-PRV-108 (a connection sends a client referral)
 * Recipient:  Practitioner (referral target)
 * Gate:       notify_agreement
 * Subject:    You have received a referral from {{referrer_name}}
 * Preheader:  {{referrer_name}} has referred a client to you. Review the details and respond.
 * Merge tags: $data['practitioner_name'], $data['referrer_name'], $data['client_initials'],
 *             $data['referral_note'], $data['referral_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_agreement';
$data['email_title'] ??= 'You have received a referral';
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
                You have received a referral
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['practitioner_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <strong><?= htmlspecialchars($data['referrer_name'] ?? 'A colleague', ENT_QUOTES, 'UTF-8') ?></strong>
                has sent you a client referral
                <?php if (!empty($data['client_initials'])): ?>
                  (client: <?= htmlspecialchars($data['client_initials'], ENT_QUOTES, 'UTF-8') ?>)
                <?php endif; ?>.
                Please review the referral details and respond at your earliest convenience.
              </p>

              <?php if (!empty($data['referral_note'])): ?>
              <!-- Referral note box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      Note from <?= htmlspecialchars($data['referrer_name'] ?? 'your colleague', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <?= htmlspecialchars($data['referral_note'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                  </td>
                </tr>
              </table>
              <?php else: ?>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Full referral details are available in your portal. You can accept,
                      decline, or mark the referral as pending.
                    </p>
                  </td>
                </tr>
              </table>
              <?php endif; ?>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['referral_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review referral
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Client details are kept confidential and shared only within the referral context.
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
You have received a referral

Dear {{practitioner_name}},

{{referrer_name}} has sent you a client referral (client: {{client_initials}}). Please review and respond at your earliest convenience.

Note: {{referral_note}}

Review referral: {{referral_url}}

Client details are kept confidential and shared only within the referral context.

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
