<?php
/**
 * Template 41 — Team Member Invitation (Agency BP)
 * Trigger:    UC-XP-018 / BP team invite (Agency BP invites a team member)
 * Recipient:  Invitee
 * Gate:       [UNGATED] (invitation)
 * Subject:    You have been invited to join {{agency_name}} on Aegis
 * Preheader:  {{inviter_name}} has invited you to join their team. Accept to get started.
 * Merge tags: $data['invitee_name'], $data['agency_name'], $data['inviter_name'],
 *             $data['role_label'], $data['invite_url'], $data['expires_days'],
 *             $data['email_title'], $data['ungated']
 */
declare(strict_types=1);
require_once __DIR__ . '/../../icons.php';
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$data['ungated']     = true;
$data['gate_key']    = '[UNGATED]';
$data['email_title'] ??= 'You have been invited to join a team on Aegis';
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
                You have been invited to join a team
              </h1>

              <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;
                        font-size:14px;line-height:1.6;color:#4a4741;">
                <?php if (!empty($data['invitee_name'])): ?>
                  Dear <?= htmlspecialchars($data['invitee_name'], ENT_QUOTES, 'UTF-8') ?>,
                <?php else: ?>
                  Dear colleague,
                <?php endif; ?>
                <?= htmlspecialchars($data['inviter_name'] ?? 'A team member', ENT_QUOTES, 'UTF-8') ?>
                has invited you to join
                <strong><?= htmlspecialchars($data['agency_name'] ?? 'their organization', ENT_QUOTES, 'UTF-8') ?></strong>
                on Aegis
                <?php if (!empty($data['role_label'])): ?>
                  as a <strong><?= htmlspecialchars($data['role_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php endif; ?>.
              </p>

              <!-- Info box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 0 24px;">
                <tr>
                  <td style="background-color:#f5f0e6;border-left:3px solid #a0813e;
                             padding:14px 16px;border-radius:0 6px 6px 0;">
                    <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                              font-size:13px;color:#4a4741;line-height:1.5;">
                      This invitation expires in
                      <?= htmlspecialchars((string)($data['expires_days'] ?? '14'), ENT_QUOTES, 'UTF-8') ?> days.
                      Accepting will give you access to the team's shared portal and assignments.
                      You are under no obligation to accept.
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Primary CTA -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     style="margin:0 auto 24px;">
                <tr>
                  <td style="background-color:#a0813e;border-radius:8px;">
                    <a href="<?= htmlspecialchars($data['invite_url'] ?? AEGIS_SITE_URL, ENT_QUOTES, 'UTF-8') ?>"
                       style="display:inline-block;padding:12px 28px;
                              font-family:Arial,Helvetica,sans-serif;font-size:14px;
                              font-weight:600;color:#ffffff;text-decoration:none;">
                      Accept invitation
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                If you do not wish to join, disregard this email.
                No account changes will be made without your consent.
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
You have been invited to join a team

Dear {{invitee_name}},

{{inviter_name}} has invited you to join {{agency_name}} on Aegis as a {{role_label}}.

This invitation expires in {{expires_days}} days. Accepting gives you access to the team's shared portal and assignments.

Accept invitation: {{invite_url}}

If you do not wish to join, disregard this email.

© {{year}} Aegis · A MA'AT product
*/
?>
