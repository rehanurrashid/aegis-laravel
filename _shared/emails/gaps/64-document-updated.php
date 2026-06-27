<?php
/**
 * Template 64 — Document Updated
 * Trigger:    UC-PRV-192 (amend) / UC-PRV-193 (renew) / UC-PRV-194 (archive)
 * Recipient:  CS + SS (fan-out — steward_name resolves per recipient)
 * Gate:       notify_plan_change
 * Subject:    {{document_title}} was {{change_type}}
 * Preheader:  {{practitioner_name}} has {{change_type}} {{document_title}}. Review if needed.
 * Merge tags: $data['steward_name'], $data['practitioner_name'], $data['document_title'],
 *             $data['change_type'], $data['new_expiry_date'], $data['document_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 *
 * change_type values: "amended" | "renewed" | "archived"
 * new_expiry_date only populated when change_type = "renewed".
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_plan_change';
$data['email_title'] ??= 'A document in your plan has been updated';
$_change   = strtolower($data['change_type'] ?? 'updated');
$_archived = ($_change === 'archived');
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
                A plan document has been <?= htmlspecialchars($_change, ENT_QUOTES, 'UTF-8') ?>
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['steward_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <strong><?= htmlspecialchars($data['practitioner_name'] ?? 'Your practitioner', ENT_QUOTES, 'UTF-8') ?></strong>
                has <?= htmlspecialchars($_change, ENT_QUOTES, 'UTF-8') ?> the document
                <?php if (!empty($data['document_title'])): ?>
                  <strong><?= htmlspecialchars($data['document_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                in their Continuity Plan.
                <?php if (!$_archived): ?>
                  Please review if needed.
                <?php endif; ?>
              </p>

              <!-- Change details box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <?php if ($_change === 'renewed' && !empty($data['new_expiry_date'])): ?>
                    <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <strong>New expiry date:</strong>
                      <?= htmlspecialchars($data['new_expiry_date'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($_archived): ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Archived documents are no longer active but remain accessible in the
                      document history for audit purposes.
                    </p>
                    <?php else: ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      This change may affect your stewardship responsibilities. Review the
                      updated document to ensure your understanding remains current.
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
                    <a href="<?= htmlspecialchars($data['document_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View document
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                All document changes are logged in the plan audit trail.
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
A plan document has been {{change_type}}

Dear {{steward_name}},

{{practitioner_name}} has {{change_type}} the document {{document_title}} in their Continuity Plan.

New expiry date (if renewed): {{new_expiry_date}}

View document: {{document_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
