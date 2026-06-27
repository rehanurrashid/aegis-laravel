<?php
/**
 * Template 45 — Referral Accepted / Declined
 * Trigger:    UC-PRV-111 (recipient responds to referral — accept or decline)
 * Recipient:  Original referral sender
 * Gate:       notify_agreement
 * Subject:    Your referral to {{recipient_name}} was {{status_label}}
 * Preheader:  {{recipient_name}} has {{status_label}} your referral for client {{client_initials}}.
 * Merge tags: $data['referrer_name'], $data['recipient_name'], $data['client_initials'],
 *             $data['status_label'], $data['response_note'], $data['responded_at'],
 *             $data['referral_url'], $data['unsubscribe_token'], $data['email_title']
 *
 * status_label is either "accepted" or "declined" — drives box color and copy.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_agreement';
$data['email_title'] ??= 'Your referral has received a response';
$_status  = strtolower($data['status_label'] ?? 'responded to');
$_accepted = ($_status === 'accepted');
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
                Your referral was <?= htmlspecialchars($_status, ENT_QUOTES, 'UTF-8') ?>
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['referrer_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <strong><?= htmlspecialchars($data['recipient_name'] ?? 'The recipient', ENT_QUOTES, 'UTF-8') ?></strong>
                has <?= htmlspecialchars($_status, ENT_QUOTES, 'UTF-8') ?> your referral
                <?php if (!empty($data['client_initials'])): ?>
                  for client <?= htmlspecialchars($data['client_initials'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
                <?php if (!empty($data['responded_at'])): ?>
                  on <?= htmlspecialchars($data['responded_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
              </p>

              <!-- Response box — green if accepted, gold if declined -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:<?= $_accepted ? '#f0faf4' : '#f5f0e6' ?>;
                             border-left:3px solid <?= $_accepted ? '#2f7d54' : '#a0813e' ?>;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <?php if (!empty($data['response_note'])): ?>
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      Note from <?= htmlspecialchars($data['recipient_name'] ?? 'them', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <?= htmlspecialchars($data['response_note'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php else: ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <?php if ($_accepted): ?>
                        The referral has been accepted. The recipient will reach out to the
                        client directly.
                      <?php else: ?>
                        No reason was provided. You may send the referral to another
                        practitioner in your Integrative Network.
                      <?php endif; ?>
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
                    <a href="<?= htmlspecialchars($data['referral_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View referral record
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Referral records are retained for your network history.
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
Your referral was {{status_label}}

Dear {{referrer_name}},

{{recipient_name}} has {{status_label}} your referral for client {{client_initials}} on {{responded_at}}.

Note: {{response_note}}

View referral record: {{referral_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
