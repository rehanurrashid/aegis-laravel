<?php
/**
 * Template 01 — Welcome (per role)
 * Trigger:    UC-PRV-001 / UC-CS-001,002 / UC-SS-001 / BP signup
 * Recipient:  New user (any role)
 * Gate:       [UNGATED]
 * Subject:    Welcome to Aegis, {{recipient_name}}
 * Preheader:  Your account is ready. Here is how to get started with your {{role_label}} portal.
 * Merge tags: $data['recipient_name'], $data['role_label'], $data['portal_url'],
 *             $data['email_title'], $data['ungated']
 *
 * BUILD DEPENDENCY: UC-PRV-001 onboarding is [UNWIRED — SIMULATED] in the current build.
 * onboarding.php performs no backend write. This template will not fire until
 * onboarding is fully wired (users insert, Stripe, email/2FA persistence).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']   = true;
$data['gate_key']  = '[UNGATED]';
$data['email_title'] ??= 'Welcome to Aegis';
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
                Welcome, <?= htmlspecialchars($data['recipient_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Your Aegis account has been created. You have been granted access to the
                <strong><?= htmlspecialchars($data['role_label'] ?? 'Aegis', ENT_QUOTES, 'UTF-8') ?></strong>
                portal — a place built around steadiness, continuity, and care.
              </p>

              <!-- Info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Aegis is designed to ensure your practice can continue — and your clients
                      are protected — even in unexpected circumstances. Your
                      <?= htmlspecialchars($data['role_label'] ?? 'portal', ENT_QUOTES, 'UTF-8') ?>
                      is ready for you to explore.
                    </p>
                  </td>
                </tr>
              </table>

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
                If the button above does not work, copy and paste this link into your browser:
                <?= htmlspecialchars($data['portal_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>
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
Welcome to Aegis, {{recipient_name}}

Your Aegis account has been created. You have been granted access to the {{role_label}} portal.

Aegis is designed to ensure your practice can continue — and your clients are protected — even in unexpected circumstances.

Go to your portal: {{portal_url}}

If you did not create this account, please contact us at support@aegis.devlet.tech.

© {{year}} Aegis · A MA'AT product
*/
?>
