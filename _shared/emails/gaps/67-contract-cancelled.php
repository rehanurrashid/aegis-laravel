<?php
/**
 * Template 67 — Contract Cancelled
 * Trigger:    UC-PRV-138 (cancel_contract — one party cancels the agreement)
 * Recipient:  Other party (BP or Practitioner — recipient_name resolves per recipient)
 * Gate:       notify_agreement
 * Subject:    Contract cancelled: {{contract_title}}
 * Preheader:  The agreement for {{contract_title}} has been cancelled.
 * Merge tags: $data['recipient_name'], $data['counterparty_name'], $data['contract_title'],
 *             $data['cancel_reason'], $data['contract_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_agreement';
$data['email_title'] ??= 'Contract cancelled';
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
                A service agreement has been cancelled
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['recipient_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                the service agreement
                <?php if (!empty($data['contract_title'])): ?>
                  <strong><?= htmlspecialchars($data['contract_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                has been cancelled by
                <?= htmlspecialchars($data['counterparty_name'] ?? 'the other party', ENT_QUOTES, 'UTF-8') ?>.
              </p>

              <!-- Reason box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <?php if (!empty($data['cancel_reason'])): ?>
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      Reason provided
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <?= htmlspecialchars($data['cancel_reason'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php else: ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      No reason was provided. The agreement record remains available
                      in your portal for reference. Any outstanding milestones or
                      payments should be resolved separately.
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
                    <a href="<?= htmlspecialchars($data['contract_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View agreement record
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you have questions about this cancellation, contact
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
A service agreement has been cancelled

Dear {{recipient_name}},

The service agreement {{contract_title}} has been cancelled by {{counterparty_name}}.

Reason: {{cancel_reason}}

View agreement record: {{contract_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
