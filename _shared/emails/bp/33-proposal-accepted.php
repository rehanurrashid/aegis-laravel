<?php
/**
 * Template 33 — Proposal Accepted
 * Trigger:    UC-PRV-132 / UC-XP-010 (Practitioner accepts BP proposal)
 * Recipient:  Business Partner
 * Gate:       notify_proposal
 * Subject:    Your proposal has been accepted by {{practitioner_name}}
 * Preheader:  Congratulations — your proposal was accepted. Next step is contract creation.
 * Merge tags: $data['bp_name'], $data['practitioner_name'], $data['proposal_title'],
 *             $data['accepted_at'], $data['contract_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_proposal';
$data['email_title'] ??= 'Your proposal has been accepted';
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
                Your proposal has been accepted
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['bp_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <?= htmlspecialchars($data['practitioner_name'] ?? 'The practitioner', ENT_QUOTES, 'UTF-8') ?>
                has accepted your proposal
                <?php if (!empty($data['proposal_title'])): ?>
                  <strong><?= htmlspecialchars($data['proposal_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                <?php if (!empty($data['accepted_at'])): ?>
                  on <?= htmlspecialchars($data['accepted_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                A service agreement is now ready for review and signature.
              </p>

              <!-- Confirmation box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      The next step is to review and sign the service agreement. Work will
                      begin once both parties have signed and any initial milestone is confirmed.
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
                      Review service agreement
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                You will be notified when the agreement is countersigned and work can begin.
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
Your proposal has been accepted

Dear {{bp_name}},

{{practitioner_name}} has accepted your proposal {{proposal_title}} on {{accepted_at}}. A service agreement is now ready for review and signature.

Review and sign the service agreement. Work begins once both parties have signed.

Review service agreement: {{contract_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
