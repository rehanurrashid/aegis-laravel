<?php
/**
 * Template 13 (CS) — Vault Attested — Continuity Steward variant
 * Trigger:    UC-PRV-040 / UC-XP-003 (Practitioner completes vault attestation)
 * Recipient:  Continuity Steward
 * Gate:       notify_attestation
 * Subject:    Vault attestation completed for {{practitioner_name}}
 * Preheader:  The vault has been attested. Please review and confirm your access remains current.
 * Merge tags: $data['cs_name'], $data['practitioner_name'], $data['vault_attested_at'],
 *             $data['review_url'], $data['unsubscribe_token'], $data['email_title']
 *
 * §A.3 correction: two role-targeted variants (CS + SS).
 * CS variant: includes attestation-review CTA and countersign duty context.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_attestation';
$data['email_title'] ??= 'Vault attestation completed';
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
                Vault attestation completed
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['cs_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                <?= htmlspecialchars($data['practitioner_name'] ?? 'Your practitioner', ENT_QUOTES, 'UTF-8') ?>
                has completed their vault attestation
                <?php if (!empty($data['vault_attested_at'])): ?>
                  on <?= htmlspecialchars($data['vault_attested_at'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>.
                As their Continuity Steward, please confirm that your access to the plan
                and vault remains current.
              </p>

              <!-- Attestation status box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f0faf4;border-left:3px solid #2f7d54;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      Vault attested
                      <?php if (!empty($data['vault_attested_at'])): ?>
                        on <?= htmlspecialchars($data['vault_attested_at'], ENT_QUOTES, 'UTF-8') ?>
                      <?php endif; ?>.
                      Review the current vault index to confirm your understanding of its contents.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['review_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Review vault attestation
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                This is part of your ongoing Continuity Steward responsibilities.
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
Vault attestation completed

Dear {{cs_name}},

{{practitioner_name}} has completed their vault attestation on {{vault_attested_at}}. As their Continuity Steward, please confirm that your access to the plan and vault remains current.

Review the current vault index to confirm your understanding of its contents.

Review vault attestation: {{review_url}}

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
