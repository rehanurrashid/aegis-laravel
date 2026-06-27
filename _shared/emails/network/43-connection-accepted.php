<?php
/**
 * Template 43 — Connection Accepted
 * Trigger:    UC-PRV-101 (connection request accepted)
 * Recipient:  Original requester
 * Gate:       notify_message
 * Subject:    {{acceptor_name}} has accepted your connection request
 * Preheader:  You are now connected on Aegis. Visit their profile to get started.
 * Merge tags: $data['requester_name'], $data['acceptor_name'], $data['acceptor_role'],
 *             $data['profile_url'], $data['accepted_at'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_message';
$data['email_title'] ??= 'Your connection request was accepted';
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
                Your connection request was accepted
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['requester_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <strong><?= htmlspecialchars($data['acceptor_name'] ?? 'Your contact', ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if (!empty($data['acceptor_role'])): ?>
                  (<?= htmlspecialchars($data['acceptor_role'], ENT_QUOTES, 'UTF-8') ?>)
                <?php endif; ?>
                has accepted your connection request
                <?php if (!empty($data['accepted_at'])): ?>
                  on <?= htmlspecialchars($data['accepted_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                You are now part of each other's Integrative Network.
              </p>

              <!-- Confirmation box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Connected practitioners can designate each other as stewards, share
                      referrals, and collaborate on continuity planning.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['profile_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View their profile
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                You can manage your network connections at any time from your portal.
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
Your connection request was accepted

Dear {{requester_name}},

{{acceptor_name}} ({{acceptor_role}}) has accepted your connection request on {{accepted_at}}. You are now part of each other's Integrative Network.

Connected practitioners can designate each other as stewards, share referrals, and collaborate on continuity planning.

View their profile: {{profile_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
