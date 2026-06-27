<?php
/**
 * Template 14 (0d) — Annual Re-Attestation Due — Day-of notice
 * Trigger:    UC-PRV-038 / UC-XP-014 (scheduled — on the due date)
 * Recipient:  Practitioner
 * Gate:       [UNGATED] — day-of send always fires regardless of notify_plan_review
 * Subject:    Your Continuity Plan re-attestation is due today
 * Preheader:  Re-attestation is due today. Complete it now to keep your plan active.
 * Merge tags: $data['practitioner_name'], $data['due_date'], $data['days_until_due'],
 *             $data['plan_url'], $data['ungated'], $data['email_title']
 *
 * §A.3: Three sends at 30d / 7d / day-of. Day-of is [UNGATED].
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']     = true;
$data['gate_key']    = '[UNGATED]';
$data['email_title'] ??= 'Re-attestation due today';
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
                Re-attestation is due today
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['practitioner_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                your annual Continuity Plan re-attestation is due today,
                <strong><?= htmlspecialchars($data['due_date'] ?? 'today', ENT_QUOTES, 'UTF-8') ?></strong>.
                Completing it now keeps your plan active and your stewards informed.
              </p>

              <!-- Alert box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#fdf2f2;border-left:3px solid #c0392b;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Plans that are not re-attested may be flagged as lapsed and could require
                      a new countersignature from your Continuity Steward to reactivate.
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
                      Complete re-attestation now
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you have already completed re-attestation today, please disregard this message.
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
Re-attestation is due today

Dear {{practitioner_name}},

Your annual Continuity Plan re-attestation is due today, {{due_date}}. Completing it now keeps your plan active and your stewards informed.

Plans not re-attested may be flagged as lapsed and require a new countersignature to reactivate.

Complete re-attestation now: {{plan_url}}

If you have already completed re-attestation today, please disregard this message.

© {{year}} Aegis · A MA'AT product
*/
?>
