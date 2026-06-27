# `continuity-stewards.php` — Required Changes

**Source spec:** `technical-changes.md` File 8 (rows 8.1–8.9) plus applicable platform-wide rename map.
**Current file:** `/provider-portal/continuity-stewards.php` (1805 lines, design-system refactor complete, wiring done).
**Owner:** practitioner portal.
**Role gate:** `practitioner` (already in place).
**Sidebar key:** `continuity-stewards` (already in place).

---

## At a glance

| Bucket | Count | Risk |
|---|---|---|
| Terminology sweeps (display strings only — no logic change) | 102 token hits | **Low** |
| Wire reads (replace seeded HTML with model loops) | 1 hookup | Medium |
| New per-card UI (countersignature chip + incident-authorization matrix) | 2 components | Medium / High |
| New cross-card action (copy tasks Primary → Alternate) | 1 action | Medium |
| Activity-log fan-out on invite | 2 calls | Low |
| Smoke-check existing fan-out (no code change) | 1 verification | Low |
| Internal link rename audit | 0 — already done | — |

**Total: 9 task IDs (8.1–8.9), grouped below by execution order.**

---

## Pre-flight — already done (no action)

| ID | Item | Status |
|---|---|---|
| pre.1 | `$active_page` matches sidebar nav key (`'continuity-stewards'`) | ✅ Done |
| pre.2 | Asset paths root-relative (`/aegis-favicon.svg`, `/_shared.css`, `/_shared.js`) | ✅ Done |
| pre.3 | Sidebar/header includes use `__DIR__ . '/../_shared/...'` | ✅ Done |
| pre.4 | PHP entry block at top (`AEGIS_ENTRY`, `models.php`, `icons.php`, role gate) | ✅ Done |
| pre.5 | Internal links updated to canonical filenames (`documents.php` → `vault.php`, `dsr.php` → `support-stewards.php`) | ✅ Done |
| pre.6 | Aegis Design System refactor — segmented-track tabs, `.stat-chips-row`, icon-only row actions, gold-dark avatars, no inline SVGs, etc. | ✅ Done |

---

## Phase 1 — Terminology sweep (8.1, 8.2, 8.3)

Pure str_replace. No structural changes. One commit per pass.

### 8.1 — `Executor` → `Continuity Steward` (60 hits)

Replace every display-string occurrence of `Executor` (and case variants) with `Continuity Steward`.

**Compound forms — explicit mapping:**

| Old | New |
|---|---|
| `Primary Executor` | `Primary Continuity Steward` |
| `Backup Executor` | `Alternate Continuity Steward` |
| `Secondary Executor` | `Alternate Continuity Steward` |
| `Tertiary Executor` | `Tertiary Continuity Steward` (or remove — see flag below) |
| `Executor Agreement` | `Continuity Plan Agreement` |
| `Executor Profile` | `Continuity Steward Profile` |
| `Executor Wizard` | `Add Continuity Steward Wizard` |
| `Add Executor` | `Add Continuity Steward` |
| `Edit Executor` | `Edit Continuity Steward` |
| `Remove Executor` | `Remove Continuity Steward` |
| `Change Executor Role` | `Change Continuity Steward Role` |
| `Executor still agrees to serve` | `Continuity Steward still agrees to serve` |

**Where to look:**
- `<title>` (already done — page title is `Continuity Stewards`)
- `.exec-hero-title`, `.exec-hero-sub`, hero buttons
- `.modal-title` of every modal (`addExecutorStep1Modal` through `addExecutorStep4Modal`, `editExecutorModal`, `changeRoleModal`, `removeExecutorModal`, etc. — modal IDs themselves can stay, only the visible titles change)
- Tab labels (already say "Continuity Stewards" — good)
- `.exec-tag` text content (`Primary Continuity Steward` etc. — partly done already)
- All `data-tip` tooltip values
- Toast strings (`showToast('Executor invited', ...)`)
- Form labels and helper text inside `addExecutorStep*Modal` modals
- Comments / section labels (`<!-- PRIMARY EXECUTOR -->`)
- `aria-label` if any
- The audit log modal's seed entries

**Don't touch:**
- Modal IDs (`addExecutorStep1Modal` etc.) — those are JS handles. Renaming them risks breaking onclick handlers across multiple modals. Keep as-is.
- CSS class names (`.exec-card`, `.exec-hero`, `.exec-avatar`, `.exec-tag`, `.exec-meta`, `.exec-name`, `.exec-sub`) — structural names. Renaming would force a CSS-wide rewrite for zero user-facing benefit.
- JS function/variable names (`selectExecutorCandidate`, `showExecSearchResults`, `execSearchInput`) — same reasoning.

### 8.2 — Standardize backup-position label (35 hits)

Rule: **only the second position is named "Alternate".** Tertiary stays Tertiary if kept (see flag).

| Old | New |
|---|---|
| `Backup CS` | `Alternate CS` |
| `Secondary CS` | `Alternate CS` |
| `Support CS` | `Alternate CS` |
| `Backup Continuity Steward` | `Alternate Continuity Steward` |
| `Secondary Continuity Steward` | `Alternate Continuity Steward` |

**Where to look:** Card headers/badges (`.exec-card secondary` card's badge label), hero copy ("Continuity Stewards manage your practice — assign a Primary and Alternate..."), modal copy in `changeRoleModal` and `addExecutorStep2Modal` (role assignment step), audit log entries that mention secondary/backup.

### 8.3 — `Professional Will` → `Continuity Plan` (10 hits)

| Old | New |
|---|---|
| `Professional Will` | `Continuity Plan` |
| `Your Professional Will` | `Your Continuity Plan` |

**Where to look:**
- Annual review alert: "Your Professional Will requires an annual attestation..." → "Your Continuity Plan requires an annual attestation..."
- `viewAgreementModal` title: "Professional Will — James Wilson" → "Continuity Plan Agreement — James Wilson"
- Hero subtitle if it mentions Will
- Audit log entries
- FAQ / help text inside any modal

---

## Phase 2 — Wire reads (8.7)

### 8.7 — Replace seeded CS list with `aegis_get_plan_stewards()` loop

**Current state:** Cards are inline HTML — Primary card with hardcoded "James Wilson", Secondary with "Dr. Maria Davis", Tertiary placeholder, Pending tab with "Dr. Thomas Chen", "I'm CS For" tab with "Dr. Robert Kim" / "Dr. Patricia Park". All hardcoded.

**Target state:**

```php
// Near top of body, just before the tabs:
$plan_id = aegis_get_plan_by_practitioner($current_user['id'])['id'] ?? null;
$primary_cs   = aegis_get_plan_stewards($plan_id, 'continuity_steward', 'primary');
$alternate_cs = aegis_get_plan_stewards($plan_id, 'continuity_steward', 'alternate');
$tertiary_cs  = aegis_get_plan_stewards($plan_id, 'continuity_steward', 'tertiary');  // optional, see flag
$pending_invites = aegis_get_pending_steward_invites($current_user['id'], 'continuity_steward');
$incoming_requests = aegis_get_incoming_steward_requests($current_user['id'], 'continuity_steward');
$am_cs_for = aegis_get_practitioners_where_user_is_steward($current_user['id'], 'continuity_steward');
```

Then loop each card. **Build card-rendering helpers** (inline PHP functions or a partial in `_shared/`) so the markup isn't duplicated 5+ times:

```php
function render_cs_card($steward, $role) {
    // Outputs the .exec-card markup with avatar, name, tags, meta, action row.
    // Reads $steward['name'], ['initials'], ['affiliation'], ['phone'], ['email'],
    // ['signed_at'], ['countersigned_at'], ['authorized_incidents'].
}
```

**WIRE checklist:**
- [ ] My Continuity Stewards tab → loops `$primary_cs`, `$alternate_cs`, `$tertiary_cs` (if kept)
- [ ] Pending tab → loops `$pending_invites` (outgoing) and `$incoming_requests` (CS-initiated)
- [ ] I'm Continuity Steward For tab → loops `$am_cs_for`
- [ ] History tab → reads from `activity_events` filtered by `event_type IN ('cs_invited', 'cs_accepted', 'cs_declined', 'cs_removed', 'cs_role_changed')`
- [ ] Stats row counts (Active CS / Pending / I'm CS For / Annual Review status) — derive from the same model reads

**No new model functions needed for the read path** — `aegis_get_plan_stewards`, `aegis_get_pending_steward_invites`, and `aegis_get_practitioners_where_user_is_steward` exist (per the spec's WIRE row 8.7 calling them out as the source of truth).

**Risk note:** This is the largest single PR for this file. Should ship after the terminology sweep so the visible diff is purely structural and easier to review.

---

## Phase 3 — Per-card additions (8.4, 8.6)

### 8.6 — Countersignature status chip (do this first — additive, low risk)

**Where:** Inside each `.exec-card`, in the same row as the existing `.exec-tag tag-active`/`tag-pending` chip (next to the role badge).

**Markup:**

```html
<!-- Existing role badge stays -->
<span class="exec-tag tag-primary"><?= aegis_icon('shield', 14) ?> Primary Continuity Steward</span>

<!-- NEW: countersignature status chip -->
<?php $sig = $steward['countersigned_at']; ?>
<?php if ($sig): ?>
  <span class="exec-tag tag-active"><span class="status-dot green"></span> Signed</span>
<?php elseif ($steward['invited_at']): ?>
  <span class="exec-tag tag-pending"><?= aegis_icon('clock', 12) ?> Pending Signature</span>
  <button class="btn-icon btn-icon-sm" data-tip="Re-send Invitation" onclick="openModal('resendInviteModal')"><?= aegis_icon('refresh', 12) ?></button>
<?php else: ?>
  <span class="exec-tag tag-expired"><?= aegis_icon('mail', 12) ?> Not Sent</span>
<?php endif; ?>
```

**Tokens:**
- Signed → reuse existing `.tag-active` (`--green-light` bg / `--green-dark` text)
- Pending Signature → reuse `.tag-pending` (now `--orange-light`/`--orange-dark` after design refactor)
- Not Sent → reuse `.tag-expired` (`--surface-3`/`--text-3`)

**Re-send action:**
- Icon-only `.btn-icon btn-icon-sm` with `data-tip="Re-send Invitation"`
- Opens existing `resendInviteModal`
- On modal submit: `aegis_log_activity()` + email stub (see 8.9 — same handler pattern)

### 8.4 — Per-incident-type authorization matrix

**Decision required from product owner before coding:**

> Where does the matrix live — inline disclosure on the card, or a "Manage Authorizations" modal?

| Option | Pros | Cons |
|---|---|---|
| **A — Inline disclosure** | One click to see all authorizations across CS at a glance | 7 toggle rows × 2-3 CS cards = vertical sprawl. Cards become very tall. |
| **B — Modal** | Card surface stays clean. Matches the existing modal-driven editing pattern (Edit Details, Change Role, Vault Access, Remove all go via modal). | Two clicks to view. Can't side-by-side compare CS authorizations. |

**Recommendation:** Option B (modal). It matches the existing UX pattern of every other CS-card action and keeps cards scannable. Design system rule: cards stay readable.

**Markup (Option B):**

```html
<!-- Add to action row alongside existing icon-only buttons -->
<button class="btn-icon" data-tip="Manage Authorizations" onclick="openModal('csAuthMatrixModal')"><?= aegis_icon('shield', 14) ?></button>
```

**New modal — `csAuthMatrixModal`:**

```html
<div class="modal-overlay" id="csAuthMatrixModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Authorize for Incident Types — <?= $steward['name'] ?></div>
      <button class="modal-close" onclick="closeModal('csAuthMatrixModal')"><?= aegis_icon('x', 13) ?></button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--text-2);font-weight:600;margin-bottom:16px;">
        Choose which incident types this Continuity Steward is authorized to act on.
        The four marked <em>Opt-in</em> are above the standard four — only enable if your practice scope requires it.
      </p>

      <!-- Standard four -->
      <div class="modal-section-label">Standard Coverage</div>
      <div class="auth-matrix">
        <?php foreach (['death','short_term_incapacitation','long_term_incapacitation'] as $type): ?>
          <div class="auth-row">
            <div class="auth-row-info">
              <div class="auth-row-label"><?= human_label($type) ?></div>
              <div class="auth-row-desc"><?= human_desc($type) ?></div>
            </div>
            <button type="button" class="toggle <?= in_array($type, $steward['authorized_incidents']) ? 'on' : '' ?>" onclick="toggleSwitch(this)"></button>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Opt-in four -->
      <div class="modal-section-label">Extended Coverage <span class="exec-tag tag-pending" style="margin-left:8px;">Opt-in</span></div>
      <div class="auth-matrix">
        <?php foreach (['missing_person','detainment','natural_disaster','geopolitical'] as $type): ?>
          <div class="auth-row">
            <div class="auth-row-info">
              <div class="auth-row-label"><?= human_label($type) ?></div>
              <div class="auth-row-desc"><?= human_desc($type) ?></div>
            </div>
            <button type="button" class="toggle <?= in_array($type, $steward['authorized_incidents']) ? 'on' : '' ?>" onclick="toggleSwitch(this)"></button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('csAuthMatrixModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveCsAuthMatrix(<?= $steward['id'] ?>)"><?= aegis_icon('check', 14) ?> Save Authorizations</button>
    </div>
  </div>
</div>
```

**Persists to:** `plan_incident_configs.authorized_cs_ids` (JSON array of CS user IDs per incident type — schema already supports this, no migration needed).

**New model helper required:** `aegis_save_plan_incident_authorization($plan_id, $incident_type, $steward_id, $authorized)`. Adds/removes the steward ID from the JSON array. Idempotent.

**JS submit handler:**

```js
function saveCsAuthMatrix(stewardId) {
  // Collect all toggle states from .auth-matrix
  const toggles = document.querySelectorAll('#csAuthMatrixModal .auth-row .toggle');
  const auths = {};
  toggles.forEach((t, i) => {
    const type = t.closest('.auth-row').dataset.incidentType;
    auths[type] = t.classList.contains('on');
  });
  // POST to handler, then on success:
  closeModal('csAuthMatrixModal');
  showToast('Authorizations saved', 'success');
  // Optionally re-fetch to update the visual state on the card
}
```

**CSS** (add to local `<style>` block):

```css
.auth-matrix { display: flex; flex-direction: column; gap: 4px; margin-bottom: 14px; }
.auth-row { display: flex; align-items: center; gap: 14px; padding: 10px 14px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius); }
.auth-row-info { flex: 1; min-width: 0; }
.auth-row-label { font-size: 13px; font-weight: 700; color: var(--text); }
.auth-row-desc { font-size: 11.5px; color: var(--text-3); margin-top: 2px; }
```

---

## Phase 4 — Cross-card action (8.5)

### 8.5 — "Copy tasks from Primary CS to Alternate CS"

**Where:** Wizard Step 3 (`addExecutorStep3Modal`, the responsibilities/tasks assignment step) when the user is adding an Alternate CS *and* a Primary CS already exists.

**Markup (added near the top of Step 3 body, conditional on Alternate role):**

```html
<?php if ($wizard_role === 'alternate' && $primary_cs): ?>
  <div class="alert alert-info" style="margin-bottom:16px;">
    <?= aegis_icon('info', 14) ?>
    <div>
      <strong>Same responsibilities as Primary?</strong>
      Most practices assign the Alternate the same task set as the Primary CS.
      <button class="btn btn-primary btn-sm" style="margin-left:8px;" onclick="copyTasksFromPrimary(<?= $primary_cs['id'] ?>)">
        <?= aegis_icon('refresh', 13) ?> Copy from <?= htmlspecialchars($primary_cs['name']) ?>
      </button>
    </div>
  </div>
<?php endif; ?>
```

**JS handler:**

```js
function copyTasksFromPrimary(primaryStewardId) {
  // Fetch primary's tasks via XHR, populate the form's task list, then
  // re-render the editable task UI inline so the user can tweak.
  fetch('/provider-portal/api/copy-tasks.php?from=' + primaryStewardId, {method:'POST'})
    .then(r => r.json())
    .then(data => {
      renderTasksList(data.tasks);
      showToast(`Copied ${data.tasks.length} tasks from Primary`, 'success');
    });
}
```

**Backend (new endpoint):** Either a dedicated `/provider-portal/api/copy-tasks.php` or extend the existing wizard handler. Calls new model fn `aegis_copy_tasks_between_stewards($plan_id, $from_steward_id, $to_steward_id)` (M.3 in the master spec, returns count copied). Duplicates every `plan_tasks` row tagged `assigned_to='continuity_steward'` with the source steward's ID, into rows with the destination steward's ID. Per-task timeline values copy verbatim.

---

## Phase 5 — Activity-log fan-out (8.9)

### 8.9 — Hook into the "Send Agreement" handler in Step 4

**Current:** `addExecutorStep4Modal` has a "Send Agreement" button. The current onclick is a `showToast()` placeholder.

**Target:** On submit, before redirect/toast, fire two activity-log calls:

```php
// In the wizard submit handler:
aegis_log_activity(
    $current_user['id'],
    'cs_invited',
    "Invited {$new_cs_name} as Continuity Steward",
    ['steward_id' => $new_cs_id, 'role' => $role]
);
aegis_log_activity(
    $new_cs_user_id,
    'cs_invitation_received',
    "You've been invited as Continuity Steward by {$current_user['display_name']}",
    ['practitioner_id' => $current_user['id'], 'role' => $role]
);
```

Both call the same existing `aegis_log_activity()` helper. No new model fn needed.

**Why both:** the practitioner sees their action in their activity log; the new CS sees the incoming invitation in *their* activity log so when they log into Aegis they see the prompt.

---

## Phase 6 — Verification (8.8 — no code change)

### 8.8 — Smoke-check existing certification fan-out

**No code edit in this file.**

When the CS countersigns the agreement (whatever flow that is — likely on the CS-portal side), the existing `aegis_steward_certify($plan_id, $steward_id)` helper fires. Confirm via test that it writes `attestation_states.cs_certified=true` so the practitioner's dashboard chip flips. If it doesn't, the bug is in `models.php` — not this file.

**Action item for the PR:** add a manual-test note in the PR description: "1. Add CS, 2. Switch to CS account, 3. Counter-sign, 4. Switch back to practitioner, 5. Confirm `attestation_states.cs_certified` row exists and the dashboard chip shows green."

---

## Items flagged — need product/design decision before coding

### Flag 1 — Tertiary slot semantics

The current file has a `.exec-card tertiary` and an "Add Tertiary Continuity Steward (Optional)" CTA. Spec 8.2 says "Alternate" replaces both Backup AND Secondary, but is silent on whether a third position exists.

**Two readings:**
- **(a) Two-tier model**: only Primary + Alternate. Tertiary CTA and card variant are removed entirely. Spec implies this by saying *"Backup CS / Secondary CS / Support CS — standardize to Alternate CS"*.
- **(b) Three-tier model**: Primary / Alternate / Tertiary stays as a structural option. Spec doesn't forbid it and the original UX rationale (solo-practitioner backup) still holds.

**Recommendation:** confirm with product. If unanswered, default to **(a) two-tier** since it aligns more cleanly with the rest of the platform language.

### Flag 2 — Matrix location

Per Phase 3 / 8.4: inline disclosure on each card vs. modal. **Recommendation: modal** (Option B) for the reasons above.

### Flag 3 — "Add Tertiary CS (Optional)" CTA card

If Flag 1 lands on (a) two-tier, the entire `.add-exec-cta` block (around line 678 in the current file) gets removed, since there's no third seat to invite. Hero CTA "Add Continuity Steward" handles all additions.

If (b), it stays.

---

## Risk-ordered execution plan

The execution-order list in `technical-changes.md` line 374 puts stewards work as step 3 (after schema migrations + new model functions). Within this file, here's the order:

| Order | Phase | What | Dependencies |
|---|---|---|---|
| 1 | Phase 1 | Terminology sweep (8.1, 8.2, 8.3) | None — pure str_replace |
| 2 | Phase 5 | Activity-log fan-out on invite (8.9) | Existing `aegis_log_activity()` |
| 3 | Phase 6 | Smoke-check certification (8.8) | Verification only, no code |
| 4 | Phase 3a | Countersignature status chip (8.6) | None — additive markup |
| 5 | **gate** | **Get product decisions on Flags 1 + 2** | Blocks everything below |
| 6 | Phase 2 | Wire CS reads (8.7) | Existing `aegis_get_plan_stewards()`, partial helpers |
| 7 | Phase 3b | Per-incident authorization matrix (8.4) | New model fn (M-?), modal CSS, JS handler |
| 8 | Phase 4 | Copy tasks Primary → Alternate (8.5) | New model fn M.3, new API endpoint |

**Items 1–4 can ship as PRs immediately, in order. Items 6–8 wait on the flagged decisions and on the schema/model work in `models.php`.**

---

## Files this work touches

| File | Why |
|---|---|
| `/provider-portal/continuity-stewards.php` | All visible changes |
| `/_shared/models.php` | New helpers: `aegis_save_plan_incident_authorization`, `aegis_copy_tasks_between_stewards` (M.3), possibly `aegis_get_pending_steward_invites` and `aegis_get_practitioners_where_user_is_steward` if not already present |
| `/provider-portal/api/copy-tasks.php` *(new)* | XHR endpoint for 8.5 |
| Schema | None new for File 8 — `plan_incident_configs.authorized_cs_ids` and `plan_tasks` already exist per spec |

---

## Acceptance checklist

When this work is done, the file should pass:

- [ ] `php -l` clean
- [ ] No `Executor` token in any user-visible string (HTML / toast / data-tip / aria-label)
- [ ] No `Backup CS` / `Secondary CS` / `Support CS` (Alternate is the only second-position label)
- [ ] No `Professional Will` (every reference uses `Continuity Plan`)
- [ ] All 5 `.exec-card`s have a countersignature status chip
- [ ] All 5 `.exec-card`s have a "Manage Authorizations" icon button → opens `csAuthMatrixModal`
- [ ] CS list reads from `aegis_get_plan_stewards()` — no inline-seeded names
- [ ] Step 3 wizard shows "Copy from {Primary name}" CTA when adding an Alternate
- [ ] Step 4 "Send Agreement" submit fires both `aegis_log_activity()` calls
- [ ] Smoke test: counter-signing a CS flips the dashboard's `cs_certified` chip green
- [ ] Internal links use canonical filenames (verified by `grep -rn -E "href=['\"](executors|dsr|agreements|documents|emergency|financials|index)\.php" .`)
- [ ] All Aegis Design System rules from the previous refactor remain intact (no inline SVGs, no `var(--gold)` in active states, no `linear-gradient`, etc.)
