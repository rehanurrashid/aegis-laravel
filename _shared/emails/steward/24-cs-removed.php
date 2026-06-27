<?php
/**
 * Template 24 — CS Removed from Plan
 * Trigger:    UC-PRV-056 (Practitioner removes CS from plan)
 * Recipient:  Continuity Steward (outgoing)
 * Gate:       notify_assignment
 * Subject:    You have been removed as a Continuity Steward
 * Preheader:  Your stewardship role on {{practitioner_name}}'s plan has ended.
 * Merge tags: $data['cs_name'], $data['practitioner_name'], $data['removed_at'],
 *             $data['portal_url'], $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_assignment';
$data['email_title'] ??= 'You have been removed as a Continuity Steward';
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
                Your Continuity Steward role has ended
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['cs_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                you have been removed as a Continuity Steward from
                <?= htmlspecialchars($data['practitioner_name'] ?? "the practitioner's", ENT_QUOTES, 'UTF-8') ?>
                Continuity Plan
                <?php if (!empty($data['removed_at'])): ?>
                  on <?= htmlspecialchars($data['removed_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                Your access to their plan and vault has been revoked.
              </p>

              <!-- Info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      You no longer have responsibilities under this plan. Your own Aegis account
                      and any other stewardship roles remain unaffected.
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
                If you believe this was done in error, contact
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
Your Continuity Steward role has ended

Dear {{cs_name}},

You have been removed as a Continuity Steward from {{practitioner_name}}'s Continuity Plan on {{removed_at}}. Your access to their plan and vault has been revoked.

Your own Aegis account and any other stewardship roles remain unaffected.

Go to my portal: {{portal_url}}

If you believe this was done in error, contact support@aegis.devlet.tech.

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
