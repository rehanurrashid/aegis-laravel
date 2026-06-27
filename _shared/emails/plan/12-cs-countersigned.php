<?php
/**
 * Template 12 — CS Countersigned Plan
 * Trigger:    UC-CS (countersign action) / UC-XP-002
 * Recipient:  Practitioner
 * Gate:       notify_plan_change
 * Subject:    Your Continuity Steward has countersigned your plan
 * Preheader:  {{cs_name}} has countersigned your Continuity Plan. It is now fully executed.
 * Merge tags: $data['practitioner_name'], $data['cs_name'], $data['plan_title'],
 *             $data['countersigned_at'], $data['plan_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_plan_change';
$data['email_title'] ??= 'Your Continuity Steward has countersigned your plan';
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
                Your plan is fully executed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['practitioner_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <?= htmlspecialchars($data['cs_name'] ?? 'Your Continuity Steward', ENT_QUOTES, 'UTF-8') ?>
                has countersigned your Continuity Plan
                <?php if (!empty($data['plan_title'])): ?>
                  <strong><?= htmlspecialchars($data['plan_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>
                <?php if (!empty($data['countersigned_at'])): ?>
                  on <?= htmlspecialchars($data['countersigned_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                Both parties have now signed, and your plan is fully executed.
              </p>

              <!-- Confirmation box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Your Continuity Plan is now active and on record. Annual re-attestation will
                      keep it current. You will receive a reminder when it is due.
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
                      View my Continuity Plan
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                A signed copy is available in your portal at any time.
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
Your plan is fully executed

Dear {{practitioner_name}},

{{cs_name}} has countersigned your Continuity Plan {{plan_title}} on {{countersigned_at}}. Both parties have now signed, and your plan is fully executed.

Your Continuity Plan is now active and on record. Annual re-attestation will keep it current.

View my Continuity Plan: {{plan_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
