<?php
/**
 * Template 35 — Contract Created
 * Trigger:    UC-XP-010 (contract auto-created after proposal accepted)
 * Recipient:  BP + Practitioner (fan-out — recipient_name resolves per recipient)
 * Gate:       notify_agreement
 * Subject:    A service agreement has been created — {{contract_title}}
 * Preheader:  A new service agreement is ready for both parties to review and sign.
 * Merge tags: $data['recipient_name'], $data['counterparty_name'], $data['contract_title'],
 *             $data['created_at'], $data['contract_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 *
 * Note: This template covers auto-creation only (UC-XP-010).
 * Contract SIGNED (UC-PRV-137) → Template 66.
 * Contract CANCELLED (UC-PRV-138) → Template 67.
 * Do not imply signing has occurred here.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_agreement';
$data['email_title'] ??= 'A service agreement has been created';
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
                A service agreement is ready for review
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['recipient_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                a service agreement
                <?php if (!empty($data['contract_title'])): ?>
                  <strong><?= htmlspecialchars($data['contract_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                has been created between you and
                <?= htmlspecialchars($data['counterparty_name'] ?? 'the other party', ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($data['created_at'])): ?>
                  on <?= htmlspecialchars($data['created_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                Please review the terms and sign at your earliest convenience.
              </p>

              <!-- Info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Work does not begin until both parties have signed the agreement.
                      You will receive a separate confirmation once fully executed.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['contract_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review and sign agreement
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                A copy of the signed agreement will be available in your portal once both
                parties have signed.
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
A service agreement is ready for review

Dear {{recipient_name}},

A service agreement {{contract_title}} has been created between you and {{counterparty_name}} on {{created_at}}. Please review the terms and sign at your earliest convenience.

Work does not begin until both parties have signed.

Review and sign agreement: {{contract_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
