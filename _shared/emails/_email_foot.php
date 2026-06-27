<?php
/**
 * _email_foot.php — Shared footer table row for all Aegis email templates.
 * Include inside the outer 600px <table>, after the body <tr>.
 * [UNGATED] templates: set $data['ungated'] = true to suppress unsubscribe link.
 * Gate key surfaced as HTML comment for the mailer to parse.
 */
declare(strict_types=1);
defined('AEGIS_SITE_URL') || define('AEGIS_SITE_URL', 'https://aegis.devlet.tech');
$_foot_ungated = !empty($data['ungated']);
$_foot_gate    = htmlspecialchars($data['gate_key'] ?? '[UNGATED]', ENT_QUOTES, 'UTF-8');
?>
          <!-- Footer -->
          <tr>
            <td style="padding:20px 40px 28px;border-top:1px solid #e4dfd7;text-align:center;">
              <p style="margin:0 0 8px;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                &copy; <?= date('Y') ?> Aegis &middot; A MA'AT product
              </p>
              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;
                        font-size:12px;color:#8a8378;line-height:1.5;">
                <a href="<?= AEGIS_SITE_URL ?>/privacy"
                   style="color:#8a8378;text-decoration:underline;">Privacy Policy</a>
                <?php if (!$_foot_ungated): ?>
                &nbsp;&middot;&nbsp;
                <a href="<?= AEGIS_SITE_URL ?>/unsubscribe?token=<?= htmlspecialchars($data['unsubscribe_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   style="color:#8a8378;text-decoration:underline;">Unsubscribe</a>
                <?php endif; ?>
              </p>
            </td>
          </tr>
          <!-- GATE: notify_email + <?= $_foot_gate ?> -->
