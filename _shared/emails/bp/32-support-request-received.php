<?php
/**
 * Template 32 — Support Request Received (Proposal Acknowledgement)
 * Trigger:    UC-BP (BP submits support proposal/request)
 * Recipient:  Business Partner
 * Gate:       notify_proposal
 * Subject:    Your support request has been received
 * Preheader:  We have received your proposal for {{practitioner_name}}. You will be notified when they respond.
 * Merge tags: $data['bp_name'], $data['practitioner_name'], $data['proposal_title'],
 *             $data['submitted_at'], $data['proposal_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_proposal';
$data['email_title'] ??= 'Your support request has been received';
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
                Your support request has been received
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['bp_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                your support request
                <?php if (!empty($data['proposal_title'])): ?>
                  <strong><?= htmlspecialchars($data['proposal_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                for
                <?= htmlspecialchars($data['practitioner_name'] ?? 'the practitioner', ENT_QUOTES, 'UTF-8') ?>
                has been received
                <?php if (!empty($data['submitted_at'])): ?>
                  on <?= htmlspecialchars($data['submitted_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                You will be notified when they respond.
              </p>

              <!-- Info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      The practitioner will review your proposal and may accept, decline,
                      or request clarification. Response times vary.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['proposal_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View my proposal
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                You can review or withdraw your proposal at any time from your portal.
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
Your support request has been received

Dear {{bp_name}},

Your support request {{proposal_title}} for {{practitioner_name}} has been received on {{submitted_at}}. You will be notified when they respond.

The practitioner will review your proposal and may accept, decline, or request clarification.

View my proposal: {{proposal_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
