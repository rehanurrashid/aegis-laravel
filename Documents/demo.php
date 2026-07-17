<?php
/**
 * Aegis — Demo Launchpad
 *
 * Single entry point for demoing every meaningful combination of
 * (viewer identity × portal state × landing page).
 *
 * Sits at the project root so `_shared.css` resolves with a relative path.
 *
 * Two base URLs are computed per request:
 *   $BASE  — root of the deployed portal cluster (host-aware: localhost or kalink)
 *   $LOCAL — root of *this* PHP app (the demo page itself + public/* pages)
 *
 * On localhost:8000, both resolve to the same origin.
 * On kalink.devlet.tech, $BASE is the portal cluster and $LOCAL is the
 * server hosting demo.php + public profile pages.
 *
 * Reset functionality is folded in: clicking "Reset demo data" hits this
 * same page with ?reset=1&token=<TOKEN>, which wipes + reseeds the DB,
 * then renders the launchpad with a success banner at the top.
 */

define('AEGIS_ENTRY', true);

/* Token gate for the reset operation. CHANGE BEFORE DEPLOYING TO PRODUCTION. */
const AEGIS_RESET_TOKEN = 'aegis-demo-reset';

$host    = $_SERVER['HTTP_HOST']   ?? '';
$server  = $_SERVER['SERVER_NAME'] ?? '';
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

$is_local = in_array($server, ['localhost', '127.0.0.1'], true)
         || str_starts_with($host, 'localhost')
         || str_starts_with($host, '127.0.0.1');

if ($is_local) {
    /* Localhost: everything is same-origin under http://localhost:8000/ */
    $BASE  = $scheme . '://' . $host;
    $LOCAL = $scheme . '://' . $host;
} else {
    /* Production / staging: portals live on kalink, $LOCAL on whatever
       host is serving this file. */
    $BASE  = 'https://aegis.devlet.tech';
    $LOCAL = $scheme . '://' . $host;
}

/* ── URL builders ──────────────────────────────────────────────
   Each portal lives at a fixed path under $BASE. Same routes work
   on localhost and devlet, because the portals share their dir layout. */
$URL = [
    /* Portal home pages */
    'provider_portal'  => $BASE . '/provider-portal/dashboard.php',
    'cs_portal'        => $BASE . '/continuity-steward-portal/dashboard.php',
    'ss_portal'        => $BASE . '/support-steward-portal/dashboard.php',
    'bp_portal'        => $BASE . '/biz-portal/dashboard.php',

    /* Provider Portal — feature deep-links */
    'provider_settings'         => $BASE . '/provider-portal/settings.php',
    'provider_continuity_plan'  => $BASE . '/provider-portal/continuity-plan.php',
    'provider_continuity_stewards' => $BASE . '/provider-portal/continuity-stewards.php',
    'provider_support_stewards' => $BASE . '/provider-portal/support-stewards.php',
    'provider_important_docs'   => $BASE . '/provider-portal/important-documents.php',
    'provider_vault'            => $BASE . '/provider-portal/vault.php',
    'provider_finances'         => $BASE . '/provider-portal/finances.php',
    'provider_referrals'        => $BASE . '/provider-portal/referrals.php',
    'provider_services'         => $BASE . '/provider-portal/services.php',
    'provider_job_postings'     => $BASE . '/provider-portal/job-postings.php',
    'provider_activity'         => $BASE . '/provider-portal/activity.php',

    /* CS Portal — feature deep-links */
    'cs_overview'               => $BASE . '/continuity-steward-portal/overview.php',
    'cs_my_tasks'               => $BASE . '/continuity-steward-portal/my-tasks.php',
    'cs_continuity_management'  => $BASE . '/continuity-steward-portal/continuity-management.php',
    'cs_vault'                  => $BASE . '/continuity-steward-portal/vault.php',
    'cs_providers'              => $BASE . '/continuity-steward-portal/providers.php',
    'cs_important_docs'         => $BASE . '/continuity-steward-portal/important-documents.php',
    'cs_finances'               => $BASE . '/continuity-steward-portal/finances.php',
    'cs_messages'               => $BASE . '/continuity-steward-portal/messages.php',
    'cs_activity'               => $BASE . '/continuity-steward-portal/activity.php',
    'cs_edit_profile'           => $BASE . '/continuity-steward-portal/edit-profile.php',
    'cs_settings'               => $BASE . '/continuity-steward-portal/settings.php',

    /* SS Portal — feature deep-links */
    'ss_critical_incident_log'  => $BASE . '/support-steward-portal/critical-incident-log.php',
    'ss_my_tasks'               => $BASE . '/support-steward-portal/my-tasks.php',
    'ss_providers'              => $BASE . '/support-steward-portal/providers.php',

    /* BP Portal — feature deep-links */
    'bp_find_jobs'              => $BASE . '/biz-portal/find-jobs.php',
    'bp_dashboard'              => $BASE . '/biz-portal/dashboard.php',
    'bp_contracts'              => $BASE . '/biz-portal/contracts.php',
    'bp_proposals'              => $BASE . '/biz-portal/proposals.php',
    'bp_milestones'             => $BASE . '/biz-portal/milestones.php',
    'bp_invoices'               => $BASE . '/biz-portal/invoices.php',
    'bp_finances'               => $BASE . '/biz-portal/finances.php',
    'bp_messages'               => $BASE . '/biz-portal/messages.php',
    'bp_activity'               => $BASE . '/biz-portal/activity.php',
    'bp_edit_profile'           => $BASE . '/biz-portal/edit-profile.php',
    'bp_payment_setup'          => $BASE . '/biz-portal/payment-setup.php',
    'bp_team'                   => $BASE . '/biz-portal/team.php',

    /* Onboarding & auth (now at root, not under /onboarding/) */
    'admin_portal'     => $BASE . '/admin-portal/dashboard.php',
    'onboarding'       => $BASE . '/onboarding.php',
    'login'            => $BASE . '/login.php',
    'pricing'          => $BASE . '/pricing.php',

    /* Same-origin local pages */
    'reset'            => $LOCAL . '/demo.php?reset=1&token=' . AEGIS_RESET_TOKEN,
    'reset_emergency'  => $LOCAL . '/demo.php?reset=1&arm_emergency=1&token=' . AEGIS_RESET_TOKEN,
    'public_provider'  => $LOCAL . '/public/provider.php',
    'public_steward'   => $LOCAL . '/public/continuity_steward.php',
    'public_support_steward' => $LOCAL . '/public/support_steward.php',
    'public_business'  => $LOCAL . '/public/business.php',
];

/* qs($params) — build a clean ?key=val&key=val string from an assoc array */
function qs(array $params): string {
    if (!$params) return '';
    return '?' . http_build_query($params);
}

/* link($base, $params) — combine a URL with query params */
function lnk(string $base, array $params = []): string {
    return htmlspecialchars($base . qs($params), ENT_QUOTES, 'UTF-8');
}

/* h() — short alias for htmlspecialchars */
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/* ──────────────────────────────────────────────────────────────────
   RESET HANDLER — runs only when the launchpad is hit with
   ?reset=1&token=<TOKEN>. On success, populates $reset_status which
   renders a green banner at the top of the page. On failure, captures
   the exception for an inline error panel. Idle load (no ?reset=1)
   skips this block entirely and demo.php renders as normal.
   ────────────────────────────────────────────────────────────────── */
$reset_status = null;

if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    $provided = $_GET['token'] ?? '';
    if (!hash_equals(AEGIS_RESET_TOKEN, $provided)) {
        $reset_status = ['ok' => false, 'error' => 'Invalid or missing reset token.', 'trace' => null];
    } else {
        try {
            require_once __DIR__ . '/_shared/db.php';
            require_once __DIR__ . '/_shared/seed.php';
            require_once __DIR__ . '/_shared/models.php';

            $arm_emergency = isset($_GET['arm_emergency'])
                          && filter_var($_GET['arm_emergency'], FILTER_VALIDATE_BOOLEAN);

            /* Delete the SQLite file so db.php re-creates it cleanly. */
            $paths = [AEGIS_DB_PATH, AEGIS_DB_PATH . '-wal', AEGIS_DB_PATH . '-shm'];
            foreach ($paths as $p) { if (file_exists($p)) @unlink($p); }

            /* Open fresh DB and run schema. */
            $pdo = aegis_db();

            /* CRITICAL: run all DDL BEFORE any transaction. SQLite auto-commits
               open transactions when DDL runs, which corrupts PDO's transaction
               state. We explicitly create every optional table here, outside any
               transaction, so seed_from_json only runs DML (INSERT/DELETE)
               inside its transaction. */
            $ddl_tables = [
                'practitioner_payments' => 'CREATE TABLE IF NOT EXISTS practitioner_payments (
                    id TEXT PRIMARY KEY, practitioner_id TEXT NOT NULL,
                    payment_type TEXT, description TEXT, amount REAL,
                    currency TEXT DEFAULT "USD", status TEXT DEFAULT "paid",
                    payment_method_label TEXT, paid_at TEXT, created_at TEXT)',
                'practitioner_payment_methods' => 'CREATE TABLE IF NOT EXISTS practitioner_payment_methods (
                    id TEXT PRIMARY KEY, practitioner_id TEXT NOT NULL,
                    method_type TEXT, brand TEXT, last4 TEXT, expiry TEXT,
                    cardholder TEXT, is_default INTEGER DEFAULT 0,
                    purpose TEXT, created_at TEXT)',
                'network_connections' => 'CREATE TABLE IF NOT EXISTS network_connections (
                    id TEXT PRIMARY KEY, practitioner_id TEXT NOT NULL,
                    connected_user_id TEXT NOT NULL,
                    connection_type TEXT NOT NULL DEFAULT "clinical",
                    status TEXT NOT NULL DEFAULT "active",
                    referral_count INTEGER DEFAULT 0, acceptance_rate INTEGER DEFAULT 0,
                    response_time_hours REAL DEFAULT 0, peer_rating REAL DEFAULT 0,
                    peer_reviews INTEGER DEFAULT 0, has_services INTEGER DEFAULT 0,
                    connected_at TEXT DEFAULT CURRENT_TIMESTAMP)',
                'network_requests' => 'CREATE TABLE IF NOT EXISTS network_requests (
                    id TEXT PRIMARY KEY, practitioner_id TEXT NOT NULL,
                    requester_id TEXT NOT NULL,
                    request_type TEXT NOT NULL DEFAULT "clinical",
                    status TEXT NOT NULL DEFAULT "pending",
                    message TEXT, requested_at TEXT DEFAULT CURRENT_TIMESTAMP, responded_at TEXT)',
                'shadow_connections' => 'CREATE TABLE IF NOT EXISTS shadow_connections (
                    id TEXT PRIMARY KEY, practitioner_id TEXT NOT NULL,
                    shadow_user_id TEXT NOT NULL, match_score INTEGER DEFAULT 0,
                    referral_count INTEGER DEFAULT 0, acceptance_rate INTEGER DEFAULT 0,
                    response_time_hours REAL DEFAULT 0, peer_rating REAL DEFAULT 0,
                    peer_reviews INTEGER DEFAULT 0, has_services INTEGER DEFAULT 0,
                    status TEXT NOT NULL DEFAULT "active",
                    added_at TEXT DEFAULT CURRENT_TIMESTAMP)',
            ];
            foreach ($ddl_tables as $ddl) {
                $pdo->exec($ddl);
            }

            /* Now seed — all DDL is done, seed only runs INSERT/DELETE. */
            if (file_exists(AEGIS_SEED_PATH)) {
                aegis_seed_from_json($pdo, AEGIS_SEED_PATH);
            }

            /* If the caller asked, arm an active critical incident. */
            if ($arm_emergency) {
                aegis_trigger_incident(
                    'p_sarah',
                    'short_term_incapacitation',
                    'ss_linda',
                    'Dr. Johnson has not responded to calls or emails for 48 hours. Missed all scheduled appointments. Family unable to reach her. Last seen leaving office Apr 24 at 5:30 PM.',
                    [
                        ['time' => '08:15', 'method' => 'phone',     'outcome' => 'no answer'],
                        ['time' => '08:18', 'method' => 'in-person', 'outcome' => 'home — no response'],
                    ]
                );
            }

            /* Counts for the success banner. */
            $counts = [
                'users'           => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
                'plans'           => (int)$pdo->query('SELECT COUNT(*) FROM continuity_plans')->fetchColumn(),
                'plan_stewards'   => (int)$pdo->query('SELECT COUNT(*) FROM plan_stewards')->fetchColumn(),
                'plan_tasks'      => (int)$pdo->query('SELECT COUNT(*) FROM plan_tasks')->fetchColumn(),
                'vault_items'     => (int)$pdo->query('SELECT COUNT(*) FROM vault_items')->fetchColumn(),
                'activity_events' => (int)$pdo->query('SELECT COUNT(*) FROM activity_events')->fetchColumn(),
                'incidents'       => (int)$pdo->query('SELECT COUNT(*) FROM critical_incidents')->fetchColumn(),
                'bp_jobs'         => (int)$pdo->query('SELECT COUNT(*) FROM bp_jobs')->fetchColumn(),
            ];

            /* Clear the demo session cookie so the fresh run isn't anchored
               to a previously-selected user. */
            setcookie('aegis_uid', '', time() - 3600, '/');

            $reset_status = ['ok' => true, 'counts' => $counts, 'armed' => $arm_emergency];
        } catch (\Throwable $e) {
            $reset_status = [
                'ok'    => false,
                'error' => $e->getMessage(),
                'trace' => $e->getFile() . ':' . $e->getLine(),
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Aegis · Demo</title>
  <link rel="icon" type="image/svg+xml" href="aegis-favicon.svg" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Spectral:wght@400;500;600;700&display=swap" />
  <link rel="stylesheet" href="_shared.css" />

  <style>
    /* ─── Page-only layout ──────────────────────────────────────
       Everything visual (colors, type, button shapes, card chrome)
       comes from _shared.css. This block only handles the demo
       landing page's macro layout — wide centered column, generous
       whitespace, section spacing. */

    body {
      background: var(--surface-2);
      font-family: var(--font-sans);
      color: var(--text);
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
      margin: 0;
    }

    .demo-shell {
      max-width: 1180px;
      margin: 0 auto;
      padding: 56px 32px 96px;
    }

    /* ── Hero ── */
    .demo-hero {
      text-align: center;
      padding: 24px 0 56px;
      border-bottom: 1px solid var(--border);
      margin-bottom: 56px;
    }
    .demo-hero-eyebrow {
      display: inline-block;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 1.6px;
      text-transform: uppercase;
      color: var(--gold-dark);
      margin-bottom: 18px;
    }
    .demo-hero h1 {
      font-family: var(--font-serif);
      font-size: 40px;
      font-weight: 700;
      letter-spacing: -0.4px;
      margin: 0 0 14px;
      color: var(--text);
      line-height: 1.15;
    }
    .demo-hero p {
      font-size: 15px;
      color: var(--text-2);
      max-width: 620px;
      margin: 0 auto;
      line-height: 1.65;
    }
    .demo-hero-actions {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin-top: 28px;
      flex-wrap: wrap;
    }

    /* ── Active-host indicator (helps clients see which deployment they're on) ── */
    .demo-host-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-family: 'SF Mono', Monaco, Menlo, Consolas, monospace;
      font-size: 11px;
      font-weight: 600;
      color: var(--text-3);
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 4px 10px;
      margin-top: 22px;
    }
    .demo-host-pill::before {
      content: '';
      width: 6px; height: 6px;
      border-radius: 50%;
      background: #2e9e5e;
      flex-shrink: 0;
    }

    /* ── Section ── */
    .demo-section { margin-bottom: 64px; }
    .demo-section:last-child { margin-bottom: 0; }

    .demo-section-head {
      margin-bottom: 28px;
      max-width: 720px;
    }
    .demo-section-num {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 26px; height: 26px;
      border-radius: var(--radius-sm);
      background: var(--gold-dark);
      color: #fff;
      font-family: var(--font-serif);
      font-weight: 700;
      font-size: 13px;
      margin-right: 10px;
    }
    .demo-section h2 {
      font-family: var(--font-serif);
      font-size: 22px;
      font-weight: 700;
      letter-spacing: -0.1px;
      color: var(--text);
      margin: 0;
      display: flex;
      align-items: center;
    }
    .demo-section-sub {
      color: var(--text-2);
      font-size: 14px;
      line-height: 1.6;
      margin: 8px 0 0 36px;
    }

    /* ── Card grid ── */
    .demo-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 16px;
    }
    .demo-grid.two   { grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); }
    .demo-grid.three { grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }

    /* ── Card ── */
    .demo-card {
      background: var(--surface);
      border-radius: var(--radius);
      padding: 22px 22px 18px;
      border: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      gap: 14px;
      transition:
        box-shadow .22s cubic-bezier(0.4, 0, 0.2, 1),
        transform .22s cubic-bezier(0.4, 0, 0.2, 1),
        border-color .22s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .demo-card:hover {
      box-shadow: 0 8px 24px rgba(30,28,26,0.06), 0 2px 6px rgba(30,28,26,0.04);
      transform: translateY(-1px);
      border-color: rgba(196,169,106,0.35);
    }

    /* Card header (avatar/icon + identity) */
    .demo-card-head {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .demo-card-avatar {
      width: 42px; height: 42px;
      border-radius: var(--radius-sm);
      background: linear-gradient(135deg, var(--gold-dark) 0%, #8a6d33 100%);
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-family: var(--font-serif);
      font-weight: 700;
      font-size: 15px;
      flex-shrink: 0;
      letter-spacing: 0.4px;
    }
    .demo-card-avatar.anon {
      background: var(--surface-3);
      color: var(--text-3);
    }
    .demo-card-avatar.cs    { background: linear-gradient(135deg, #4a7c5e, #36604a); }
    .demo-card-avatar.ss    { background: linear-gradient(135deg, #7a8a98, #5e6c78); }
    .demo-card-avatar.bp    { background: linear-gradient(135deg, #4a90c4, #356b96); }
    .demo-card-identity {
      flex: 1;
      min-width: 0;
    }
    .demo-card-name {
      font-family: var(--font-serif);
      font-size: 15px;
      font-weight: 700;
      color: var(--text);
      line-height: 1.25;
    }
    .demo-card-role {
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--text-3);
      margin-top: 3px;
    }

    /* Card description */
    .demo-card-desc {
      font-size: 13px;
      color: var(--text-2);
      line-height: 1.55;
      margin: 0;
    }

    /* Card meta tags */
    .demo-card-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }
    .demo-tag {
      display: inline-flex;
      align-items: center;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.8px;
      text-transform: uppercase;
      padding: 3px 7px;
      border-radius: var(--radius-sm);
      background: var(--surface-3);
      color: var(--text-3);
    }
    .demo-tag.gold  { background: rgba(196,169,106,0.14); color: var(--gold-dark); }
    .demo-tag.green { background: rgba(74,124,94,0.13);   color: #2e5640; }
    .demo-tag.blue  { background: rgba(74,144,196,0.12);  color: #2a6a9a; }
    .demo-tag.gray  { background: var(--surface-3);       color: var(--text-3); }

    /* Card actions — bottom-aligned button row */
    .demo-card-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: auto;
      padding-top: 4px;
    }
    .demo-card-actions .btn { font-size: 12px; padding: 7px 14px; }
    .demo-card-actions .btn-sm { font-size: 11.5px; padding: 6px 12px; }

    /* Param chip — shows the resulting URL params for the launch */
    .demo-card-params {
      font-family: 'SF Mono', Monaco, Menlo, Consolas, monospace;
      font-size: 10.5px;
      color: var(--text-4);
      background: var(--surface-2);
      border-radius: var(--radius-sm);
      padding: 6px 9px;
      word-break: break-all;
      line-height: 1.5;
    }

    /* Inline code (used in card descriptions) */
    .demo-inline-code {
      font-family: 'SF Mono', Monaco, Menlo, Consolas, monospace;
      font-size: 12px;
      color: var(--gold-dark);
    }

    /* Tier matrix */
    .demo-matrix {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 16px;
    }
    @media (max-width: 720px) {
      .demo-matrix { grid-template-columns: 1fr; }
    }

    /* Footer note */
    .demo-foot-note {
      margin-top: 64px;
      padding: 22px 24px;
      background: var(--surface);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      font-size: 13px;
      color: var(--text-2);
      line-height: 1.65;
    }
    .demo-foot-note strong { color: var(--text); font-weight: 700; }
    .demo-foot-note code {
      font-family: 'SF Mono', Monaco, Menlo, Consolas, monospace;
      font-size: 12px;
      background: var(--surface-2);
      padding: 2px 6px;
      border-radius: var(--radius-xs);
      color: var(--gold-dark);
    }

    @media (max-width: 700px) {
      .demo-shell { padding: 36px 20px 64px; }
      .demo-hero h1 { font-size: 28px; }
      .demo-hero p { font-size: 14px; }
      .demo-hero { padding-bottom: 32px; margin-bottom: 36px; }
      .demo-section { margin-bottom: 44px; }
      .demo-section-sub { margin-left: 0; margin-top: 10px; }
    }

    /* ── Reset banner ─────────────────────────────────────────────
       Shown only after a successful or failed reset operation. */
    .reset-banner {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      padding: 18px 22px;
      border-radius: var(--radius, 12px);
      margin-bottom: 28px;
      border: 1px solid;
    }
    .reset-banner-success {
      background: rgba(76, 175, 125, 0.08);
      border-color: rgba(76, 175, 125, 0.30);
      color: var(--green-dark, #2c7a4e);
    }
    .reset-banner-error {
      background: rgba(160, 45, 34, 0.06);
      border-color: rgba(160, 45, 34, 0.30);
      color: var(--red-dark, #7d211a);
    }
    .reset-banner-icon {
      font-size: 22px;
      line-height: 1;
      flex-shrink: 0;
      font-weight: 700;
    }
    .reset-banner-body { flex: 1; min-width: 0; }
    .reset-banner-title {
      font-family: var(--font-serif, Georgia), serif;
      font-size: 16px;
      font-weight: 700;
      margin-bottom: 6px;
    }
    .reset-banner-counts {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      font-size: 13px;
      color: var(--text-2, #555);
    }
    .reset-banner-counts strong {
      color: var(--text, #1e1c1a);
      font-weight: 700;
    }
    .reset-banner-error-msg {
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 6px;
    }
    .reset-banner-trace {
      font-family: var(--font-mono, ui-monospace, monospace);
      font-size: 11px;
      color: var(--text-3, #777);
      background: rgba(0, 0, 0, 0.04);
      padding: 4px 8px;
      border-radius: 4px;
      word-break: break-all;
    }
  </style>
</head>
<body>

<div class="demo-shell">

  <?php if ($reset_status): ?>
  <?php if ($reset_status['ok']): ?>
  <div class="reset-banner reset-banner-success" role="status">
    <div class="reset-banner-icon">✓</div>
    <div class="reset-banner-body">
      <div class="reset-banner-title">
        Demo database reset successfully<?= $reset_status['armed'] ? ' · Active incident armed' : '' ?>.
      </div>
      <div class="reset-banner-counts">
        <?php foreach ($reset_status['counts'] as $label => $n): ?>
          <span><strong><?= (int)$n ?></strong> <?= h(str_replace('_', ' ', $label)) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <a class="btn btn-outline btn-sm" href="<?= h($LOCAL . '/demo.php') ?>">Dismiss</a>
  </div>
  <?php else: ?>
  <div class="reset-banner reset-banner-error" role="alert">
    <div class="reset-banner-icon">⚠</div>
    <div class="reset-banner-body">
      <div class="reset-banner-title">Reset failed</div>
      <div class="reset-banner-error-msg"><?= h($reset_status['error']) ?></div>
      <?php if (!empty($reset_status['trace'])): ?>
      <div class="reset-banner-trace"><?= h($reset_status['trace']) ?></div>
      <?php endif; ?>
    </div>
    <a class="btn btn-outline btn-sm" href="<?= h($LOCAL . '/demo.php') ?>">Dismiss</a>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <!-- ─── Hero ─── -->
  <header class="demo-hero">
    <div class="demo-hero-eyebrow">Aegis · Practice Continuity</div>
    <h1>Demo Launchpad</h1>
    <p>
      Pick a scenario to launch the portal with the right user identity and state.
      All flags persist as you navigate — set them once, browse freely.
    </p>
    <div class="demo-hero-actions">
      <a class="btn btn-primary btn-sm" href="<?= h(lnk($BASE . '/admin-portal/dashboard.php', ['as' => 'admin_root'])) ?>" target="_blank" rel="noopener">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Admin Portal
      </a>
      <a class="btn btn-outline btn-sm" href="<?= h($URL['reset']) ?>">Reset demo data</a>
      <a class="btn btn-outline btn-sm" href="<?= h($URL['pricing']) ?>" target="_blank" rel="noopener">Pricing page</a>
      <a class="btn btn-outline btn-sm" href="<?= h($URL['onboarding']) ?>" target="_blank" rel="noopener">Onboarding flow</a>
      <a class="btn btn-outline btn-sm" href="<?= h($URL['login']) ?>" target="_blank" rel="noopener">Sign-in screen</a>
      <a class="btn btn-outline btn-sm" href="<?= h($LOCAL . '/email-demo.php') ?>">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        Email templates
      </a>
    </div>
    <div class="demo-host-pill" title="Active deployment host">
      <?= h($BASE) ?>
    </div>
  </header>

  <!-- ═════════════════════════════════════════════════════════
       0. Utility
       ═════════════════════════════════════════════════════════ -->
  <section class="demo-section">
    <div class="demo-section-head">
      <h2><span class="demo-section-num">0</span> Utility</h2>
      <p class="demo-section-sub">
        Reset the SQLite database from <code>seed.json</code>. Run this after editing
        the seed file or whenever the demo data gets dirty.
      </p>
    </div>
    <div class="demo-grid three">
      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar anon">↻</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Reset demo database</div>
            <div class="demo-card-role">Wipes + re-seeds from seed.json</div>
          </div>
        </div>
        <p class="demo-card-desc">Re-runs the full seed pipeline. Use after pulling new seed data or hitting any constraint errors. Token-protected — only works in demo environments. Runs in-place on this page and shows a result banner at the top.</p>
        <div class="demo-card-params">?reset=1 · token=<?= h(AEGIS_RESET_TOKEN) ?></div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= h($URL['reset']) ?>">Run reset</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar anon">⚠</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Reset + arm active incident</div>
            <div class="demo-card-role">Reseed and trigger Short-Term Incapacitation</div>
          </div>
        </div>
        <p class="demo-card-desc">Same reset, but also fires <code>aegis_trigger_incident()</code> immediately afterward — Sarah enters the active-incident state, vault unseals, CS gets notified. Useful for testing the emergency UX without manually triggering.</p>
        <div class="demo-card-params">?reset=1 · arm_emergency=1</div>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= h($URL['reset_emergency']) ?>">Reset + arm</a>
        </div>
      </article>
    </div>
  </section>

  <!-- ═════════════════════════════════════════════════════════
       1. Practitioner Portal — Dr. Sarah Johnson
       ═════════════════════════════════════════════════════════ -->
  <section class="demo-section">
    <div class="demo-section-head">
      <h2><span class="demo-section-num">1</span> Practitioner Portal — Dr. Sarah Johnson</h2>
      <p class="demo-section-sub">
        Sarah is the canonical practitioner identity. Owner of
        <span class="demo-inline-code">/provider/sarah-johnson</span>, Practice tier with Integrative Services on,
        plus an active CS role for Dr. Michael Torres and an active SS role for Dr. Rachel Okafor.
        All scenarios below open her portal in various states.
      </p>
    </div>

    <!-- ── 1A. Plan & services matrix ── -->
    <div class="demo-section-head" style="margin-top:24px">
      <h2 style="font-size:18px"><span class="demo-section-num">1A</span> Plan &amp; services matrix</h2>
      <p class="demo-section-sub">
        Combinations of plan tier (Practice vs Access) and Integrative Business Services mode.
        Flags persist across navigation.
      </p>
    </div>
    <div class="demo-matrix">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Continuity Practice · Services on</div>
            <div class="demo-card-role">Full dashboard, Clinical Services visible</div>
          </div>
        </div>
        <p class="demo-card-desc">Sarah's default state. Practice tier + Integrative Services on. All nav items unlocked. Clinical Services pill in sidebar, My Services nav item appears. Header CS Portal switcher visible (Sarah is a CS for Dr. Torres).</p>
        <div class="demo-card-params">tier=practice · services=1 · cs_role=1</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['provider_portal'], ['as' => 'p_sarah', 'tier' => 'practice', 'services' => '1', 'cs_role' => '1']) ?>" target="_blank" rel="noopener">Open portal</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Continuity Practice · Services off</div>
            <div class="demo-card-role">Full dashboard, Services nav hidden</div>
          </div>
        </div>
        <p class="demo-card-desc">Practice tier with Integrative Services mode off. No Services badge in sidebar, no My Services nav item.</p>
        <div class="demo-card-params">tier=practice · services=0 · cs_role=1</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['provider_portal'], ['as' => 'p_sarah', 'tier' => 'practice', 'services' => '0', 'cs_role' => '1']) ?>" target="_blank" rel="noopener">Open portal</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Continuity Access · Services off</div>
            <div class="demo-card-role">Limited dashboard, upgrade prompts</div>
          </div>
        </div>
        <p class="demo-card-desc">Access tier ($29/mo). Referrals and My Services locked behind upgrade modals. Job Postings hidden. Sidebar shows Continuity Access badge with Upgrade link.</p>
        <div class="demo-card-params">tier=access · services=0 · cs_role=1</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['provider_portal'], ['as' => 'p_sarah', 'tier' => 'access', 'services' => '0', 'cs_role' => '1']) ?>" target="_blank" rel="noopener">Open portal</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['provider_settings'], ['as' => 'p_sarah', 'tab' => 'subscription', 'upgrade' => '1', 'tier' => 'access', 'cs_role' => '1']) ?>" target="_blank" rel="noopener">Upgrade panel</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Continuity Access · Services on</div>
            <div class="demo-card-role">Limited dashboard with Services badge</div>
          </div>
        </div>
        <p class="demo-card-desc">Access tier with Services mode toggled on. Tests how the Clinical Services pill renders alongside the Continuity Access tier indicator.</p>
        <div class="demo-card-params">tier=access · services=1 · cs_role=1</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['provider_portal'], ['as' => 'p_sarah', 'tier' => 'access', 'services' => '1', 'cs_role' => '1']) ?>" target="_blank" rel="noopener">Open portal</a>
        </div>
      </article>

    </div>

    <!-- ── 1B. Critical incident state ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">1B</span> Critical incident state</h2>
      <p class="demo-section-sub">
        Toggle the active-incident banner. <code>emergency=true</code> persists portal-wide
        so you can navigate freely with the banner showing.
      </p>
    </div>
    <div class="demo-matrix">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar gold">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Provider portal · Active incident</div>
            <div class="demo-card-role">Continuity Plan Active banner on dashboard</div>
          </div>
        </div>
        <p class="demo-card-desc">Sarah's portal in emergency mode. Dashboard shows the "Continuity Plan Active — Short-Term Incapacitation" banner. Activity → Critical Incident lists the full activation timeline (report → activation → CS acknowledgement → vault unseal → SS notification).</p>
        <div class="demo-card-params">emergency=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['provider_portal'], ['as' => 'p_sarah', 'tier' => 'practice', 'services' => '1', 'cs_role' => '1', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Launch with incident</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['provider_activity'], ['as' => 'p_sarah', 'tier' => 'practice', 'services' => '1', 'cs_role' => '1', 'emergency' => 'true', 'module' => 'incident']) ?>" target="_blank" rel="noopener">Incident activity log</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar gold">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Provider portal · Standby</div>
            <div class="demo-card-role">No active incident</div>
          </div>
        </div>
        <p class="demo-card-desc">Same portal in standby state — no incident banner. Useful for comparing the calm-vs-active UX side by side.</p>
        <div class="demo-card-params">emergency=false</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['provider_portal'], ['as' => 'p_sarah', 'tier' => 'practice', 'services' => '1', 'cs_role' => '1', 'emergency' => 'false']) ?>" target="_blank" rel="noopener">Launch standby</a>
        </div>
      </article>

    </div>

    <!-- ── 1C. Acting in another role ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">1C</span> Acting as CS / SS for another provider</h2>
      <p class="demo-section-sub">
        Sarah holds active steward roles for two other practitioners. These cards open her Provider portal
        deep-linked to the "I'm CS For" and "I'm SS For" tabs, which only render when she actually serves someone.
      </p>
    </div>
    <div class="demo-grid two">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Acting as CS for another provider</div>
            <div class="demo-card-role">I'm Continuity Steward For (deep-linked tab)</div>
          </div>
        </div>
        <p class="demo-card-desc">Sarah is the Primary Continuity Steward for <strong>Dr. Michael Torres</strong> (Bay Area Psychiatry). Opens <span class="demo-inline-code">continuity-stewards.php</span> directly on the "I'm Continuity Steward For" tab. Use <code>cs_account_type=business</code> to render the Business CS sub-label.</p>
        <div class="demo-card-params">cs_role=1 · cs_account_type=business · tab=iamexec</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['provider_continuity_stewards'], ['as' => 'p_sarah', 'cs_role' => '1', 'cs_account_type' => 'business', 'tab' => 'iamexec']) ?>" target="_blank" rel="noopener">Open “I'm CS For”</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Acting as SS for another provider</div>
            <div class="demo-card-role">I'm a Support Steward For (deep-linked tab)</div>
          </div>
        </div>
        <p class="demo-card-desc">Sarah is the Primary Support Steward for <strong>Dr. Rachel Okafor</strong> (North Bay Therapy Collective). Opens <span class="demo-inline-code">support-stewards.php</span> directly on the "I'm a Support Steward For" tab.</p>
        <div class="demo-card-params">ss_role=1 · tab=iamdsr</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['provider_support_stewards'], ['as' => 'p_sarah', 'ss_role' => '1', 'tab' => 'iamdsr']) ?>" target="_blank" rel="noopener">Open “I'm SS For”</a>
        </div>
      </article>

    </div>

    <!-- ── 1D. Page deep-links ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">1D</span> Page deep-links</h2>
      <p class="demo-section-sub">
        Jump straight to a canonical page for a walkthrough. All links open as Sarah in her default state.
      </p>
    </div>
    <div class="demo-grid three">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Continuity Plan Builder</div>
            <div class="demo-card-role">The 7-row incident-type grid</div>
          </div>
        </div>
        <p class="demo-card-desc">The single most important page in Aegis. Configure each of the 7 critical-moment types: authorized stewards, required documentation, pre-written task lists.</p>
        <div class="demo-card-params">continuity-plan.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['provider_continuity_plan'], ['as' => 'p_sarah', 'cs_role' => '1']) ?>" target="_blank" rel="noopener">Open Builder</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Designate Continuity Stewards</div>
            <div class="demo-card-role">4-step wizard + per-incident authorization</div>
          </div>
        </div>
        <p class="demo-card-desc">Add CSes (Primary + Alternate) with per-incident-type authorization matrix. Two account-type variants: Provider+CS Add-on ($19/mo) or Business CS ($49/mo).</p>
        <div class="demo-card-params">continuity-stewards.php · cs_account_type=addon|business</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['provider_continuity_stewards'], ['as' => 'p_sarah', 'cs_role' => '1', 'cs_account_type' => 'addon']) ?>" target="_blank" rel="noopener">Add-on tier</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['provider_continuity_stewards'], ['as' => 'p_sarah', 'cs_role' => '1', 'cs_account_type' => 'business']) ?>" target="_blank" rel="noopener">Business CS</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Designate Support Stewards</div>
            <div class="demo-card-role">3-step wizard with permission matrix</div>
          </div>
        </div>
        <p class="demo-card-desc">Add SSes (Primary + Alternate) with granular permission matrix (scheduling, billing, referrals, vault access scope).</p>
        <div class="demo-card-params">support-stewards.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['provider_support_stewards'], ['as' => 'p_sarah', 'cs_role' => '1']) ?>" target="_blank" rel="noopener">Open</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Important Documents</div>
            <div class="demo-card-role">Signed plans + Aegis library</div>
          </div>
        </div>
        <p class="demo-card-desc">Aegis Document Library, signed Continuity Plan, addendums, contracts/MOUs, BAA. Mirrored to CS and SS portals as read-only.</p>
        <div class="demo-card-params">important-documents.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['provider_important_docs'], ['as' => 'p_sarah', 'cs_role' => '1']) ?>" target="_blank" rel="noopener">Open</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Document Vault — 4 zones</div>
            <div class="demo-card-role">Standard / Emergency / Roster / Credentials</div>
          </div>
        </div>
        <p class="demo-card-desc">Standard zone visible to CS at all times; the other three (Emergency Vault, Client Roster, Secure Credentials via Keeper) sealed during standby and unlock only on a verified incident.</p>
        <div class="demo-card-params">vault.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['provider_vault'], ['as' => 'p_sarah', 'cs_role' => '1']) ?>" target="_blank" rel="noopener">Open Vault</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Finances</div>
            <div class="demo-card-role">Subscription + invoices + Stripe</div>
          </div>
        </div>
        <p class="demo-card-desc">Subscription billing + invoices from CSes and BPs with a Stripe Connect status pill per recipient. Aegis holds no funds — CS fees and BP invoices are charged directly to recipients via Stripe Connect.</p>
        <div class="demo-card-params">finances.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['provider_finances'], ['as' => 'p_sarah', 'cs_role' => '1']) ?>" target="_blank" rel="noopener">Open</a>
        </div>
      </article>

    </div>

    <!-- ── 1E. Public profile — viewer tiers ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">1E</span> Sarah's public profile — viewer tiers</h2>
      <p class="demo-section-sub">
        Three viewer tiers (anonymous / logged-in / owner) on Sarah's provider profile.
        Viewer role doesn't matter once signed in — any role gets the full logged-in view.
      </p>
    </div>
    <div class="demo-grid three">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar anon">—</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Tier 1 · Anonymous</div>
            <div class="demo-card-role">Not signed in</div>
          </div>
        </div>
        <p class="demo-card-desc">No contact details. Locked-section placeholders for metrics, activity, connection info. Sign-in CTAs everywhere.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['public_provider'], ['slug' => 'sarah-johnson']) ?>">View as anonymous</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Tier 2 · Logged-in non-owner</div>
            <div class="demo-card-role">Signed in as Marcus (CS) or Acme (BP)</div>
          </div>
        </div>
        <p class="demo-card-desc">Contact details unlocked. Real metrics, activity feed, connection info card. Send Message + Send Referral actions.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['public_provider'], ['slug' => 'sarah-johnson', 'as' => 'cs_marcus']) ?>">View as Marcus</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['public_provider'], ['slug' => 'sarah-johnson', 'as' => 'bp_acme']) ?>">View as Acme</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Tier 3 · Owner</div>
            <div class="demo-card-role">Signed in as Sarah herself</div>
          </div>
        </div>
        <p class="demo-card-desc">Edit Profile button replaces Send Message. Profile Visibility info panel appears. Connection card hidden.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['public_provider'], ['slug' => 'sarah-johnson', 'as' => 'p_sarah']) ?>">View as Sarah</a>
        </div>
      </article>

    </div>
  </section>

  <!-- ═════════════════════════════════════════════════════════
       2. Continuity Steward Portal
       ═════════════════════════════════════════════════════════ -->
  <section class="demo-section">
    <div class="demo-section-head">
      <h2><span class="demo-section-num">2</span> Continuity Steward Portal</h2>
      <p class="demo-section-sub">
        Two CS identities: <strong>Marcus Chen</strong> is an independent Business CS (paid, serves multiple practitioners, has a public profile, also holds a BP role).
        <strong>Dr. Priya Raman (cs_alternate)</strong> is an Invited CS — free tier, linked to one practitioner, no public profile.
        The CS portal is dormant during standby and becomes the command center the moment a critical incident is verified.
      </p>
    </div>

    <!-- ── 2A. Identities ── -->
    <div class="demo-section-head" style="margin-top:24px">
      <h2 style="font-size:18px"><span class="demo-section-num">2A</span> CS Identities</h2>
      <p class="demo-section-sub">Two account subtypes — Business (paid, multi-practitioner, public profile) and Invited (free, single practitioner, no public profile).</p>
    </div>
    <div class="demo-grid two">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Marcus Chen · Business CS</div>
            <div class="demo-card-role">Independent · $49/mo · also holds BP role</div>
          </div>
        </div>
        <p class="demo-card-desc">Standalone CS account for independent stewards serving up to 20 practitioners. Multi-role identity — Marcus owns both <span class="demo-inline-code">/steward/marcus-chen</span> (CS profile) and <span class="demo-inline-code">/business/marcus-chen</span> (BP profile). Stripe Connect active, Aegis Verified, 4.9 rating.</p>
        <div class="demo-card-tags">
          <span class="demo-tag green">Business CS</span>
          <span class="demo-tag blue">+ BP role</span>
          <span class="demo-tag gold">Verified</span>
        </div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_portal'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Open portal (standby)</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['cs_portal'], ['as' => 'cs_marcus', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Open with incident</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">PR</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Dr. Priya Raman · Invited CS</div>
            <div class="demo-card-role">Free tier · 1 practitioner · no public profile</div>
          </div>
        </div>
        <p class="demo-card-desc">Free invited-CS account linked to one inviting practitioner. Cannot proactively invite additional providers — hits an upgrade prompt if she tries. No public CS profile; her slug at <span class="demo-inline-code">/steward/priya-raman</span> intentionally returns 404.</p>
        <div class="demo-card-tags">
          <span class="demo-tag green">Invited CS</span>
          <span class="demo-tag gray">No public profile</span>
        </div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_portal'], ['as' => 'cs_alternate', 'invited' => 'true']) ?>" target="_blank" rel="noopener">Open portal</a>
        </div>
      </article>

    </div>

    <!-- ── 2B. Dashboard states ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">2B</span> Dashboard states</h2>
      <p class="demo-section-sub">The CS dashboard has three distinct render states driven by query params. Combine them freely.</p>
    </div>
    <div class="demo-matrix">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Dashboard · Standby</div>
            <div class="demo-card-role">Calm — no active incident</div>
          </div>
        </div>
        <p class="demo-card-desc">Typical day-to-day state. Greeting with provider count + certification status. Glance cards neutral. Provider caseload cards show green "All Good" or gold "Cert Due" chips with icon-only action buttons. Certification Status section below. Upcoming Tasks in aside. No Incident Cockpit.</p>
        <div class="demo-card-params">emergency=false</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_portal'], ['as' => 'cs_marcus', 'emergency' => 'false']) ?>" target="_blank" rel="noopener">Launch standby</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Dashboard · Active incident</div>
            <div class="demo-card-role">Critical Incident in progress</div>
          </div>
        </div>
        <p class="demo-card-desc">Full emergency mode. Top <code>alert-emergency</code> banner (Open Continuity Management / Task List / Vault). Topbar turns red with "Active Critical Incident" CTA. Glance cards red. Provider card shows red "Active Incident" chip + task/vault buttons. Incident Task List and Incident Cockpit progress ring appear. Verify Incident modal accessible.</p>
        <div class="demo-card-params">emergency=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['cs_portal'], ['as' => 'cs_marcus', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Launch with incident</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['cs_continuity_management'], ['as' => 'cs_marcus', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Jump to Cockpit</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Dashboard · Readiness review due</div>
            <div class="demo-card-role">Monthly attestation prompt</div>
          </div>
        </div>
        <p class="demo-card-desc">Monthly readiness review widget appears above the greeting. 5-item checklist modal (contact info, credentials, availability, plan review, Stripe). CS checks all items + attests, then submits — widget hides on submit. Stackable with emergency or standby states.</p>
        <div class="demo-card-params">readiness_due=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_portal'], ['as' => 'cs_marcus', 'readiness_due' => 'true']) ?>" target="_blank" rel="noopener">Launch with readiness due</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['cs_portal'], ['as' => 'cs_marcus', 'readiness_due' => 'true', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">+ Active incident</a>
        </div>
      </article>

    </div>

    <!-- ── 2C. Critical Incident flow ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">2C</span> Critical Incident flow</h2>
      <p class="demo-section-sub">The CS's core job — verify the incident SS triggered, unlock the vault, execute the task checklist, close the incident. Each page in the flow.</p>
    </div>
    <div class="demo-grid three">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Continuity Management cockpit</div>
            <div class="demo-card-role">Step 1 — Verify &amp; activate</div>
          </div>
        </div>
        <p class="demo-card-desc">The gateway. CS reviews the full SS report, uploads supporting documentation (death cert, medical doc, police report — type varies by incident). Documentation-required enforcement: if the plan mandates a cert, verify modal blocks until upload. On confirm, vault unlocks and task checklist generates.</p>
        <div class="demo-card-params">continuity-management.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_continuity_management'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Open cockpit (standby)</a>
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['cs_continuity_management'], ['as' => 'cs_marcus', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Open with incident</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">My Tasks</div>
            <div class="demo-card-role">Step 2 — Execute the checklist</div>
          </div>
        </div>
        <p class="demo-card-desc">Tasks pulled from each practitioner's Continuity Plan for the active incident type. During an active incident, that incident's tasks are pinned at the top; standby preparation tasks collapse below. Whole-list certification checkbox at the bottom + optional per-task exception flag for tasks that couldn't be completed.</p>
        <div class="demo-card-params">my-tasks.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_my_tasks'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Standby tasks</a>
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['cs_my_tasks'], ['as' => 'cs_marcus', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Incident tasks pinned</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Document Vault</div>
            <div class="demo-card-role">Step 3 — Access sealed records</div>
          </div>
        </div>
        <p class="demo-card-desc">3-tab read-only view: Support Documents / Client Roster / Secure Credentials. Sealed in standby — Emergency Vault, Client Roster, and Secure Credentials zones only unlock after CS verifies an incident. Every view, download, and credential reveal auto-logs to the activity feed as a legal audit trail entry.</p>
        <div class="demo-card-params">vault.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_vault'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Vault (sealed)</a>
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['cs_vault'], ['as' => 'cs_marcus', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Vault (unlocked)</a>
        </div>
      </article>

    </div>

    <!-- ── 2D. My Work pages ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">2D</span> My Work pages</h2>
      <p class="demo-section-sub">The CS's everyday management surfaces — caseload, documents, and finances.</p>
    </div>
    <div class="demo-grid three">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">My Providers (caseload)</div>
            <div class="demo-card-role">providers.php</div>
          </div>
        </div>
        <p class="demo-card-desc">CS's full caseload. Search, filter by status (standby / cert due / active incident). Refer a client from the roster modal. Business CS can invite new practitioners; Invited CS sees an upgrade lock on the invite button. Per-provider plan status chips and last-activity timestamps.</p>
        <div class="demo-card-params">providers.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_providers'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Open caseload</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['cs_providers'], ['as' => 'cs_alternate', 'invited' => 'true']) ?>" target="_blank" rel="noopener">Invited CS (locked invite)</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Important Documents</div>
            <div class="demo-card-role">important-documents.php</div>
          </div>
        </div>
        <p class="demo-card-desc">Signed Continuity Plans, addendums, CS↔Provider agreements, BAA, and the Aegis Sample Forms Library. Countersignature UI surfaces here when a practitioner has designated Marcus and the plan is awaiting his counter-sign — the "Review &amp; Sign" CTA is the critical action that activates the plan.</p>
        <div class="demo-card-params">important-documents.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_important_docs'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Open documents</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Finances</div>
            <div class="demo-card-role">finances.php</div>
          </div>
        </div>
        <p class="demo-card-desc">Stripe Connect status, active invoices (labeled "Active Invoices" — no escrow held by Aegis), awaiting-payment items, and payment history. Funds flow direct practitioner → CS bank via Stripe Connect. CS can create and send invoices when a critical incident is activated.</p>
        <div class="demo-card-params">finances.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_finances'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Open finances</a>
        </div>
      </article>

    </div>

    <!-- ── 2E. Communication & profile ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">2E</span> Communication, profile &amp; settings</h2>
    </div>
    <div class="demo-grid three">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Messages</div>
            <div class="demo-card-role">messages.php</div>
          </div>
        </div>
        <p class="demo-card-desc">3-column messaging: contact list / thread / detail panel. Continuity Contacts section pinned at top of every inbox (practitioner + primary SS + alternate SS + Aegis Support). All threads involving an active incident are audit-flagged as legal records and show a Critical Incident badge.</p>
        <div class="demo-card-params">messages.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_messages'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Open messages</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['cs_messages'], ['as' => 'cs_marcus', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">With incident badge</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Activity Log</div>
            <div class="demo-card-role">activity.php</div>
          </div>
        </div>
        <p class="demo-card-desc">Unified event stream for everything Marcus does and everything that affects him. Vault accesses, task completions, plan certifications, incident triggers, message receipt — all logged here in the canonical module-filtered feed. The single source of truth for the CS audit trail.</p>
        <div class="demo-card-params">activity.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_activity'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Open activity log</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['cs_activity'], ['as' => 'cs_marcus', 'emergency' => 'true', 'module' => 'incident']) ?>" target="_blank" rel="noopener">Incident module</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Edit Profile</div>
            <div class="demo-card-role">edit-profile.php</div>
          </div>
        </div>
        <p class="demo-card-desc">5-tab profile editor: Basic (name, contact, bio) / Credentials (licenses, bar number, background check) / Plan Framework (incident types Marcus accepts, service states) / Fee Structure (retainer, hourly rate, activation fee) / Verification (Aegis Verified module: gov ID upload, Code of Conduct, Checkr background check).</p>
        <div class="demo-card-params">edit-profile.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_edit_profile'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Edit profile</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Overview — Start Here</div>
            <div class="demo-card-role">overview.php</div>
          </div>
        </div>
        <p class="demo-card-desc">CS-specific key terms, role explanation, step-by-step "How to use Aegis as a CS" guide, and FAQs. Includes the critical incident notice: "If a provider in your care has passed away or is incapacitated, start with Continuity Management." Rendered from <code>aegis_overview_data()</code> role bundle.</p>
        <div class="demo-card-params">overview.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_overview'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Open overview</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Settings</div>
            <div class="demo-card-role">settings.php</div>
          </div>
        </div>
        <p class="demo-card-desc">Account &amp; login, 2FA, notifications, email, privacy, appearance, accessibility. Billing panel switches by CS path — Business CS view shows the $49/mo plan card, payment method, invoice history, and enterprise upgrade strip; Invited CS view shows the "covered by your linked provider, no cost" panel. Read-only profile summary card at top with "Edit Full Profile" CTA. Plan attestation timeline.</p>
        <div class="demo-card-params">settings.php · as=cs_marcus · or · as=cs_alternate · invited=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_settings'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Business CS view</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['cs_settings'], ['as' => 'cs_alternate', 'invited' => 'true']) ?>" target="_blank" rel="noopener">Invited CS view</a>
        </div>
      </article>

    </div>

    <!-- ── 2F. Plan countersignature lifecycle ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">2F</span> Plan lifecycle — CS perspective</h2>
      <p class="demo-section-sub">The CS interacts with the plan lifecycle at two critical points: countersigning to activate it, and re-certifying annually. Both surface in Important Documents.</p>
    </div>
    <div class="demo-grid two">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Countersignature — awaiting CS signature</div>
            <div class="demo-card-role">Plan can't go Active until CS signs</div>
          </div>
        </div>
        <p class="demo-card-desc">After a practitioner signs their Continuity Plan, it routes to the designated CS for countersignature. In Important Documents, Marcus sees a "Review &amp; Sign" CTA on plans awaiting his signature. Once he countersigns, plan status moves to Active and SS receives a read-only copy. Until countersigned, the plan is in "Pending CS Signature" limbo.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_important_docs'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Open Important Documents</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Annual re-certification</div>
            <div class="demo-card-role">CS confirms tasks are still accurate</div>
          </div>
        </div>
        <p class="demo-card-desc">Every year, after the practitioner re-attests the plan, the CS is asked to re-certify that the task list is still accurate and complete. The certification includes a whole-list sign-off plus optional per-task exception flags for tasks that are no longer applicable. Missing CS certification blocks the plan-active status chips on the practitioner's dashboard.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_my_tasks'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">My Tasks (cert flow)</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['cs_important_docs'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Important Documents</a>
        </div>
      </article>

    </div>

    <!-- ── 2G. Public profile ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">2G</span> Public profile</h2>
      <p class="demo-section-sub">Only Business-tier CSes have public profiles. Invited CSes are never publicly browsable — their slug intentionally 404s.</p>
    </div>
    <div class="demo-grid three">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Marcus's CS profile · viewed by himself</div>
            <div class="demo-card-role">Business CS · publicly resolvable</div>
          </div>
        </div>
        <p class="demo-card-desc">Business CS public profile at <span class="demo-inline-code">/public/continuity_steward.php?slug=marcus-chen</span>. Shows specialties, capacity, certifications, fee structure, Aegis Verified badge, performance stats, and a "Request a Consultation" CTA.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['public_steward'], ['slug' => 'marcus-chen', 'as' => 'cs_marcus']) ?>">View as Marcus</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Marcus's profile · viewed by a Provider</div>
            <div class="demo-card-role">Practitioner perspective — "Designate as My CS"</div>
          </div>
        </div>
        <p class="demo-card-desc">Sarah (a practitioner) views the same profile and sees a "Designate as My CS" CTA instead of the owner-view. This is the marketplace entry point — how practitioners discover and vet independent CSes before designating them in their plan.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['public_steward'], ['slug' => 'marcus-chen', 'as' => 'p_sarah', 'tier' => 'practice', 'cs_role' => '1']) ?>">View as Sarah (Provider)</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">PR</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Priya's profile → 404</div>
            <div class="demo-card-role">Invited tier — not publicly resolvable</div>
          </div>
        </div>
        <p class="demo-card-desc">Invited CSes are never publicly browsable regardless of viewer. The slug resolution step fails — <span class="demo-inline-code">cs_public=0</span> prevents the profile from rendering. Useful to confirm the access gate works correctly.</p>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['public_steward'], ['slug' => 'priya-raman']) ?>">View → 404</a>
        </div>
      </article>

    </div>

    <!-- ── 2H. Edge cases ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">2H</span> Edge cases</h2>
      <p class="demo-section-sub">Specific states and access gates that need dedicated demo links.</p>
    </div>
    <div class="demo-grid two">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Continuity Management · active incident</div>
            <div class="demo-card-role">Cockpit with SS report pre-filled</div>
          </div>
        </div>
        <p class="demo-card-desc">The verify cockpit with <code>emergency=true</code>: the SS's full narrative report is visible, documentation upload is required before activation, the 4-step modal is open. This is the exact state the CS lands in after receiving the alert — verify, upload docs, confirm, unlock vault.</p>
        <div class="demo-card-params">continuity-management.php · emergency=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['cs_continuity_management'], ['as' => 'cs_marcus', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Open cockpit (active)</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Document Vault · unlocked post-verification</div>
            <div class="demo-card-role">All 3 tabs accessible after CS verifies</div>
          </div>
        </div>
        <p class="demo-card-desc">The vault with <code>emergency=true</code>: all three tabs (Support Documents / Client Roster / Secure Credentials) are now readable. In standby only the Support Documents tab is visible; the other two are sealed. Every view, download, and credential reveal auto-logs to the activity feed as a permanent audit record.</p>
        <div class="demo-card-params">vault.php · emergency=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['cs_vault'], ['as' => 'cs_marcus', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Vault (unlocked)</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['cs_vault'], ['as' => 'cs_marcus']) ?>" target="_blank" rel="noopener">Vault (sealed — compare)</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">PR</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Invited CS — upgrade prompt on invite</div>
            <div class="demo-card-role">Free tier gate · locked invite button</div>
          </div>
        </div>
        <p class="demo-card-desc">Priya's Invited CS account is locked to one practitioner. On My Providers, the "Invite Practitioner" button is visually locked with a prompt explaining the Business CS tier ($49/mo) is required to serve multiple practitioners or proactively invite new ones.</p>
        <div class="demo-card-params">providers.php · as=cs_alternate · invited=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['cs_providers'], ['as' => 'cs_alternate', 'invited' => 'true']) ?>" target="_blank" rel="noopener">Open providers (locked)</a>
        </div>
      </article>

    </div>
  </section>
  <section class="demo-section">
    <div class="demo-section-head">
      <h2><span class="demo-section-num">3</span> Support Steward Portal</h2>
      <p class="demo-section-sub">
        Linda is the Primary SS for Sarah's plan; James is the Alternate. The SS's only job on a
        normal day is to stay informed. On a crisis day they are the first responder — they report,
        then step back while the CS acts. SS profiles are relationship-gated (not tier-gated).
      </p>
    </div>

    <!-- ── 3A. Identity & state ── -->
    <div class="demo-section-head" style="margin-top:24px">
      <h2 style="font-size:18px"><span class="demo-section-num">3A</span> Open as an SS identity</h2>
    </div>
    <div class="demo-grid two">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">LJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Linda Johnson</div>
            <div class="demo-card-role">SS · Primary · active</div>
          </div>
        </div>
        <p class="demo-card-desc">Primary SS for Sarah's plan. Calm state: green "All clear" status strip, prep checklist, gold rail on practitioner cards. Active-incident state: red topbar banner, alert-emergency above greeting, red pulse dot, report widget replaced with "Incident Reported" callout, ISW sidebar widget.</p>
        <div class="demo-card-tags">
          <span class="demo-tag green">Active</span>
          <span class="demo-tag gold">Primary</span>
        </div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['ss_portal'], ['as' => 'ss_linda', 'ss_role' => '1', 'emergency' => 'false']) ?>" target="_blank" rel="noopener">Open · calm</a>
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['ss_portal'], ['as' => 'ss_linda', 'ss_role' => '1', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Open · active incident</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">JR</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">James Rodriguez</div>
            <div class="demo-card-role">SS · Alternate · active</div>
          </div>
        </div>
        <p class="demo-card-desc">Alternate SS — Sarah's spouse and personal representative. Role chip and greet-meta strip both show "Alternate SS". No active incident seeded for James — useful for demoing the standby Alternate UX and Primary/Alternate handoff workflow.</p>
        <div class="demo-card-tags">
          <span class="demo-tag green">Active</span>
          <span class="demo-tag">Alternate</span>
        </div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['ss_portal'], ['as' => 'ss_james', 'ss_role' => '1', 'emergency' => 'false']) ?>" target="_blank" rel="noopener">Open · calm</a>
        </div>
      </article>

    </div>

    <!-- ── 3B. Incident UX matrix ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">3B</span> Incident UX — the two states that matter</h2>
      <p class="demo-section-sub">
        The SS portal has exactly two meaningful states: standby (everything is fine) and active
        incident (something happened). Every UI decision is built around this binary.
      </p>
    </div>
    <div class="demo-grid two">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">LJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Dashboard · Standby</div>
            <div class="demo-card-role">Normal day — calm and reassuring</div>
          </div>
        </div>
        <p class="demo-card-desc">Watch Status = "All clear" (green). Prep checklist visible in continuity card. Report Incident widget visible in sidebar. Practitioner cards show gold rail with task counts. No alert banner.</p>
        <div class="demo-card-params">emergency=false</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['ss_portal'], ['as' => 'ss_linda', 'ss_role' => '1', 'emergency' => 'false']) ?>" target="_blank" rel="noopener">Launch standby</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">LJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Dashboard · Active incident</div>
            <div class="demo-card-role">Crisis day — first responder</div>
          </div>
        </div>
        <p class="demo-card-desc">Top <code>alert-emergency</code> banner (incident type + reassuring "CS is handling it" copy). Topbar turns red with "Active Critical Incident". Red pulse dot on continuity card. Continuity card right panel switches from prep checklist to incident status summary. Sidebar report widget replaced with "Incident Reported" green callout + ISW progress widget.</p>
        <div class="demo-card-params">emergency=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['ss_portal'], ['as' => 'ss_linda', 'ss_role' => '1', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Launch with incident</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['ss_critical_incident_log'], ['as' => 'ss_linda', 'ss_role' => '1', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Jump to incident log</a>
        </div>
      </article>

    </div>

    <!-- ── 3C. Critical Incident Log — the trigger page ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">3C</span> Critical Incident Log — the trigger page</h2>
      <p class="demo-section-sub">
        The SS's primary action page. This is where the report is filed. The dashboard has a quick
        modal shortcut, but this page is the full form with documentation upload.
      </p>
    </div>
    <div class="demo-grid two">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">LJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Pre-report · Standby</div>
            <div class="demo-card-role">No active incident yet</div>
          </div>
        </div>
        <p class="demo-card-desc">Full trigger form: practitioner select, 7-type incident dropdown (opt-in types greyed when not enabled by provider), narrative textarea, contact-attempt checkboxes, documentation upload, and a false-reporting warning before submit.</p>
        <div class="demo-card-params">critical-incident-log.php · emergency=false</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['ss_critical_incident_log'], ['as' => 'ss_linda', 'ss_role' => '1', 'emergency' => 'false']) ?>" target="_blank" rel="noopener">Open form (pre-report)</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">LJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Post-report · Active</div>
            <div class="demo-card-role">Incident filed — status view</div>
          </div>
        </div>
        <p class="demo-card-desc">After submission the page switches to incident status view: timeline of events (report → CS notified → CS verified → tasks in progress), CS contact card, and a reassuring "You did the right thing" message. No re-submission possible.</p>
        <div class="demo-card-params">critical-incident-log.php · emergency=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['ss_critical_incident_log'], ['as' => 'ss_linda', 'ss_role' => '1', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Open status view (post-report)</a>
        </div>
      </article>

    </div>

    <!-- ── 3D. Cross-portal moment: SS reports → CS activates ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">3D</span> Cross-portal moment — SS reports, CS activates</h2>
      <p class="demo-section-sub">
        The most important workflow in Aegis. SS files the report; CS receives the alert and verifies.
        Open both side-by-side to demo the handoff.
      </p>
    </div>
    <div class="demo-grid two">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">LJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">SS side — incident reported</div>
            <div class="demo-card-role">Linda · post-report state</div>
          </div>
        </div>
        <p class="demo-card-desc">SS dashboard after filing the report. Alert banner reads "You did the right thing — it is being handled." Report widget replaced with green "Incident Reported" callout. ISW sidebar shows CS name, verified status, and task progress bar.</p>
        <div class="demo-card-params">ss_portal · emergency=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['ss_portal'], ['as' => 'ss_linda', 'ss_role' => '1', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Open SS side</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">CS side — verify cockpit</div>
            <div class="demo-card-role">Marcus · active incident received</div>
          </div>
        </div>
        <p class="demo-card-desc">CS dashboard the moment the alert arrives. Red alert-emergency banner with SS's narrative. "Verify Incident" modal pre-opened with 4-step activation flow: review SS report → upload docs → confirm → unlock vault. This is what Marcus sees after Linda reports.</p>
        <div class="demo-card-params">cs_portal · emergency=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['cs_portal'], ['as' => 'cs_marcus', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Open CS side</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['cs_continuity_management'], ['as' => 'cs_marcus', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Jump to verify cockpit</a>
        </div>
      </article>

    </div>

    <!-- ── 3E. Providers list — SS watching screen ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">3E</span> Providers list — standby vs active</h2>
    </div>
    <div class="demo-grid two">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">LJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">My Practitioners · Standby</div>
            <div class="demo-card-role">Normal watch — all clear</div>
          </div>
        </div>
        <p class="demo-card-desc">Full list of practitioners Linda is assigned to. Each row shows plan status, last check-in, CS contact. No red indicators.</p>
        <div class="demo-card-params">providers.php · emergency=false</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['ss_providers'], ['as' => 'ss_linda', 'ss_role' => '1', 'emergency' => 'false']) ?>" target="_blank" rel="noopener">Open standby</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">LJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">My Practitioners · Active incident</div>
            <div class="demo-card-role">Dr. Sarah Johnson flagged</div>
          </div>
        </div>
        <p class="demo-card-desc">Same list with active incident. Dr. Sarah Johnson's row shows red "Incident Active" chip and "View Status" button. Other practitioners unaffected. SS can see which provider triggered the incident and track CS response without any action required.</p>
        <div class="demo-card-params">providers.php · emergency=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['ss_providers'], ['as' => 'ss_linda', 'ss_role' => '1', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Open with incident</a>
        </div>
      </article>

    </div>

    <!-- ── 3F. Messages — with incident badge ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">3F</span> Messages — incident coordination thread</h2>
    </div>
    <div class="demo-grid two">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">LJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Messages · Active incident</div>
            <div class="demo-card-role">CS coordination thread visible</div>
          </div>
        </div>
        <p class="demo-card-desc">During an active incident the CS may message the SS for coordination details (emergency contacts, office keys, context). Inbox shows the unread thread with incident badge. SS can reply but cannot reassign or close the incident.</p>
        <div class="demo-card-params">messages.php · emergency=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['ss_portal'] . '/../support-steward-portal/messages.php', ['as' => 'ss_linda', 'ss_role' => '1', 'emergency' => 'true']) ?>" target="_blank" rel="noopener">Open with incident badge</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">LJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Activity log · Incident module</div>
            <div class="demo-card-role">Full incident timeline</div>
          </div>
        </div>
        <p class="demo-card-desc">Activity feed filtered to the incident module. Shows full timeline: SS filed report → CS received alert → CS verified → vault unsealed → tasks in progress. Each event timestamped and attributed. SS read-only; no action buttons except "View Full Incident".</p>
        <div class="demo-card-params">activity.php · module=incident · emergency=true</div>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['ss_portal'] . '/../support-steward-portal/activity.php', ['as' => 'ss_linda', 'ss_role' => '1', 'emergency' => 'true', 'module' => 'incident']) ?>" target="_blank" rel="noopener">Incident activity log</a>
        </div>
      </article>

    </div>

    <!-- ── 3G. Public profile — relationship-gated ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">3G</span> Public profile — relationship-gated</h2>
      <p class="demo-section-sub">
        SS profiles ignore the three-tier model entirely. Visible <strong>only</strong> to the Provider listed in
        <code>users.invited_by_id</code> and the SS themselves. Every other viewer gets a 404 — the page
        must not leak that this slug exists.
      </p>
    </div>
    <div class="demo-grid two">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Linked Provider · Sarah</div>
            <div class="demo-card-role">invited_by_id match — works</div>
          </div>
        </div>
        <p class="demo-card-desc">Sarah invited Linda, so the relationship gate opens. Full profile renders with all sections + Provider quick actions (Message, Remove).</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['public_support_steward'], ['slug' => 'linda-johnson', 'as' => 'p_sarah']) ?>">View as Sarah</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">LJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Owner · Linda</div>
            <div class="demo-card-role">Self-view — works</div>
          </div>
        </div>
        <p class="demo-card-desc">Linda viewing her own profile. Edit Profile button replaces Provider's Message + Remove actions.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['public_support_steward'], ['slug' => 'linda-johnson', 'as' => 'ss_linda', 'ss_role' => '1']) ?>">View as Linda</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Unrelated CS · Marcus</div>
            <div class="demo-card-role">No relationship — 404</div>
          </div>
        </div>
        <p class="demo-card-desc">Marcus is CS on Sarah's plan but did not invite Linda. He has no business seeing this SS profile — resolver returns 404.</p>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['public_support_steward'], ['slug' => 'linda-johnson', 'as' => 'cs_marcus']) ?>">View → 404</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar anon">—</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Anonymous viewer</div>
            <div class="demo-card-role">Not signed in — 404</div>
          </div>
        </div>
        <p class="demo-card-desc">No viewer = no relationship. Always 404, never a sign-in prompt — the page must not even leak that this slug exists.</p>
        <div class="demo-card-actions">
          <a class="btn btn-danger btn-sm" href="<?= lnk($URL['public_support_steward'], ['slug' => 'linda-johnson']) ?>">View → 404</a>
        </div>
      </article>

    </div>

    <!-- SS profile lifecycle states -->
    <div class="demo-section-head" style="margin-top:24px">
      <h2 style="font-size:18px"><span class="demo-section-num">3G·</span> SS profile lifecycle states</h2>
      <p class="demo-section-sub">
        All viewed as Sarah (the linked Provider). Relationship intact but SS in a non-active state.
      </p>
    </div>
    <div class="demo-grid three">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">RP</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Rachel Pham · Suspended</div>
            <div class="demo-card-role">Paused access, relationship on record</div>
          </div>
        </div>
        <p class="demo-card-desc">SS with paused access — relationship still on record. Profile shows suspended-state layout with reinstate hint for the Provider.</p>
        <div class="demo-card-actions">
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['public_support_steward'], ['slug' => 'rachel-pham', 'as' => 'p_sarah']) ?>">View (as Sarah)</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">JT</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Jordan Taylor · Pending invite</div>
            <div class="demo-card-role">Awaiting acceptance</div>
          </div>
        </div>
        <p class="demo-card-desc">Invited but hasn't accepted yet. Profile renders the awaiting-acceptance banner and resend-invitation quick action for the Provider.</p>
        <div class="demo-card-actions">
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['public_support_steward'], ['slug' => 'jordan-taylor', 'as' => 'p_sarah']) ?>">View (as Sarah)</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">BS</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Brian Santos · Declined</div>
            <div class="demo-card-role">Invitation declined</div>
          </div>
        </div>
        <p class="demo-card-desc">Declined the invitation. Profile renders the declined-state alert; no active relationship and no portal access.</p>
        <div class="demo-card-actions">
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['public_support_steward'], ['slug' => 'brian-santos', 'as' => 'p_sarah']) ?>">View (as Sarah)</a>
        </div>
      </article>

    </div>
  </section>

  <section class="demo-section">
    <div class="demo-section-head">
      <h2><span class="demo-section-num">4</span> Business Partner Portal</h2>
      <p class="demo-section-sub">
        Acme is the multi-person agency, Jamal is the solo freelancer.
        Both surface the same job/proposal/contract pipeline with slightly different UX.
      </p>
    </div>

    <!-- ── 4A. Identity ── -->
    <div class="demo-section-head" style="margin-top:24px">
      <h2 style="font-size:18px"><span class="demo-section-num">4A</span> Open as a BP identity</h2>
    </div>
    <div class="demo-grid two">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">AP</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Acme Practice Services</div>
            <div class="demo-card-role">BP · Agency</div>
          </div>
        </div>
        <p class="demo-card-desc">Multi-person agency with full job/proposal/contract pipeline. Owner of <span class="demo-inline-code">/business/acme-practice-services</span>.</p>
        <div class="demo-card-tags">
          <span class="demo-tag blue">BP Agency</span>
        </div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_portal'], ['as' => 'bp_acme']) ?>" target="_blank" rel="noopener">Open portal</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">JW</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Jamal Washington</div>
            <div class="demo-card-role">BP · Freelancer</div>
          </div>
        </div>
        <p class="demo-card-desc">Solo freelance BP. Same pipeline as Acme but smaller scale — useful for testing the freelancer-vs-agency UX split.</p>
        <div class="demo-card-tags">
          <span class="demo-tag blue">BP Freelancer</span>
        </div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_portal'], ['as' => 'bp_jamal']) ?>" target="_blank" rel="noopener">Open portal</a>
        </div>
      </article>

    </div>

    <!-- ── 4B. Page deep-links ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">4B</span> Page deep-links</h2>
    </div>
    <div class="demo-grid two">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">AP</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Find Jobs feed</div>
            <div class="demo-card-role">Marketplace board</div>
          </div>
        </div>
        <p class="demo-card-desc">Practitioner-posted jobs across accounting, billing, compliance, technology, legal, marketing. Searchable + filterable. Send proposal direct.</p>
        <div class="demo-card-params">find-jobs.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_find_jobs'], ['as' => 'bp_acme']) ?>" target="_blank" rel="noopener">Browse Jobs</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">AP</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Contracts &amp; Milestones</div>
            <div class="demo-card-role">Job → proposal → contract → invoice</div>
          </div>
        </div>
        <p class="demo-card-desc">Agency view includes per-milestone team-assignment column. Freelancer view is leaner. Submit-work flow differs (Agency owner reviews first vs Freelancer direct submit).</p>
        <div class="demo-card-params">contracts.php · milestones.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_contracts'], ['as' => 'bp_acme']) ?>" target="_blank" rel="noopener">Contracts</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['bp_milestones'], ['as' => 'bp_acme']) ?>" target="_blank" rel="noopener">Milestones</a>
        </div>
      </article>

    </div>

    <!-- ── 4C. Public profiles ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">4C</span> Public profiles</h2>
    </div>
    <div class="demo-grid three">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">AP</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Acme's BP profile</div>
            <div class="demo-card-role">Agency public-facing page</div>
          </div>
        </div>
        <p class="demo-card-desc">Public Agency profile at <span class="demo-inline-code">/business/acme-practice-services</span>.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['public_business'], ['slug' => 'acme-practice-services', 'as' => 'bp_acme']) ?>">View profile</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">JW</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Jamal's BP profile</div>
            <div class="demo-card-role">Freelancer public-facing page</div>
          </div>
        </div>
        <p class="demo-card-desc">Public Freelancer profile at <span class="demo-inline-code">/business/jamal-washington</span>.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['public_business'], ['slug' => 'jamal-washington', 'as' => 'bp_jamal']) ?>">View profile</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Marcus's BP profile</div>
            <div class="demo-card-role">Multi-role: CS + BP same identity</div>
          </div>
        </div>
        <p class="demo-card-desc">Marcus holds both CS and BP roles, so he owns two distinct public profiles. This one is the BP face of his identity at <span class="demo-inline-code">/business/marcus-chen</span>.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['public_business'], ['slug' => 'marcus-chen', 'as' => 'cs_marcus']) ?>">View profile</a>
        </div>
      </article>

    </div>

    <!-- ── 4D. Agency experience (bp_acme) ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">4D</span> Agency experience — Acme Practice Services</h2>
      <p class="demo-section-sub">Full agency view with team management, active contracts, proposal pipeline, and milestone tracking. Uses <span class="demo-inline-code">?as=bp_acme</span>.</p>
    </div>
    <div class="demo-grid three">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">AP</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Agency Dashboard</div>
            <div class="demo-card-role">Revenue snapshot &middot; Team capacity</div>
          </div>
        </div>
        <p class="demo-card-desc">Agency dashboard with active contract rail, overdue milestone alert, Team Capacity sidebar, revenue snapshot (blurred by default), and notification feed.</p>
        <div class="demo-card-params">dashboard.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_dashboard'], ['as' => 'bp_acme']) ?>" target="_blank" rel="noopener">Open</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">AP</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Find Jobs &mdash; Agency</div>
            <div class="demo-card-role">Browse &middot; filter &middot; send proposals</div>
          </div>
        </div>
        <p class="demo-card-desc">25 open jobs across billing, compliance, technology, marketing, legal, HR, and design. Agency view includes Team Assignment field on proposals.</p>
        <div class="demo-card-params">find-jobs.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_find_jobs'], ['as' => 'bp_acme']) ?>" target="_blank" rel="noopener">Browse Jobs</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">AP</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Contracts &amp; Milestones</div>
            <div class="demo-card-role">Active &middot; milestone billing</div>
          </div>
        </div>
        <p class="demo-card-desc">1 active contract (SimplePractice migration for Dr. Sarah Johnson). Milestone billing: Phase 1 paid, Phase 2 approved, Phase 3 in progress.</p>
        <div class="demo-card-params">contracts.php &middot; milestones.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_contracts'], ['as' => 'bp_acme']) ?>" target="_blank" rel="noopener">Contracts</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['bp_milestones'], ['as' => 'bp_acme']) ?>" target="_blank" rel="noopener">Milestones</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">AP</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Proposals Pipeline</div>
            <div class="demo-card-role">Pending &middot; accepted &middot; withdrawn</div>
          </div>
        </div>
        <p class="demo-card-desc">3 open proposals: billing (pending), IT support (pending), website redesign (pending). 1 accepted (virtual admin). Withdraw or edit from this view.</p>
        <div class="demo-card-params">proposals.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_proposals'], ['as' => 'bp_acme']) ?>" target="_blank" rel="noopener">Proposals</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">AP</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Finances &amp; Invoices</div>
            <div class="demo-card-role">Invoiced &middot; received &middot; outstanding</div>
          </div>
        </div>
        <p class="demo-card-desc">Revenue from 2 paid invoices ($600 + $700). Outstanding balance from Phase 3 milestone. Privacy toggle hides all figures by default.</p>
        <div class="demo-card-params">finances.php &middot; invoices.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_finances'], ['as' => 'bp_acme']) ?>" target="_blank" rel="noopener">Finances</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['bp_invoices'], ['as' => 'bp_acme']) ?>" target="_blank" rel="noopener">Invoices</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">AP</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Team Management</div>
            <div class="demo-card-role">Agency-only &middot; capacity view</div>
          </div>
        </div>
        <p class="demo-card-desc">Agency-exclusive page. Shows team capacity bar, per-member project counts, active/idle/inactive status badges, and Add Member modal.</p>
        <div class="demo-card-params">team.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_team'], ['as' => 'bp_acme']) ?>" target="_blank" rel="noopener">Team</a>
        </div>
      </article>

    </div>

    <!-- ── 4E. Freelancer experience (bp_jamal) ── -->
    <div class="demo-section-head" style="margin-top:32px">
      <h2 style="font-size:18px"><span class="demo-section-num">4E</span> Freelancer experience &mdash; Jamal Washington</h2>
      <p class="demo-section-sub">Leaner solo view &mdash; no Team section, availability widget replaces Team Capacity, billing labelled as Earnings. Uses <span class="demo-inline-code">?as=bp_jamal</span>.</p>
    </div>
    <div class="demo-grid three">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">JW</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Freelancer Dashboard</div>
            <div class="demo-card-role">My Availability &middot; Earnings toggle</div>
          </div>
        </div>
        <p class="demo-card-desc">Freelancer dashboard with My Availability widget (pencil icon to update), Active Clients KPI, Earnings blurred by default, and profile views count.</p>
        <div class="demo-card-params">dashboard.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_dashboard'], ['as' => 'bp_jamal']) ?>" target="_blank" rel="noopener">Open</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">JW</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Find Jobs &mdash; Freelancer</div>
            <div class="demo-card-role">Individual hire &middot; no team assignment</div>
          </div>
        </div>
        <p class="demo-card-desc">Same 25-job marketplace. Freelancer proposal form hides the Team Assignment section. Urgency badges shown for time-sensitive postings (Medicaid billing, credentialing).</p>
        <div class="demo-card-params">find-jobs.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_find_jobs'], ['as' => 'bp_jamal']) ?>" target="_blank" rel="noopener">Browse Jobs</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">JW</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Proposals &amp; Contracts</div>
            <div class="demo-card-role">Accepted &middot; tax filing contract</div>
          </div>
        </div>
        <p class="demo-card-desc">Jamal has 1 accepted proposal on job_001 (billing) and 1 active contract (2025 tax filing &amp; S-corp setup for Dr. Sarah Johnson). Invoice paid.</p>
        <div class="demo-card-params">proposals.php &middot; contracts.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_contracts'], ['as' => 'bp_jamal']) ?>" target="_blank" rel="noopener">Contracts</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['bp_proposals'], ['as' => 'bp_jamal']) ?>" target="_blank" rel="noopener">Proposals</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">JW</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Earnings &amp; Invoices</div>
            <div class="demo-card-role">Paid &middot; outstanding &middot; freelancer view</div>
          </div>
        </div>
        <p class="demo-card-desc">Freelancer finances labelled &ldquo;Earnings&rdquo; (not Revenue). 1 paid invoice $2,400 for tax filing. No team cost overhead &mdash; all income is personal earnings.</p>
        <div class="demo-card-params">finances.php &middot; invoices.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_finances'], ['as' => 'bp_jamal']) ?>" target="_blank" rel="noopener">Finances</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['bp_invoices'], ['as' => 'bp_jamal']) ?>" target="_blank" rel="noopener">Invoices</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">JW</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Activity Log &amp; Messages</div>
            <div class="demo-card-role">Notifications &middot; practitioner threads</div>
          </div>
        </div>
        <p class="demo-card-desc">Activity feed with proposal_sent and milestone_done events. Messages show practitioner conversation threads. Sidebar shows Activity Log (not Notifications).</p>
        <div class="demo-card-params">activity.php &middot; messages.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_activity'], ['as' => 'bp_jamal']) ?>" target="_blank" rel="noopener">Activity</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['bp_messages'], ['as' => 'bp_jamal']) ?>" target="_blank" rel="noopener">Messages</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">JW</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Edit Profile</div>
            <div class="demo-card-role">Freelancer identity &middot; rate &middot; bio</div>
          </div>
        </div>
        <p class="demo-card-desc">Profile completion strip links here. Freelancer fields include hourly rate, availability, bio, and service categories. No team/agency fields shown.</p>
        <div class="demo-card-params">edit-profile.php</div>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['bp_edit_profile'], ['as' => 'bp_jamal', 'tier' => 'practice', 'services' => '1']) ?>" target="_blank" rel="noopener">Edit Profile</a>
        </div>
      </article>

    </div>
  </section>

  <!-- ═════════════════════════════════════════════════════════
       5. Onboarding flows
       ═════════════════════════════════════════════════════════ -->
  <section class="demo-section">
    <div class="demo-section-head">
      <h2><span class="demo-section-num">5</span> Onboarding flows</h2>
      <p class="demo-section-sub">
        Eight signup paths. <em>Preview</em> walks the full multi-step flow; <em>Fast</em> skips
        non-blocking steps for a quick end-to-end loop.
      </p>
    </div>

    <div class="demo-grid three">

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Practitioner · Practice</div>
            <div class="demo-card-role">Full-tier signup</div>
          </div>
        </div>
        <p class="demo-card-desc">Full Continuity Practice signup. Lands in the provider portal with all nav unlocked.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['onboarding'], ['role' => 'practitioner', 'tier' => 'practice']) ?>" target="_blank" rel="noopener">Preview</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['onboarding'], ['role' => 'practitioner', 'tier' => 'practice', 'fast' => '1']) ?>" target="_blank" rel="noopener">Fast</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Practitioner · Access</div>
            <div class="demo-card-role">Limited-tier signup</div>
          </div>
        </div>
        <p class="demo-card-desc">Continuity Access signup. Lands in provider portal with Referrals and My Services locked.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['onboarding'], ['role' => 'practitioner', 'tier' => 'access']) ?>" target="_blank" rel="noopener">Preview</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['onboarding'], ['role' => 'practitioner', 'tier' => 'access', 'fast' => '1']) ?>" target="_blank" rel="noopener">Fast</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar">SJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Practitioner · Practice + Services</div>
            <div class="demo-card-role">Full tier with Services mode on</div>
          </div>
        </div>
        <p class="demo-card-desc">Practice tier with Integrative Business Services enabled at signup time.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['onboarding'], ['role' => 'practitioner', 'tier' => 'practice', 'services' => '1']) ?>" target="_blank" rel="noopener">Preview</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['onboarding'], ['role' => 'practitioner', 'tier' => 'practice', 'services' => '1', 'fast' => '1']) ?>" target="_blank" rel="noopener">Fast</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">MC</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Continuity Steward · Business</div>
            <div class="demo-card-role">Independent paid signup</div>
          </div>
        </div>
        <p class="demo-card-desc">Standalone CS account for independent stewards serving multiple practitioners.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['onboarding'], ['role' => 'cs', 'path' => 'business']) ?>" target="_blank" rel="noopener">Preview</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['onboarding'], ['role' => 'cs', 'path' => 'business', 'fast' => '1']) ?>" target="_blank" rel="noopener">Fast</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar cs">PR</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Continuity Steward · Invited</div>
            <div class="demo-card-role">Free, linked-account signup</div>
          </div>
        </div>
        <p class="demo-card-desc">Free invited-CS flow. Skips payment entirely, lands in CS portal as <span class="demo-inline-code">invited=true</span>.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['onboarding'], ['role' => 'cs', 'path' => 'invited']) ?>" target="_blank" rel="noopener">Preview</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['onboarding'], ['role' => 'cs', 'path' => 'invited', 'fast' => '1']) ?>" target="_blank" rel="noopener">Fast</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar ss">LJ</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Support Steward · Invite gate</div>
            <div class="demo-card-role">Blocked-signup landing</div>
          </div>
        </div>
        <p class="demo-card-desc">Shows the gate explaining SS accounts must be invited — no public signup.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['onboarding'], ['role' => 'support-steward']) ?>" target="_blank" rel="noopener">Open gate</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar bp">AP</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Business Partner</div>
            <div class="demo-card-role">Full BP signup</div>
          </div>
        </div>
        <p class="demo-card-desc">Business profile, services, pricing, subscription — full flow.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= lnk($URL['onboarding'], ['role' => 'business-partner']) ?>" target="_blank" rel="noopener">Preview</a>
          <a class="btn btn-outline btn-sm" href="<?= lnk($URL['onboarding'], ['role' => 'business-partner', 'fast' => '1']) ?>" target="_blank" rel="noopener">Fast</a>
        </div>
      </article>

      <article class="demo-card">
        <div class="demo-card-head">
          <span class="demo-card-avatar anon">→</span>
          <div class="demo-card-identity">
            <div class="demo-card-name">Standard onboarding</div>
            <div class="demo-card-role">Role-selection start page</div>
          </div>
        </div>
        <p class="demo-card-desc">The normal onboarding entry point with no demo flags applied.</p>
        <div class="demo-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= h($URL['onboarding']) ?>" target="_blank" rel="noopener">Open</a>
          <a class="btn btn-outline btn-sm" href="<?= h($URL['login']) ?>" target="_blank" rel="noopener">Sign in</a>
        </div>
      </article>

    </div>
  </section>


  <!-- Footer note -->
  <div class="demo-foot-note">
    <strong>How persistence works.</strong> The shared JS layer intercepts every internal link click and pushState transition, auto-injecting the demo flags
    (<code>as</code>, <code>tier</code>, <code>services</code>, <code>emergency</code>, <code>invited</code>, <code>cs_role</code>, <code>ss_role</code>, <code>cs_account_type</code>) into the destination URL.
    Set them once via any card above and they ride along as you navigate the portal.
    The <code>?as=</code> override is gated to <code>localhost</code>, <code>127.0.0.1</code>, and <code>*.devlet.tech</code> server-side, so it's safe in production environments.
    <br><br>
    <strong>New role params.</strong> <code>cs_role=1</code> and <code>ss_role=1</code> reveal the "Other Portals" section in the header dropdown — the CS Portal and SS Portal switcher links only appear when the flag is set.
    <code>cs_account_type=addon</code> (default) or <code>cs_account_type=business</code> changes the sub-label under the CS Portal link.
  </div>

</div>

</body>
</html>
