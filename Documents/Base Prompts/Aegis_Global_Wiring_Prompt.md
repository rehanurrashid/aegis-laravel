# Aegis Wiring Prompt — single-file pass

Step 1 of 2. Wires one portal page into the centralized Aegis system. Pair with the Design Prompt in the same chat turn for a complete refit.

---

## What this prompt does

Take the file the user gives you and: hook it into the backend (`models.php` helpers + `seed.json` data), retarget asset paths to root globals, swap in centralized includes (`sidebar.php`, `header.php`), rename outdated filename references, drop local JS that duplicates `_shared.js`, audit every button/link for missing modal completion, convert raw file inputs to Dropzone, and ensure module-scoped activity links.

**No design refinement here.** Visual centralization is Phase 2 (Design Prompt). Don't restyle anything in this pass.

---

## Scope — files you can edit without asking

| File | When you'll touch it |
|---|---|
| **`<target>.php`** (the file the user gave you) | always — the primary target |
| **`models.php`** | when the page needs data with no existing `aegis_get_*` helper |
| **`models_write.php`** | when the page needs a new write helper (origination layer) |
| **`/_shared/save_*.php`** | when the page's write actions need a new endpoint case routed |
| **`seed.json`** | when the page needs data that isn't seeded yet, or needs more rows to render meaningfully |
| **`activity_body.php`** | when this page introduces a new activity module slug |
| **`icons.php`** | when the page needs an icon name not yet in the canonical map (propose adding it; don't inline `<svg>`) |

**Out of scope** — flag in Phase 1, never edit silently:
- `_shared.css`, `_shared.js` — design tokens & global helpers
- `sidebar.php` — finalized; do NOT re-edit
- `db.php` — host-detection logic & schema CHECK constraints (widening a CHECK constraint requires explicit owner approval and a seed-completeness pass — see "Seed-data completeness gate" below)
- The Wiring / Design / Tone prompts themselves — propose new rules for approval
- Pricing constants, the 7 approved critical-incident types, terminology rules
- The 16 `save_*.php` endpoints' auth gates and JSON contract — only add new `case` branches, never edit the gate/header structure

---

## Workflow

```
Phase 1 — Read & plan              (output a brief plan; auto-proceed unless out-of-scope items appear)
Phase 2 — Read-path edits          (Edits 1–9: surgical str_replace only)
Phase 3 — Write-path edits         (Edits 10–14: page wrapper + endpoint case + helper + activity fan-out)
Phase 4 — Verify & deliver         (pre-flight; export; present_files; summary)
```

**Don't pause between phases.** Output the Phase 1 plan, then immediately start Phase 2. The only stop is if Phase 1 surfaces an out-of-scope change that needs approval, or if Phase 3 hits the seed-data completeness gate.

**After Phase 4:** the page is wired but not yet toned. The Tone & Voice pass (`Aegis_Tone_Voice_Prompt.md`) is the mandatory 4th standard pass — see "Tone pass" below.

---

## Phase 1 — Read & plan

Read in this order:

1. `AEGIS-PROJECT-CONTEXT.md` (terminology, roles, file structure, §6.5 write-path layer, §9 third-party tools)
2. `CENTRALIZED-SYSTEM.md` (inventory of shared files, write-action pattern)
3. This prompt + Design Prompt (workflow and conversion rules)
4. `_shared.css` (canonical components — full read)
5. `_shared.js` (global helpers — full read)
6. `models.php` (read helpers, `AEGIS_TIER_LIMITS`)
7. `models_write.php` (write helpers — what exists vs what you'll add)
8. `icons.php` (canonical icon names)
9. `seed.json` (what's available for this page; schema CHECK constraints in `db.php` if write helpers will touch new enum values)
10. The target file (full read)

Then output ONE brief plan block:

```
Portal: <folder> · User: <default_user> · Role: <role> · Tier: <tier>
Target: <filename>.php

Data scope (read path)
- Helpers I'll reuse: <list>
- Helpers I'll add to models.php: <name + 1-line purpose>
- Seed keys present: <key (n rows)>
- Seed rows I'll add: <key + count + states>

Write scope (write path)
- Write helpers I'll reuse from models_write.php: <list>
- Write helpers I'll add: <name + 1-line purpose + which activity events it fans out>
- Endpoint(s) this page calls: <save_X.php · save_Y.php>
- New action cases I'll add to those endpoints: <action key + payload shape>
- Cross-portal activity recipients: <e.g. "CS feed on assignment", "SS feed on incident report">

Page audit
- Hardcoded data to replace: <count + brief>
- Modal "Save/Submit/Confirm" buttons missing a fetch wrapper: <count + brief>
- Local global-helper redefinitions: <list — openModal/closeModal/showToast/toggleSwitch/etc.>
- Old filename references: <count + brief>
- alert() / confirm() / title="" patterns: <counts>
- Real file uploads needing Dropzone: <count>
- Activity-button needed: yes/no (module slug if yes)
- Tier gating: <what + how>

Seed-data completeness (pre-flight for Phase 3)
- Schema CHECK constraints adequate for new writes: yes/no — <if no, flag>
- Required relationship rows seeded: yes/no — <if no, what's missing>
- All user references in seed exist as user records: yes/no

Out-of-scope items flagged for approval
- <thing>: <why I'd touch it>
```

Auto-proceed to Phase 2 unless the flagged section has items or seed-data completeness is "no".

---

## Phase 2 — Read-path edits

### Edit 1–3: Auth block, asset paths, centralized includes

Top of file:

```php
<?php
declare(strict_types=1);
define('AEGIS_ENTRY', true);
require_once __DIR__ . '/../_shared/models.php';
require_once __DIR__ . '/../_shared/icons.php';

$current_user = aegis_current_user('p_sarah');             // default per portal table
if (!$current_user || $current_user['role'] !== 'practitioner') {
    header('Location: /reset.php?token=aegis-demo-reset'); exit;
}
$active_page = 'finances';                                 // filename without .php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Finances | Aegis</title>
  <link rel="icon" type="image/svg+xml" href="/aegis-favicon.svg">
  <link rel="stylesheet" href="/_shared.css">
  <!-- Dropzone CDN — only if Edit 8 applies on this page -->
  <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css">
  <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js" defer></script>
<style>
/* Page-local CSS only — no globals here */
</style>
</head>
<body>

<?php include __DIR__ . '/../_shared/sidebar.php'; ?>
<div class="main-content">
<?php include __DIR__ . '/../_shared/header.php'; ?>
<div class="page-body">

  <!-- CONTENT -->

</div><!-- /page-body -->
</div><!-- /main-content -->

<!-- MODALS -->

<script src="/_shared.js"></script>
<script>
/* Page-local JS only — no global helper redefinitions */
</script>
</body>
</html>
```

**Defaults per portal:**

| Folder | Default user | Role string |
|---|---|---|
| `/provider-portal/` | `p_sarah` | `practitioner` |
| `/continuity-steward-portal/` | `cs_marcus` | `continuity_steward` |
| `/support-steward-portal/` | `ss_linda` | `support_steward` |
| `/biz-portal/` | `bp_acme` | `business_partner` |

BP pages also add right after `$active_page`:
```php
$bp_type = $current_user['bp_type'] ?? 'agency';
```

---

### Edit 4: Wire dynamic data (with seed.json + models.php updates)

Replace every hardcoded array with `aegis_get_*()` calls. **Use `$current_user['id']` as a string slug, never `(int)` cast.** Use `display_name`, not bespoke field names.

**Standard helpers:**

| Data needed | Helper |
|---|---|
| Continuity plan for practitioner | `aegis_get_plan_by_practitioner($current_user['id'])` |
| Plan stewards | `aegis_get_plan_stewards($plan_id)` |
| Plan tasks | `aegis_get_plan_tasks($plan_id)` |
| Plan incident configs | `aegis_get_plan_incident_configs($plan_id)` |
| Providers (for CS/SS) | `aegis_get_providers_for_steward($current_user['id'], 'continuity_steward'\|'support_steward')` |
| CS active incidents | `aegis_get_incidents_for_cs($current_user['id'])` |
| Activity feed | `aegis_get_activity($current_user['id'], 50, ['module' => $slug])` |
| Message threads | `aegis_get_threads_for_user($current_user['id'])` |
| BP open jobs / new jobs | `aegis_get_open_jobs()` / `aegis_get_new_jobs_for_bp($current_user['id'])` |
| BP contracts / proposals / milestones / invoices | `aegis_get_contracts_for_bp` / `_proposals_for_bp` / `_milestones_for_bp` / `_invoices_for_bp` |
| BP earnings / badges | `aegis_get_bp_earnings($id)` / `aegis_count_bp_badges($id)` |
| Current tier (from URL) | `aegis_current_tier($_GET)` |
| Tier limit | `aegis_tier_limit($tier, 'cs'\|'ss')` |

**Adding a new helper to `models.php`:**

Group with related helpers (USERS / PLANS / STEWARDS / VAULT / BP / FINANCES). Pattern:

```php
function aegis_get_transactions_for_user(string $user_id, int $limit = 50, array $filters = []): array {
    $db = aegis_db();
    $sql = "SELECT * FROM transactions WHERE user_id = ?";
    $params = [$user_id];
    if (!empty($filters['type']))   { $sql .= " AND type = ?";   $params[] = $filters['type']; }
    if (!empty($filters['status'])) { $sql .= " AND status = ?"; $params[] = $filters['status']; }
    $sql .= " ORDER BY occurred_at DESC LIMIT ?";
    $params[] = $limit;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

**Adding rows to `seed.json`:**

Never overwrite the whole file. Use `str_replace` to insert into an existing array. Preserve existing IDs/UUIDs/relationships. After every edit, validate JSON parses:

```bash
python3 -c "import json; json.load(open('seed.json'))" && echo "OK"
```

Add enough rows that the page renders meaningfully — typically **4–8 rows with mixed states** (active, pending, expired, declined) so stat counts, filters, and pagination all have something to show. Not just 1–2 demo rows.

---

### Edit 5: Rename outdated filenames

Every `href`, `action`, inline `window.location='…'`, and PHP redirect:

| Old | New |
|---|---|
| `index.php` | `dashboard.php` |
| `assignments.php` / `tasks.php` | `my-tasks.php` |
| `executors.php` | `continuity-stewards.php` |
| `dsr.php` | `support-stewards.php` |
| `agreements.php` | `important-documents.php` |
| `documents.php` | `vault.php` |
| `emergency.php` (CS portal) | `continuity-management.php` |
| `emergency.php` (SS portal) | `critical-incident-log.php` |
| `financials.php` / `billing.php` | `finances.php` |
| `providers.php` (practitioner dropdown) | `my-tasks.php` (practitioner has no providers page) |

---

### Edit 6: Drop local helpers; use `_shared.js`

Every local definition that shadows a global is a **bug source** (double-fired events, modal glitches, toggle stutter). Delete all of them:

| Local pattern | What to do |
|---|---|
| `function openModal()` | **DELETE LOCAL** — use global |
| `function closeModal()` / `closeAllModals()` | **DELETE LOCAL** — use global |
| `function showToast()` (any variant, any toast container) | **DELETE LOCAL + delete toast container div** — use global |
| `function confirmAction()` or any wrapper around `confirm()` | **DELETE LOCAL** — use global |
| `function toggleSwitch()` / `this.classList.toggle('on')` | Call `toggleSwitch(this)` |
| `function switchTab()` (uses `data-tab-group`) | **DELETE LOCAL** — use global |
| `function toggleDropdown()` / `closeAllDropdowns()` | **DELETE LOCAL** — use global |
| `function navigateTo()` | **DELETE LOCAL** — use global |
| `function aegisSlugify()` | **DELETE LOCAL** — use global |
| Modal overlay-click handler (`document.addEventListener('click', ...)`) | **DELETE LOCAL** — globally wired |
| Escape keydown for modal close | **DELETE LOCAL** — globally wired |
| `alert('msg')` | `showToast('msg', 'info'\|'success'\|'error'\|'warning')` |
| `confirm('?') && fn()` | `confirmAction('?', fn, {title, btnLabel, type:'danger'})` |
| `title="text"` on icon-only button | `data-tip="text"` (keep `title` on form `<input>` for a11y) |
| `this.style.borderColor = 'var(--blue-dark)'` for selection | `this.classList.add('selected')` + CSS `.selected` rule |

**Legitimate page-local tab switcher:** if a page uses a non-standard tab pattern (e.g. `data-tab` + `display:none` instead of `data-tab-group`), **rename** to avoid shadowing the global: `switchDsrTab`, `switchBpTab`, `switchFinanceTab`. Never name a local function `switchTab` — it shadows the global.

---

### Edit 7: Modal completeness & button wiring

#### 7a — Add missing modals

Inventory every button, link, and inline onclick. For each action needing user input or confirmation, ensure a modal exists. Add missing ones using the canonical templates:

**Confirm (destructive):**
```html
<div class="modal-overlay" id="confirmDeleteModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-title">Delete <thing>?</div>
      <button class="modal-close" onclick="closeModal('confirmDeleteModal')"><?= aegis_icon('x', 13) ?></button>
    </div>
    <div class="modal-body">
      <div class="alert alert-danger">
        <div class="alert-icon"><?= aegis_icon('alert-triangle', 18) ?></div>
        <div class="alert-content">
          <div class="alert-title">This can't be undone.</div>
          <div>The item will be permanently removed.</div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('confirmDeleteModal')">Cancel</button>
      <button class="btn btn-danger" onclick="…">Delete</button>
    </div>
  </div>
</div>
```

**Form (edit/create/invite):**
```html
<div class="modal-overlay" id="editThingModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Edit <thing></div>
      <button class="modal-close" onclick="closeModal('editThingModal')"><?= aegis_icon('x', 13) ?></button>
    </div>
    <div class="modal-body">
      <div class="row-2">
        <div class="form-group"><label class="form-label">First Name</label><input class="form-input" type="text"></div>
        <div class="form-group"><label class="form-label">Last Name</label><input class="form-input" type="text"></div>
      </div>
      <div class="form-group">
        <label class="form-label">Email <span class="required">*</span></label>
        <input class="form-input" type="email">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('editThingModal')">Cancel</button>
      <button class="btn btn-primary" onclick="…">Save</button>
    </div>
  </div>
</div>
```

**Wizard (multi-step):**
```html
<div class="modal-overlay" id="addThingWizardModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Add <thing></div>
      <button class="modal-close" onclick="closeModal('addThingWizardModal')"><?= aegis_icon('x', 13) ?></button>
    </div>
    <div class="modal-steps">
      <div class="modal-step active"><span class="modal-step-num">1</span>Find</div>
      <div class="modal-step-divider"></div>
      <div class="modal-step"><span class="modal-step-num">2</span>Set role</div>
      <div class="modal-step-divider"></div>
      <div class="modal-step"><span class="modal-step-num">3</span>Review</div>
    </div>
    <div class="modal-body">…</div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('addThingWizardModal')">Cancel</button>
      <button class="btn btn-primary" onclick="…">Next</button>
    </div>
  </div>
</div>
```

#### 7b — Wire every button & link

After modals exist, audit:

| Wrong | Right |
|---|---|
| `onclick="showToast('Coming soon')"` on a real action | `openModal('thatActionModal')` |
| `onclick="showToast(…)"` on destructive action | `confirmAction(…)` or open a confirm modal |
| `href="#"` or `href=""` on real navigation | Correct portal page (per rename table) |
| `<button class="btn-icon" onclick="window.location='page.php'">` | `<a class="btn-icon" href="page.php">` |
| `.btn-icon` without `data-tip` | Add descriptive `data-tip="…"` |
| Inline color on icon button: `style="color:var(--red)"` | `class="btn-icon btn-icon-danger"` |

#### 7c — Cross-check modal IDs

1. Every `openModal('id')` has a matching `<div class="modal-overlay" id="id">`
2. No duplicate modal IDs in the file
3. Every modal footer has Cancel/Close on the **LEFT**, primary/destructive on the **RIGHT**
4. Destructive modal footers use `btn-danger` (not `btn-primary`)
5. Wizard step indicator uses `aegis_icon('check', 10)` for completed steps — not `✓` text

---

### Edit 8: File uploads → Dropzone

Real file uploads always use `.aegis-dropzone`. Never `<input type="file">`, never custom drag-drop.

**CDN in `<head>`** (only if at least one upload zone exists):
```html
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css">
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js" defer></script>
```

**Canonical markup:**
```html
<div class="aegis-dropzone"
     id="<uniqueId>"
     data-dz-url="#"                     <!-- upload endpoint; "#" for demos -->
     data-dz-accept=".pdf,.doc,.docx"    <!-- comma-separated extensions -->
     data-dz-max="25"                    <!-- max file size in MB -->
     data-dz-max-files="1">              <!-- total max files -->
  <div class="dz-default">
    <?= aegis_icon('upload', 22) ?>
    <div class="aegis-dz-title">Drag &amp; drop or click to browse</div>
    <div class="aegis-dz-sub">PDF or Word · Max 25 MB</div>
  </div>
</div>
```

**Page-local CSS** (paste into page `<style>` block):
```css
.aegis-dropzone {
  position: relative; min-height: 130px;
  border: 2px dashed var(--border); border-radius: var(--radius);
  background: var(--surface);
  transition: border-color var(--transition), background var(--transition);
  padding: 22px 18px !important; cursor: pointer;
}
.aegis-dropzone:hover, .aegis-dropzone.dz-drag-hover {
  border-color: var(--gold-dark); background: var(--badge-bg-gold);
}
.aegis-dropzone .dz-default {
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  color: var(--text-3); pointer-events: none;
}
.aegis-dropzone.dz-started .dz-default { display: none; }
.aegis-dropzone .dz-default .aegis-dz-title { font-size: 13px; font-weight: 700; color: var(--text); }
.aegis-dropzone .dz-default .aegis-dz-sub   { font-size: 11px; color: var(--text-3); }
.aegis-dropzone .dz-message:not(.dz-default) { display: none !important; }
.aegis-dropzone .dz-preview {
  background: var(--surface-2); border: 1px solid var(--border);
  border-radius: var(--radius-sm); margin: 6px; padding: 8px 12px;
  font-family: var(--font-sans); font-size: 13px; color: var(--text-2); min-height: 0;
}
.aegis-dropzone .dz-preview .dz-filename { font-weight: 700; color: var(--text); }
.aegis-dropzone .dz-preview .dz-remove   { color: var(--red-dark); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; }
.aegis-dropzone .dz-preview.dz-error .dz-error-message { background: var(--red-light); color: var(--red-dark); border-radius: var(--radius-sm); }
.aegis-dropzone .dz-preview.dz-success .dz-success-mark { color: var(--green-dark); }
```

**Auto-init JS** (paste at end of page `<script>` block):
```js
/* ── AEGIS DROPZONE AUTO-INIT ── */
(function initAegisDropzones() {
  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }
  ready(function() {
    if (typeof Dropzone === 'undefined') return;
    Dropzone.autoDiscover = false;
    document.querySelectorAll('.aegis-dropzone').forEach(function(el) {
      if (el.dropzone) return;
      try {
        new Dropzone(el, {
          url:              el.dataset.dzUrl       || '#',
          maxFilesize:      parseInt(el.dataset.dzMax      || '25', 10),
          maxFiles:         parseInt(el.dataset.dzMaxFiles || '10', 10),
          acceptedFiles:    el.dataset.dzAccept    || null,
          autoProcessQueue: false,
          addRemoveLinks:   true,
          createImageThumbnails: false,
          dictDefaultMessage:   '',
          dictRemoveFile:       'Remove',
          dictFileTooBig:       'File too large ({{filesize}} MB). Max {{maxFilesize}} MB.',
          dictInvalidFileType:  'File type not allowed.',
          dictMaxFilesExceeded: 'Maximum file count reached.',
          init: function() {
            this.on('addedfile',   function(file)          { if (typeof showToast === 'function') showToast('Added: ' + file.name, 'success'); });
            this.on('removedfile', function(file)          { if (typeof showToast === 'function') showToast('Removed: ' + file.name, 'info'); });
            this.on('error',       function(file, message) { if (typeof showToast === 'function') showToast(typeof message === 'string' ? message : 'Upload error', 'error'); });
          }
        });
      } catch (err) { console.warn('Dropzone init failed for', el, err); }
    });
  });
})();
```

**Banned patterns** anywhere on the platform:
- ❌ `<input type="file">` (raw HTML input)
- ❌ Custom dashed-border `<div>` styled to look like a drop area
- ❌ Click handlers that programmatically click a hidden file input
- ❌ Self-rolled drag-and-drop using `dragenter` / `dragover` / `drop`

**`.aegis-dropzone` vs `.upload-zone`:**
- `.aegis-dropzone` = real file upload (Dropzone manages the click)
- `.upload-zone` = CTA tile (signature block, "Add Steward" placeholder) — clicks open a modal

If a `.upload-zone` is meant to accept files, add `.aegis-dropzone` + `data-dz-*` attrs alongside it (`class="upload-zone aegis-dropzone"`).

---

### Edit 9: Module-scoped Activity button

Every primary module page surfaces an Activity button in the hero. **Don't add** to dashboards, settings, profile editors, or wizards.

**Canonical button** as the FIRST child of `.page-hero-actions`:
```html
<a href="activity.php?module=continuity_plan"
   class="btn-hero-ghost is-on-light"
   data-tip="Module activity">
  <?= aegis_icon('clock', 14) ?> Activity
</a>
```

Drop `.is-on-light` on the default (non-quiet) hero.

**Module slugs** (snake_case — must match what writes to `activity_events.module`):

| Page | Module slug |
|---|---|
| `continuity-plan.php` | `continuity_plan` |
| `continuity-stewards.php` | `continuity_stewards` |
| `support-stewards.php` | `support_stewards` |
| `important-documents.php` | `documents` |
| `vault.php` | `vault` |
| `finances.php` | `finances` |
| `referrals.php` | `referrals` |
| `network.php` | `network` |
| `services.php` | `services` |
| `messages.php` | `messages` |

**New module** → add an entry to `$AEGIS_ACTIVITY_MODULES` in `activity_body.php`:
```php
'finances' => [
    'label' => 'Finances',
    'icon'  => 'credit-card',
    'back'  => 'finances.php',
],
```

**Filter wiring** — pass `module` through to `aegis_get_activity()`:
```php
$filters = [
    'event_type' => $_GET['type']     ?? '',
    'severity'   => $_GET['severity'] ?? '',
    'unread'     => !empty($_GET['unread']),
    'module'     => $_GET['module']   ?? '',
];
$events = aegis_get_activity($current_user['id'], 100,
    array_filter($filters, fn($v) => $v !== '' && $v !== false));
```

Other links to `activity.php` on the same page must carry the same `?module=` param.

---

## Seed-data completeness gate (between Phase 2 and Phase 3)

Before Phase 3 begins, the seed must be complete enough that write helpers have something valid to write against. Document 2 (seed/schema foundation) before Document 1 (write helpers) — this ordering was learned the hard way; write helpers with stale schema constraints or missing relationship rows produce "Page not found" errors and silent FK failures.

**Pre-flight checklist before Phase 3:**

```
☐ Schema CHECK constraints in db.php wide enough for every value the new write helpers will INSERT
☐ Every foreign-key target row exists in seed (user records, plan rows, steward rows)
☐ Every enum value the page emits is in the CHECK constraint's allowed list
☐ Seed has at least one row in every state the page can render (active, pending, archived, etc.)
☐ Reset endpoint (/reset.php?token=aegis-demo-reset) loads the seed without errors
☐ The page renders with at least one row of real seed data in the read path before write actions are tested
```

If anything fails, **stop and flag**. Don't proceed to write helpers; widen the schema or seed first. Schema CHECK widening is owner-approval territory because it ripples across all four portals — surface it explicitly.

---

## Phase 3 — Write-path edits

The pattern Provider Portal Waves 1–7 use. Five edits, same contract everywhere. Reference: `AEGIS-PROJECT-CONTEXT.md §6.5` and `CENTRALIZED-SYSTEM.md "How to wire a write action"` for the full architecture.

### Edit 10: Page-local fetch wrapper

Add one wrapper named for the page's domain. Never redefine global helpers (`openModal`, `closeModal`, `showToast`, `toggleSwitch` — these live in `_shared.js`).

```javascript
/* ── Write path → /_shared/save_<domain>.php ──
   Page-local fetch wrapper. Global helpers from _shared.js are never redefined. */
function xPost(payload) {
  return fetch('/_shared/save_<domain>.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(function(r){ return r.json(); })
  .catch(function(){ return { ok: false, error: 'Network error' }; });
}
```

Naming convention by domain: `csPost` (continuity-stewards), `ssPost` (support-stewards), `vPost` (vault), `dPost` (documents), `fnPost` (finances), `nPost` (network), `rPost` (referrals), `svcPost` (services), `jPost` (jobs), `msgPost` (messages), `evPost` (events), `cpPost` (continuity-plan), `incPost` (incidents).

### Edit 11: Hook every "Save / Submit / Confirm" modal button

Every modal that mutates data must call the wrapper, close the modal, fire a toast with a human sentence (the toast text gets the MA'AT tone pass — not Phase 3's job, but write the sentence so a tone pass can later improve it without restructuring).

```javascript
function xRemove() {
  if (!_activeId) { closeModal('removeModal'); showToast('No item selected.','error'); return; }
  xPost({ action: 'remove', item_id: _activeId }).then(function(d){
    closeModal('removeModal');
    showToast(d.ok ? 'Removed. Both parties notified.' : (d.error || 'Could not remove.'),
              d.ok ? 'warning' : 'error');
    if (d.ok) setTimeout(function(){ window.location.reload(); }, 900);
  });
}
```

Identify every modal's submit button in the page. For each:
- Wire to the wrapper if it has a write action
- Leave alone if it's a read-only modal (View Details, etc.)
- Flag if it has a write action but no corresponding `models_write.php` helper (Edit 12 territory)

### Edit 12: Confirm/add the write helper in `models_write.php`

For each `action` the page sends, there must be a matching helper in `models_write.php`. If not, **flag in Phase 1 and add in Phase 3**. Helper conventions:

```php
/**
 * One-line purpose. Returns primitive (id / bool / count).
 * Authorization assumed proved by caller endpoint.
 * Fans out activity events to cross-portal recipients before returning.
 */
function aegis_<domain>_<verb>(string $owner_id, string $target_id, array $opts = []): bool {
    $db = aegis_db();

    /* 1. Verify ownership / fetch entity */
    $stmt = $db->prepare('SELECT … FROM <table> WHERE id = ? AND owner_id = ?');
    $stmt->execute([$target_id, $owner_id]);
    $row = $stmt->fetch();
    if (!$row) return false;

    /* 2. Mutate */
    $db->prepare('UPDATE <table> SET … WHERE id = ?')->execute([$target_id]);

    /* 3. Activity fan-out — every recipient that should see this event */
    aegis_log_activity(
        $recipient_id, $recipient_portal, $event_type, $severity,
        $module_slug, $action_verb,
        'Headline sentence',
        'Body sentence — apply MA\'AT tone in the tone pass.',
        $target_id, '<entity_type>', $owner_id
    );

    /* 4. Originator confirmation entry on their own feed */
    aegis_log_activity(
        $owner_id, 'provider', $event_type, 'info',
        $module_slug, $action_verb,
        'Confirmation headline', 'Confirmation body.',
        $target_id, '<entity_type>'
    );

    return true;
}
```

### Edit 13: Confirm/add the endpoint case in `/_shared/save_<domain>.php`

For each `action` the page sends, the endpoint's `switch ($action)` must have a matching case that calls the helper and returns the JSON response. If a new endpoint file is needed entirely, copy the skeleton from `save_pref.php` (the canonical template) and add the case.

```php
switch ($action) {

    case '<verb>': {
        $target_id = (string)($data['target_id'] ?? '');
        if ($target_id === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'target_id required']);
            break;
        }
        $ok = aegis_<domain>_<verb>(
            $user['id'],
            $target_id,
            $data['opts'] ?? []
        );
        echo json_encode(['ok' => $ok]);
        break;
    }

    // … existing cases …

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => "Unknown action '$action'"]);
}
```

**Auth gate convention:** every endpoint rejects anonymous, rejects wrong role, rejects non-POST. Never edit the gate structure — only add cases to the switch.

### Edit 14: Verify activity fan-out hits the right recipient portals

Walk through each new helper. For every cross-portal action, the `aegis_log_activity()` call(s) should hit:

| Action originated by | Recipients that should see it |
|---|---|
| Practitioner adds/removes a steward | The steward (their portal feed) + practitioner confirmation |
| Practitioner shares vault item with CS | The CS (their portal feed) + practitioner confirmation |
| Practitioner sends a referral | The recipient practitioner + sender confirmation |
| Practitioner posts a job | All matching BPs (broadcast) + practitioner confirmation |
| SS reports a critical incident | Practitioner + all CSes on the plan + SS confirmation |
| CS verifies an incident | Practitioner + all SSes on the plan + CS confirmation |
| BP submits invoice | Practitioner + BP confirmation |
| Practitioner approves invoice | BP + practitioner confirmation |

Missing a recipient = broken cross-portal communication. The pattern is "every actor affected by this event gets a feed entry."

**Pre-flight before exiting Phase 3:**

```
☐ Page-local fetch wrapper present (named for domain, never redefines globals)
☐ Every "Save/Submit/Confirm" modal button wired to the wrapper
☐ Every action sent by the page has a matching helper in models_write.php
☐ Every action has a matching case in the save_*.php endpoint
☐ Helpers with cross-portal impact call aegis_log_activity() for every recipient
☐ No alert() / confirm() — replaced with showToast() / confirmAction()
☐ Toast messages are human sentences (tone pass will refine)
☐ One successful end-to-end click test through the demo for each new action
```

---

## Phase 4 — Verify & deliver

Run this checklist mentally before exporting:

```
☐ 0 alert() / confirm() / title="" on icon buttons
☐ 0 local openModal / closeModal / showToast / switchTab / toggleSwitch / toggleSidebar / toggleDropdown / navigateTo / aegisSlugify definitions
☐ 0 hardcoded user data (all via aegis_get_*)
☐ 0 (int) cast on user IDs / slugs
☐ 0 inline <svg> (all via aegis_icon)
☐ 0 raw <input type="file">
☐ 0 old filename refs (per rename table)
☐ 0 emojis in rendered HTML (toast strings are fine)
☐ Every openModal('id') has a matching <div id="id">
☐ Every btn-icon has data-tip
☐ Every modal footer: Cancel/Close on LEFT, primary/danger on RIGHT
☐ Activity button in hero (if applicable) → activity.php?module=<slug>
☐ seed.json parses (if edited)
```

Copy edited files to `/mnt/user-data/outputs/` — target first, then supporting files. Call `present_files`. Summary in this format:

```
Portal: <folder> · User: <default_user> · Role: <role> · Module: <slug or n/a>

WIRING APPLIED
  Edits 1–3: done
  Edit 4: <helpers wired / added; seed rows added>
  Edit 5: <renames done>
  Edit 6: <counts: alert→toast, confirm→confirmAction, title→data-tip, local helpers dropped>
  Edit 7: <missing modals added; buttons wired; ID audit result>
  Edit 8: <uploads converted or "n/a">
  Edit 9: <activity button added & slug registered or "n/a">

FILES DELIVERED
  Target: <filename>.php
  models.php: <helpers added or "no changes">
  seed.json: <rows added or "no changes">
  activity_body.php: <updated or "no changes">
  icons.php: <updated or "no changes">

FLAGGED
  <out-of-scope items, manual review needed, etc.>
```

---

## Hard rules

- **Surgical `str_replace` only.** No full rewrites except new files.
- **Read before editing.** `view` the file once before any edit.
- **One concern per edit.** Don't bundle wiring + design.
- **Never overwrite `seed.json`.** Insert with `str_replace`, validate JSON after every batch.
- **String slugs, never `(int)` casts** for user IDs.
- **Never redefine globals** (`openModal`, `closeModal`, `showToast`, `confirmAction`, `switchTab`, `toggleSwitch`, `toggleDropdown`, `navigateTo`, `aegisSlugify`, `AegisTier.*`).
- **Detail-modal for a person is forbidden.** Always link to the public profile via `viewPartyProfile(name, kind, slug)` or the canonical URL `/public/<role>.php?slug=<slug>`.
- **Never use** "DSR", "Executor", or "Professional Will" — say "Support Steward", "Continuity Steward", "Continuity Plan".
- **No emojis in rendered HTML** (toast message strings are fine).

---

## Critical patterns from recent sessions

Patterns that come up repeatedly and must be applied in every wiring pass.

### Identity defaults derive from `$current_user`

`header.php` resolves `$executor_name`, `$executor_initials`, `$executor_email` from `$current_user['display_name']` and `$current_user['email']` automatically. **Don't redefine these in the page** unless you have a deliberate override reason.

If you're updating header.php itself, the resolution block:

```php
$_aegis_topbar_user = $current_user ?? (function_exists('aegis_current_user') ? aegis_current_user() : null);
$_aegis_full_name   = $_aegis_topbar_user['display_name'] ?? null;
$_aegis_clean_name  = $_aegis_full_name
    ? trim(preg_replace('/^(Dr|Mr|Mrs|Ms|Prof|Rev|Sr|Jr)\.?\s+/i', '', $_aegis_full_name))
    : null;
$_aegis_parts       = $_aegis_clean_name ? array_values(array_filter(explode(' ', $_aegis_clean_name))) : [];
$_aegis_initials    = '';
if (count($_aegis_parts) >= 2) {
    $_aegis_initials = strtoupper(substr($_aegis_parts[0], 0, 1) . substr(end($_aegis_parts), 0, 1));
} elseif (count($_aegis_parts) === 1) {
    $_aegis_initials = strtoupper(substr($_aegis_parts[0], 0, 2));
}

$executor_name     = $executor_name     ?? ($_aegis_clean_name ?: 'User');
$executor_initials = $executor_initials ?? ($_aegis_initials   ?: 'U');
$executor_email    = $executor_email    ?? ($_aegis_topbar_user['email'] ?? '');
```

### Honorific stripping

For practitioner display names like "Dr. Sarah Johnson", the leading honorific (`Dr.`, `Mr.`, `Mrs.`, `Ms.`, `Prof.`, `Rev.`, `Sr.`, `Jr.`) is stripped both for the displayed name AND for the avatar initials. Result: display "Sarah Johnson", initials "SJ".

### Hidden Assignments item for practitioner

In `header.php`'s dropdown, the Assignments item is wrapped in `<?php if ($aegis_role !== 'practitioner'): ?>...<?php endif; ?>`. Practitioners don't get task assignments — they manage providers and stewards.

### Canonical public-profile URLs

```
Practitioners      → /public/provider.php?slug=<slug>
Continuity Steward → /public/continuity_steward.php?slug=<slug>
Business Partner   → /public/business.php?slug=<slug>
Support Steward    → /public/support_steward.php?slug=<slug>  (Provider-gated)
```

The PHP helpers `aegis_practitioner_public_url()`, `aegis_cs_public_url()`, `aegis_bp_public_url()` return these canonical URLs (with `rawurlencode()` on the slug). The JS `viewPartyProfile(name, kind, slug)` redirects to the same pattern.

**Never use pretty URLs** (`/provider/<slug>`, `/steward/<slug>`, `/business/<slug>`) — they break relative-link resolution. When a page later renders relative links like `finances.php`, the browser resolves them against the pretty-URL base, producing 404s like `/provider/finances.php`.

### Header URLs are portal-absolute

`header.php` prefixes every relative `.php` URL in `$aegis_urls` with the role's portal base (`/provider-portal/`, `/continuity-steward-portal/`, `/support-steward-portal/`, `/biz-portal/`). So the header dropdown items work from anywhere — portal pages, public profile pages, public preview pages.

If building a similar URL map elsewhere, follow the same pattern: detect the portal base from `$aegis_role`, prefix relative URLs.

### Tier gating

```php
$tier      = $_GET['tier'] ?? 'practice';
$cs_max    = aegis_tier_limit($tier, 'cs');                                   // 1 (access) / 2 (practice)
$ss_max    = aegis_tier_limit($tier, 'ss');                                   // 2 (both tiers)
$at_limit  = aegis_tier_at_limit($current_user['id'], 'cs', $tier);
```

For Access-tier locked features, route through `AegisTier.showUpgradeFor('Feature name')`:

```html
<a href="referrals.php" class="nav-item is-locked"
   onclick="event.preventDefault(); AegisTier.showUpgradeFor('Referrals');">
  Referrals
</a>
```

At-limit add buttons swap onclick:
```php
<button class="btn btn-primary"
        onclick="<?= $at_limit ? "AegisTier.showUpgradeFor('More Continuity Stewards')" : "openModal('addCsModal')" ?>">
  Add Continuity Steward
</button>
```

### Demo persistence keys

These 8 keys ride through every navigation: `as`, `tier`, `services`, `emergency`, `invited`, `cs_role`, `ss_role`, `cs_account_type`. The persistence layer in `_shared.js` handles this automatically for `<a href>` clicks and `navigateTo()` calls.

Use `navigateTo(url)` for any JS-driven navigation — never `window.location.href = url` directly. The DOM-ready scanner in `_shared.js` will rewrite inline `onclick="window.location='…'"` patterns to `navigateTo('...')` automatically, but explicit calls are clearer.

---

## Tone pass

Wiring (this prompt) + Design (Design Prompt) + Seeding (data work) is **three of four** standard passes. The fourth is the MA'AT brand voice & tone pass — applied to copy after the page is structurally complete.

- Reference: `Aegis_Tone_Voice_Prompt.md`
- When: after all three preceding passes for the same file have shipped
- Scope: prose only (heroes, eyebrows, subtitles, section intros, notice/callout bodies, empty-state text, FAQ Q&A, onboarding/welcome copy, descriptions, narrative helper/hint text, placeholder sentences, the human sentence inside `showToast()` strings)
- Out of tone-pass scope: button labels, form field labels, table headers, validation/error messages, status badges, nav/sidebar labels, canonical entity names (Continuity Steward, Support Steward, etc.)

**Don't pre-tone during wiring.** Write toast messages and section copy in clear plain English during Phase 3 — the tone pass refines them without restructuring. Trying to apply MA'AT voice during wiring leads to back-and-forth and dilutes the wiring pass.

**Source of all toned copy:** if the copy lives in `seed.json` or a helper like `aegis_overview_data()`, edit it there, not in the template. See `CENTRALIZED-SYSTEM.md "How to wire a cross-portal template"`.

---

## Third-party integration touch points

Several write helpers touch external services. When wiring a page that includes these surfaces, check `AEGIS-PROJECT-CONTEXT.md §9` for service status and BAA state. The integrations below are scaffolded in UI but most have backend work blocked on client account creation (see `PENDING-ITEMS.md` Section A).

| Page domain | Third-party | What the wiring does today | What needs the live service |
|---|---|---|---|
| `finances.php` | **Stripe** + **Stripe Connect Express** | UI states (Connected / Restricted / Disconnected) render from `users.stripe_connected`. Invoice approve/reject/dispute fan out activity events. | Webhook handlers, actual money flow, payouts to CS/BP — pending Stripe account |
| `vault.php` (Credentials zone) | **Keeper Security** (canonical) / **AES-256-GCM** (current stopgap) | Local credential storage uses AES-256-GCM envelope (IV + ciphertext + auth tag + version byte). UI gates by verified-incident state. | Full Keeper API integration is Phase 2 (4–6 wk effort); pending Keeper Business account |
| `important-documents.php` | **In-portal e-signature** (built in-house) | Hybrid DocuSign-style signing flow is fully native — no third-party dependency | N/A |
| Onboarding invitations · password reset · incident alerts · digest emails | **Amazon SES** (recommended) or **SendGrid** | Activity events fire on the originator's feed; email send is stubbed | Pending ESP selection + account + BAA + domain verification |
| File downloads (vault, documents, invoices, CEU certs, settings export) | **AWS S3 + signed URLs** | ~28 file-binary stubs across these pages link to placeholder URLs | Pending AWS account + S3 bucket + BAA |
| Profile · settings analytics | **Google Analytics + GTM** with PHI-exclusion | No tracking code injected yet | Pending client go-ahead on PHI-exclusion config |
| Help links · support buttons | **Freshdesk** / **Zendesk** / custom inbox | Help links route to placeholder `/support` | Pending help desk vendor decision |
| Aegis Verified badge | **Checkr** | UI displays verified badge from `users.verified` | Pending Checkr integration |

**Rule:** when wiring a page that surfaces one of these integrations, your write helper logs the activity event normally and leaves a clear hook (e.g. `// TODO(stripe): webhook will mark invoice paid here`) where the real service call will plug in. Don't stub fake "success" — the demo should accurately reflect "UI wired, integration pending."

---

## When pairing with the Design Prompt

If the user wants both passes in one turn, run wiring first (Edits 1–14, all four phases), THEN immediately start the Design Prompt's Phase 1. Don't pause between. Final delivery covers both passes.

If a Tone pass is also requested in the same turn, sequence is: **Wiring → Design → Seeding (if needed) → Tone.** Never tone before wiring is complete — the tone pass assumes the structure is stable.
