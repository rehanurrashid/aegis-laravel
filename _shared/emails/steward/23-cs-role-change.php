<?php
/**
 * Template 23 — CS Role Change Requested
 * Trigger:    UC-CS-023 (CS requests a role change within the plan)
 * Recipient:  Practitioner
 * Gate:       notify_role_change
 * Subject:    Your Continuity Steward has requested a role change
 * Preheader:  {{cs_name}} has submitted a role change request. Review it in your portal.
 * Merge tags: $data['practitioner_name'], $data['cs_name'], $data['requested_role'],
 *             $data['request_note'], $data['requested_at'], $data['review_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_role_change';
$data['email_title'] ??= 'Continuity Steward role change requested';
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
                A role change has been requested
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['practitioner_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <?= htmlspecialchars($data['cs_name'] ?? 'Your Continuity Steward', ENT_QUOTES, 'UTF-8') ?>
                has submitted a request to change their role
                <?php if (!empty($data['requested_role'])): ?>
                  to <strong><?= htmlspecialchars($data['requested_role'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                <?php if (!empty($data['requested_at'])): ?>
                  on <?= htmlspecialchars($data['requested_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                Please review this request at your earliest convenience.
              </p>

              <?php if (!empty($data['request_note'])): ?>
              <!-- Request note box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      Note from <?= htmlspecialchars($data['cs_name'] ?? 'your Steward', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <?= htmlspecialchars($data['request_note'], ENT_QUOTES, 'UTF-8') ?>
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
                      A role change may affect countersignature requirements and plan status.
                      Review the full request before approving.
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
                    <a href="<?= htmlspecialchars($data['review_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review role change request
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                No change will take effect until you approve the request.
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
A role change has been requested

Dear {{practitioner_name}},

{{cs_name}} has submitted a request to change their role to {{requested_role}} on {{requested_at}}. Please review at your earliest convenience.

Note: {{request_note}}

Review role change request: {{review_url}}

No change will take effect until you approve.

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
