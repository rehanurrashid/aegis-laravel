<?php
/**
 * Template 28 — Vault Unlocked Notice
 * Trigger:    UC-XP-006 (vault unlocked following incident verification)
 * Recipient:  Continuity Steward
 * Gate:       notify_vault_unlock | [UNGATED] — always sends; listed gate is advisory for UI only
 * Subject:    The credential vault has been unlocked
 * Preheader:  Vault access is now open for {{practitioner_name}}'s Continuity Plan. Handle with care.
 * Merge tags: $data['cs_name'], $data['practitioner_name'], $data['unlocked_at'],
 *             $data['incident_id'], $data['vault_url'],
 *             $data['email_title'], $data['ungated']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']     = true;
$data['gate_key']    = '[UNGATED]';
$data['email_title'] ??= 'The credential vault has been unlocked';
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
                Credential vault unlocked
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['cs_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                the credential vault for
                <strong><?= htmlspecialchars($data['practitioner_name'] ?? 'your practitioner', ENT_QUOTES, 'UTF-8') ?></strong>
                has been unlocked
                <?php if (!empty($data['unlocked_at'])): ?>
                  on <?= htmlspecialchars($data['unlocked_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
                following incident verification. You now have access to the vault contents
                needed to carry out the Continuity Plan.
              </p>

              <!-- Security notice -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fdf2f2;border-left:3px solid #c0392b;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      All vault access is logged and audited. Use the credentials only as
                      directed by the Continuity Plan. Do not share vault contents with
                      unauthorized parties.
                      <?php if (!empty($data['incident_id'])): ?>
                        Reference: <?= htmlspecialchars($data['incident_id'], ENT_QUOTES, 'UTF-8') ?>.
                      <?php endif; ?>
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['vault_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Access the vault
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you did not expect this notification, contact
                <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>
                immediately.
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
Credential vault unlocked

Dear {{cs_name}},

The credential vault for {{practitioner_name}} has been unlocked on {{unlocked_at}} following incident verification. You now have access to the vault contents needed to carry out the Continuity Plan.

All vault access is logged and audited. Use credentials only as directed by the plan. Do not share vault contents with unauthorized parties. Reference: {{incident_id}}.

Access the vault: {{vault_url}}

If you did not expect this notification, contact support@aegis.devlet.tech immediately.

© {{year}} Aegis · A MA'AT product
*/
?>
