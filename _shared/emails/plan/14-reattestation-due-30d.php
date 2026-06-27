<?php
/**
 * Template 14 (30d) — Annual Re-Attestation Due — 30-day reminder
 * Trigger:    UC-PRV-038 / UC-XP-014 (scheduled — 30 days before due date)
 * Recipient:  Practitioner
 * Gate:       notify_plan_review
 * Subject:    Your Continuity Plan re-attestation is due in 30 days
 * Preheader:  Your annual re-attestation window opens soon. Review your plan to prepare.
 * Merge tags: $data['practitioner_name'], $data['due_date'], $data['days_until_due'],
 *             $data['plan_url'], $data['unsubscribe_token'], $data['email_title']
 *
 * §A.3: Three sends at 30d / 7d / day-of. Only the day-of (0d) send is [UNGATED].
 * This is the 30-day send — gated on notify_plan_review.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_plan_review';
$data['email_title'] ??= 'Re-attestation due in 30 days';
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
                Your plan re-attestation is due in
                <?= htmlspecialchars((string)($data['days_until_due'] ?? '30'), ENT_QUOTES, 'UTF-8') ?> days
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['practitioner_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                your annual Continuity Plan re-attestation is due on
                <strong><?= htmlspecialchars($data['due_date'] ?? 'the scheduled date', ENT_QUOTES, 'UTF-8') ?></strong>.
                This is an early reminder so you have time to review your plan and make
                any necessary updates before attesting.
              </p>

              <!-- Info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Re-attestation confirms that your plan, vault, and steward designations
                      remain accurate. Plans that lapse may require a new countersignature cycle.
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
                      Review my plan
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                You will receive another reminder 7 days before the due date.
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
Your plan re-attestation is due in {{days_until_due}} days

Dear {{practitioner_name}},

Your annual Continuity Plan re-attestation is due on {{due_date}}. This is an early reminder so you have time to review and prepare.

Re-attestation confirms that your plan, vault, and steward designations remain accurate.

Review my plan: {{plan_url}}

You will receive another reminder 7 days before the due date.

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
