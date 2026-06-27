<?php
/**
 * Template 63 — Vault Item Shared
 * Trigger:    UC-PRV-200 (vault_item_shared — fan-out ×2 per share event)
 * Recipient:  Recipient steward (CS or SS)
 * Gate:       notify_docs_accessed
 * Subject:    {{practitioner_name}} shared a vault item with you
 * Preheader:  You now have scoped access to {{item_title}} in {{practitioner_name}}'s vault.
 * Merge tags: $data['steward_name'], $data['practitioner_name'], $data['item_title'],
 *             $data['access_level'], $data['vault_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_docs_accessed';
$data['email_title'] ??= 'A vault item has been shared with you';
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
                A vault item has been shared with you
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['steward_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <strong><?= htmlspecialchars($data['practitioner_name'] ?? 'Your practitioner', ENT_QUOTES, 'UTF-8') ?></strong>
                has granted you scoped access to
                <?php if (!empty($data['item_title'])): ?>
                  <strong><?= htmlspecialchars($data['item_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php else: ?>
                  a vault item
                <?php endif; ?>
                in their credential vault.
              </p>

              <!-- Access details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <?php if (!empty($data['access_level'])): ?>
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>Access level:</strong>
                      <?= htmlspecialchars($data['access_level'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Vault access is scoped, logged, and audited. Use this access
                      only as directed by your stewardship responsibilities.
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
                      View vault item
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you did not expect this access grant, contact
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
A vault item has been shared with you

Dear {{steward_name}},

{{practitioner_name}} has granted you scoped access to {{item_title}} in their credential vault.

Access level: {{access_level}}
Vault access is scoped, logged, and audited.

View vault item: {{vault_url}}

If you did not expect this access grant, contact support@aegis.devlet.tech.

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
