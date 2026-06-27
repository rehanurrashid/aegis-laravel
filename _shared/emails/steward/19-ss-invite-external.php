<?php
/**
 * Template 19 — SS Invitation (External)
 * Trigger:    UC-PRV (SS invite — external, no existing Aegis account)
 * Recipient:  Invitee (no Aegis account yet)
 * Gate:       [UNGATED] (invitation)
 * Subject:    You have been invited to serve as a Support Steward on Aegis
 * Preheader:  {{practitioner_name}} has named you as a Support Steward. Create your account to accept.
 * Merge tags: $data['practitioner_name'], $data['invite_url'], $data['expires_days'],
 *             $data['email_title'], $data['ungated']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']     = true;
$data['gate_key']    = '[UNGATED]';
$data['email_title'] ??= 'You have been invited to serve as a Support Steward';
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
                You have been named as a Support Steward
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                <?= htmlspecialchars($data['practitioner_name'] ?? 'A healthcare practitioner', ENT_QUOTES, 'UTF-8') ?>
                has invited you to serve as a Support Steward on Aegis — a platform for
                healthcare practice continuity planning. As a Support Steward, you would provide
                practical assistance during a critical incident, working alongside the
                Continuity Steward.
              </p>

              <!-- Role context box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      To accept, you will need to create a free Aegis account. This invitation
                      expires in
                      <?= htmlspecialchars((string)($data['expires_days'] ?? '14'), ENT_QUOTES, 'UTF-8') ?> days.
                      You are under no obligation to accept.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['invite_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review invitation
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you do not wish to accept, disregard this email.
                No account will be created without your consent.
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
You have been named as a Support Steward

{{practitioner_name}} has invited you to serve as a Support Steward on Aegis.

To accept, create a free Aegis account. This invitation expires in {{expires_days}} days.

Review invitation: {{invite_url}}

If you do not wish to accept, disregard this email.

© {{year}} Aegis · A MA'AT product
*/
?>
