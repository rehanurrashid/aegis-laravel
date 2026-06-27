<?php
/**
 * _email_head.php — Shared <head> block for all Aegis email templates.
 * Include at the very top of every template file, before <body>.
 * $data['email_title'] should be set by the mailer before include.
 */
declare(strict_types=1);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title><?= htmlspecialchars($data['email_title'] ?? 'Aegis', ENT_QUOTES, 'UTF-8') ?></title>
</head>
