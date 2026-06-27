<?php
/**
 * Template 15 — Annual Re-Attestation Completed
 * Trigger:    UC-PRV-039 (Practitioner completes re-attestation)
 * Recipient:  CS and SS (fan-out — same copy, recipient_name resolves per recipient)
 * Gate:       notify_attestation
 * Subject:    Continuity Plan re-attestation completed by {{practitioner_name}}
 * Preheader:  The plan has been re-attested for another year. No action required.
 * Merge tags: $data['recipient_name'], $data['practitioner_name'], $data['plan_title'],
 *             $data['attested_at'], $data['next_due_date'], $data['plan_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_attestation';
$data['email_title'] ??= 'Re-attestation completed';
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
                Re-attestation completed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['recipient_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <?= htmlspecialchars($data['practitioner_name'] ?? 'The practitioner', ENT_QUOTES, 'UTF-8') ?>
                has completed their annual Continuity Plan re-attestation
                <?php if (!empty($data['plan_title'])): ?>
                  for <strong><?= htmlspecialchars($data['plan_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                <?php if (!empty($data['attested_at'])): ?>
                  on <?= htmlspecialchars($data['attested_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                The plan remains active for another year.
              </p>

              <!-- Confirmation box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      No action is required from you at this time.
                      <?php if (!empty($data['next_due_date'])): ?>
                        The next re-attestation is due on
                        <?= htmlspecialchars($data['next_due_date'], ENT_QUOTES, 'UTF-8') ?>.
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
                    <a href="<?= htmlspecialchars($data['plan_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View the plan
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                You will be notified when the next re-attestation window approaches.
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
Re-attestation completed

Dear {{recipient_name}},

{{practitioner_name}} has completed their annual Continuity Plan re-attestation for {{plan_title}} on {{attested_at}}. The plan remains active for another year.

No action is required from you at this time. Next re-attestation due: {{next_due_date}}.

View the plan: {{plan_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
