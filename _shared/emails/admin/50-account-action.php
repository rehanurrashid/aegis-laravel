<?php
/**
 * Template 50 — Account Action by Admin
 * Trigger:    UC-ADM-023 (lock) / UC-ADM-024 (unlock) / UC-ADM-026 (role change)
 * Recipient:  Affected user (any role)
 * Gate:       [UNGATED]
 * Subject:    Your Aegis account has been {{action_label}}
 * Preheader:  An administrative action has been taken on your account. Review the details below.
 * Merge tags: $data['recipient_name'], $data['action_label'], $data['action_reason'],
 *             $data['actioned_at'], $data['support_url'],
 *             $data['email_title'], $data['ungated']
 *
 * action_label examples: "locked", "unlocked", "updated" (role change).
 * action_reason is the admin-supplied reason — required field in the admin UI.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']     = true;
$data['gate_key']    = '[UNGATED]';
$data['email_title'] ??= 'Administrative action on your Aegis account';
$_action = strtolower($data['action_label'] ?? 'updated');
$_is_lock = ($_action === 'locked');
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
                Your account has been <?= htmlspecialchars($_action, ENT_QUOTES, 'UTF-8') ?>
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['recipient_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                an administrative action has been taken on your Aegis account
                <?php if (!empty($data['actioned_at'])): ?>
                  on <?= htmlspecialchars($data['actioned_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
              </p>

              <!-- Action details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:<?= $_is_lock ? '#fdf2f2' : '#f5f0e6' ?>;
                             border-left:3px solid <?= $_is_lock ? '#c0392b' : '#a0813e' ?>;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Action:</strong>
                      Account <?= htmlspecialchars($_action, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php if (!empty($data['action_reason'])): ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Reason:</strong>
                      <?= htmlspecialchars($data['action_reason'], ENT_QUOTES, 'UTF-8') ?>
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
                    <a href="<?= htmlspecialchars($data['support_url'] ?? AEGIS_SITE_URL . '/support', ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Contact support
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you believe this action was taken in error, contact
                <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>
                with your account details.
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
Your account has been {{action_label}}

Dear {{recipient_name}},

An administrative action has been taken on your Aegis account on {{actioned_at}}.

Action: Account {{action_label}}
Reason: {{action_reason}}

If you believe this was taken in error, contact support@aegis.devlet.tech.

Contact support: {{support_url}}

© {{year}} Aegis · A MA'AT product
*/
?>
