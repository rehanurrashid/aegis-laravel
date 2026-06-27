<?php
/**
 * Template 34 — Proposal Declined
 * Trigger:    UC-PRV-133 (Practitioner declines BP proposal)
 * Recipient:  Business Partner
 * Gate:       notify_proposal
 * Subject:    Your proposal was not accepted
 * Preheader:  {{practitioner_name}} has declined your proposal. You may submit a revised request.
 * Merge tags: $data['bp_name'], $data['practitioner_name'], $data['proposal_title'],
 *             $data['declined_at'], $data['decline_reason'], $data['portal_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_proposal';
$data['email_title'] ??= 'Your proposal was not accepted';
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
                Your proposal was not accepted
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['bp_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <?= htmlspecialchars($data['practitioner_name'] ?? 'The practitioner', ENT_QUOTES, 'UTF-8') ?>
                has declined your proposal
                <?php if (!empty($data['proposal_title'])): ?>
                  <strong><?= htmlspecialchars($data['proposal_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                <?php if (!empty($data['declined_at'])): ?>
                  on <?= htmlspecialchars($data['declined_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
              </p>

              <?php if (!empty($data['decline_reason'])): ?>
              <!-- Reason box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      Reason provided
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <?= htmlspecialchars($data['decline_reason'], ENT_QUOTES, 'UTF-8') ?>
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
                      No reason was provided. You may submit a revised proposal or
                      reach out to the practitioner directly through your portal.
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
                    <a href="<?= htmlspecialchars($data['portal_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Go to my portal
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                Your profile and service listings remain active and visible to other practitioners.
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
Your proposal was not accepted

Dear {{bp_name}},

{{practitioner_name}} has declined your proposal {{proposal_title}} on {{declined_at}}.

Reason: {{decline_reason}}

You may submit a revised proposal or reach out through your portal.

Go to my portal: {{portal_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
