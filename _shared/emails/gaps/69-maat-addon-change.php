<?php
/**
 * Template 69 — MAAT Add-on Activated / Deactivated
 * Trigger:    UC-PRV-210 (addon activated) / UC-PRV-211 (addon deactivated)
 * Recipient:  Practitioner
 * Gate:       notify_payment
 * Subject:    MAAT Continuity Steward Service {{addon_state}}
 * Preheader:  Your MAAT add-on is now {{addon_state}} ({{addon_price}}/mo).
 * Merge tags: $data['practitioner_name'], $data['addon_state'], $data['addon_price'],
 *             $data['billing_url'], $data['unsubscribe_token'], $data['email_title']
 *
 * addon_state: "active" or "inactive"
 * BILLING NOTE: Add-on requires Practice tier. Billing side is a [STUB] pending Stripe
 * Connect setup — this template is spec-correct but the send is contingent on the
 * billing event existing in the webhook pipeline.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['gate_key']    = 'notify_payment';
$data['email_title'] ??= 'MAAT Continuity Steward Service update';
$_state    = strtolower($data['addon_state'] ?? 'updated');
$_active   = ($_state === 'active');
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
                MAAT Continuity Steward Service is now <?= htmlspecialchars($_state, ENT_QUOTES, 'UTF-8') ?>
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                Dear <?= htmlspecialchars($data['practitioner_name'] ?? 'there', ENT_QUOTES, 'UTF-8') ?>,
                the MAAT Continuity Steward Service add-on on your account is now
                <strong><?= htmlspecialchars($_state, ENT_QUOTES, 'UTF-8') ?></strong>.
                <?php if ($_active && !empty($data['addon_price'])): ?>
                  A charge of <strong><?= htmlspecialchars($data['addon_price'], ENT_QUOTES, 'UTF-8') ?>/mo</strong>
                  has been added to your billing cycle.
                <?php elseif (!$_active): ?>
                  The add-on charge has been removed from your billing cycle effective next renewal.
                <?php endif; ?>
              </p>

              <!-- Status box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:<?= $_active ? '#f0faf4' : '#f5f0e6' ?>;
                             border-left:3px solid <?= $_active ? '#2f7d54' : '#a0813e' ?>;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <?php if ($_active): ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      The MAAT Continuity Steward Service provides a MA'AT-designated steward
                      to fulfill your Continuity Steward role. Your Continuity Plan is now
                      backed by the MA'AT network.
                    </p>
                    <?php else: ?>
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      The MAAT Continuity Steward Service has been deactivated. You will
                      need to designate a new Continuity Steward to keep your plan fully active.
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
                    <a href="<?= htmlspecialchars($data['billing_url'] ?? AEGIS_SITE_URL . '/billing', ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      View billing details
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                This add-on requires an active Practice tier subscription. Contact
                <a href="mailto:support@aegis.devlet.tech"
                   style="color:#8a8378;">support@aegis.devlet.tech</a>
                with any billing questions.
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
MAAT Continuity Steward Service is now {{addon_state}}

Dear {{practitioner_name}},

The MAAT Continuity Steward Service add-on is now {{addon_state}}.
{{#if active}}A charge of {{addon_price}}/mo has been added to your billing cycle.{{/if}}
{{#if inactive}}The add-on charge has been removed effective next renewal.{{/if}}

View billing details: {{billing_url}}

This add-on requires an active Practice tier subscription.

© {{year}} Aegis · A MA'AT product
Unsubscribe: {{unsubscribe_url}}
*/
?>
