<?php
/**
 * Template 56 — Weekly Activity Digest
 * Trigger:    Scheduled opt-in digest (weekly)
 * Recipient:  Any user with notify_summary enabled
 * Gate:       notify_summary
 * Subject:    Your Aegis weekly digest — week of {{week_label}}
 * Preheader:  A summary of activity on your account this week.
 * Merge tags: $data['recipient_name'], $data['week_label'], $data['summary_items'],
 *             $data['portal_url'], $data['unsubscribe_token'], $data['email_title']
 *
 * $data['summary_items'] is an array of ['label' => string, 'count' => int, 'url' => string].
 * The mailer builds this from the activity feed before passing to the template.
 * If the array is empty or absent, a "quiet week" message renders instead.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_summary';
$data['email_title'] ??= 'Your Aegis weekly digest';
$_items = is_array($data['summary_items'] ?? null) ? $data['summary_items'] : [];
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

              <h1 style="margin:0 0 8px;font-family:'Georgia','Times New Roman',serif;
                         font-size:22px;font-weight:700;color:#2d2a26;line-height:1.3;">
                Weekly digest
              </h1>

              <p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;color:#8a8378;line-height:1.5;">
                <?php if (!empty($data['week_label'])): ?>
                  Week of <?= htmlspecialchars($data['week_label'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
                &middot;
                <?= htmlspecialchars($data['recipient_name'] ?? 'Your account', ENT_QUOTES, 'UTF-8') ?>
              </p>

              <?php if (!empty($_items)): ?>
                <?php foreach ($_items as $_item): ?>
                  <?php
                    $_label = htmlspecialchars($_item['label'] ?? '', ENT_QUOTES, 'UTF-8');
                    $_count = (int)($_item['count'] ?? 0);
                    $_url   = htmlspecialchars($_item['url'] ?? ($data['portal_url'] ?? AEGIS_SITE_URL), ENT_QUOTES, 'UTF-8');
                  ?>
                  <!-- Summary row -->
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                         style="margin:0 0 8px;">
                    <tr>
                      <td style="background-color:#f5f0e6;border-radius:8px;padding:12px 16px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                          <tr>
                            <td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;
                                       color:#4a4741;line-height:1.4;">
                              <?= $_label ?>
                            </td>
                            <td align="right" style="font-family:'Georgia','Times New Roman',serif;
                                        font-size:18px;font-weight:700;color:#a0813e;
                                        white-space:nowrap;padding-left:16px;">
                              <?= $_count ?>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                <?php endforeach; ?>

                <p style="margin:24px 0 0;font-family:Arial,Helvetica,sans-serif;
                          font-size:13px;color:#8a8378;line-height:1.5;">
                  Review the full activity log in your portal for details.
                </p>

              <?php else: ?>
                <!-- Quiet week -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="margin:0 0 24px;">
                  <tr>
                    <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                               padding:14px 16px;border-radius:0 6px 6px 0;">
                      <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                                font-size:13px;color:#4a4741;line-height:1.5;">
                        It was a quiet week on your account — no notable activity to report.
                      </p>
                    </td>
                  </tr>
                </table>
              <?php endif; ?>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:24px auto 0;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['portal_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Go to my portal
                    </a>
                  </td>
                </tr>
              </table>

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
Weekly digest — week of {{week_label}}
{{recipient_name}}

Activity this week:
{{#each summary_items}}
- {{label}}: {{count}}
{{/each}}

Go to my portal: {{portal_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
