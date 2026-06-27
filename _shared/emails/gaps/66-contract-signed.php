<?php
/**
 * Template 66 — Contract Signed
 * Trigger:    UC-PRV-137 (sign_contract — both parties have now signed)
 * Recipient:  BP + Practitioner (fan-out — recipient_name resolves per recipient)
 * Gate:       notify_agreement
 * Subject:    Contract signed: {{contract_title}}
 * Preheader:  The agreement for {{contract_title}} is now fully signed and active.
 * Merge tags: $data['recipient_name'], $data['counterparty_name'], $data['contract_title'],
 *             $data['contract_url'], $data['signed_at'],
 *             $data['unsubscribe_token'], $data['email_title']
 *
 * Distinct from Template 35 (contract CREATED — signature not yet complete).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_agreement';
$data['email_title'] ??= 'Contract signed and active';
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
                Your agreement is fully signed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['recipient_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                the service agreement
                <?php if (!empty($data['contract_title'])): ?>
                  <strong><?= htmlspecialchars($data['contract_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                between you and
                <?= htmlspecialchars($data['counterparty_name'] ?? 'the other party', ENT_QUOTES, 'UTF-8') ?>
                has been fully signed by both parties
                <?php if (!empty($data['signed_at'])): ?>
                  on <?= htmlspecialchars($data['signed_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                The agreement is now active.
              </p>

              <!-- Confirmation box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      A signed copy of the agreement is available in your portal.
                      Work can now begin according to the terms and milestones defined.
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
                      View signed agreement
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Keep a copy of this agreement for your records.
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
Your agreement is fully signed

Dear {{recipient_name}},

The service agreement {{contract_title}} between you and {{counterparty_name}} has been fully signed on {{signed_at}}. The agreement is now active.

A signed copy is available in your portal. Work can now begin per the agreed terms.

View signed agreement: {{contract_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
