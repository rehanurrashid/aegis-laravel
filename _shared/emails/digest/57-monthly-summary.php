<?php
/**
 * Template 57 — Monthly Summary (Practitioner)
 * Trigger:    Scheduled opt-in digest (monthly — Practitioner only)
 * Recipient:  Practitioner
 * Gate:       notify_summary
 * Subject:    Your Aegis summary — {{month_label}}
 * Preheader:  Plan health, network activity, and upcoming items for {{month_label}}.
 * Merge tags: $data['practitioner_name'], $data['month_label'],
 *             $data['plan_status_label'], $data['reattestation_due_date'],
 *             $data['network_connections'], $data['open_proposals'],
 *             $data['ceus_note'], $data['portal_url'],
 *             $data['unsubscribe_token'], $data['email_title']
 *
 * All fields are optional — the template degrades gracefully if any are absent.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_summary';
$data['email_title'] ??= 'Your Aegis monthly summary';
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
                Monthly summary
              </h1>

              <p style="margin:0 0 28px;font-family:Arial,Helvetica,sans-serif;
                        font-size:13px;color:#8a8378;line-height:1.5;">
                <?php if (!empty($data['month_label'])): ?>
                  <?= htmlspecialchars($data['month_label'], ENT_QUOTES, 'UTF-8') ?>
                  &middot;
                <?php endif; ?>
                <?= htmlspecialchars($data['practitioner_name'] ?? 'Your account', ENT_QUOTES, 'UTF-8') ?>
              </p>

              <!-- Plan health -->
              <?php if (!empty($data['plan_status_label'])): ?>
              <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;font-weight:600;color:#4a4741;
                        text-transform:uppercase;letter-spacing:0.5px;">
                Plan health
              </p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 20px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-radius:8px;padding:12px 16px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;
                                   color:#4a4741;line-height:1.4;">
                          Continuity Plan status
                        </td>
                        <td align="right" style="font-family:Arial,Helvetica,sans-serif;
                                    font-size:13px;font-weight:600;color:#a0813e;
                                    white-space:nowrap;padding-left:16px;">
                          <?= htmlspecialchars($data['plan_status_label'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              <?php endif; ?>

              <!-- Re-attestation -->
              <?php if (!empty($data['reattestation_due_date'])): ?>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 20px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-radius:8px;padding:12px 16px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;
                                   color:#4a4741;line-height:1.4;">
                          Re-attestation due
                        </td>
                        <td align="right" style="font-family:Arial,Helvetica,sans-serif;
                                    font-size:13px;font-weight:600;color:#a0813e;
                                    white-space:nowrap;padding-left:16px;">
                          <?= htmlspecialchars($data['reattestation_due_date'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              <?php endif; ?>

              <!-- Network activity -->
              <?php if (isset($data['network_connections'])): ?>
              <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;font-weight:600;color:#4a4741;
                        text-transform:uppercase;letter-spacing:0.5px;">
                Network
              </p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 20px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-radius:8px;padding:12px 16px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;
                                   color:#4a4741;line-height:1.4;">
                          Integrative Network connections
                        </td>
                        <td align="right" style="font-family:'Georgia','Times New Roman',serif;
                                    font-size:18px;font-weight:700;color:#a0813e;
                                    white-space:nowrap;padding-left:16px;">
                          <?= (int)$data['network_connections'] ?>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              <?php endif; ?>

              <!-- Open proposals -->
              <?php if (isset($data['open_proposals'])): ?>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 20px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-radius:8px;padding:12px 16px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;
                                   color:#4a4741;line-height:1.4;">
                          Open Business Partner proposals
                        </td>
                        <td align="right" style="font-family:'Georgia','Times New Roman',serif;
                                    font-size:18px;font-weight:700;color:#a0813e;
                                    white-space:nowrap;padding-left:16px;">
                          <?= (int)$data['open_proposals'] ?>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              <?php endif; ?>

              <!-- CEU note -->
              <?php if (!empty($data['ceus_note'])): ?>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;
                              font-size:12px;font-weight:600;color:#4a4741;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      CEU reminder
                    </p>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      <?= htmlspecialchars($data['ceus_note'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                  </td>
                </tr>
              </table>
              <?php endif; ?>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto;">
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
Monthly summary — {{month_label}}
{{practitioner_name}}

Plan health:
  Continuity Plan status: {{plan_status_label}}
  Re-attestation due: {{reattestation_due_date}}

Network:
  Integrative Network connections: {{network_connections}}
  Open Business Partner proposals: {{open_proposals}}

CEU reminder: {{ceus_note}}

Go to my portal: {{portal_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
