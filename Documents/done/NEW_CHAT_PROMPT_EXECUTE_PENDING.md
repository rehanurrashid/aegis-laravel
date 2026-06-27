# Aegis — New Chat: Execute Remaining Pending Items

## Step 0 — Setup (Do This First)

```bash
cd /home/claude
rm -rf aegis
git clone https://github.com/rehanurrashid/aegis.git
cd aegis
git log -1 --oneline
php -l _shared/models.php
```

Then read via `project_knowledge_search` (mandatory before any code change):
- `CENTRALIZED-SYSTEM.md` — full `_shared/` inventory + architecture patterns
- `AEGIS-PROJECT-CONTEXT.md` — roles, lifecycle, terminology, tier limits
- `Aegis_Desing_Prompt_Short.md` — design system execution rules (wins on conflicts)
- `Aegis_Desing_Prompt.md` — full design reference
- `Aegis_Global_Wiring_Prompt.md` — write-path pattern
- `Aegis_Tone_Voice_Prompt.md` — MA'AT tone
- `ADMIN-PORTAL-SPEC.md` — admin portal scope (greenfield)
- `PENDING-ITEMS-CONSOLIDATED.md` — latest open items tracker
- `SUPPORT_FEEDBACK_FEATURE_SPEC.md` — feature spec for task #2 below

Then read in repo:
- `_shared/db.php`, `_shared/models.php`, `_shared/models_write.php` (full)
- `_shared/sidebar.php` (nav structure)
- `data/seed.json` (real data shape)
- `provider-portal/settings.php` (verify MFA + password reset placement for task #1)
- `provider-portal/login.php` (verify reset feature for task #1)

---

## Tasks to Execute (in this order)

### TASK 1 — Verify Auth Features Already Live

Carizma's B1 items (password reset + MFA) are believed to already exist:
- **Password reset** in `login.php`
- **Multi-factor authentication** inside `settings.php`

**Action:**
1. Grep both files to confirm presence of reset flow + MFA UI
2. If both confirmed present → mark B1 as ✅ DONE in `PENDING-ITEMS-CONSOLIDATED.md`
3. If anything missing or stub-only → flag specifically what's missing, do NOT build yet (will scope separately)

Output: a 4-line confirmation showing where each feature lives + status.

---

### TASK 2 — Build Support & Feedback Feature (centralized)

Follow `SUPPORT_FEEDBACK_FEATURE_SPEC.md` exactly. Summary:

**Architecture:** New sidebar item "Support" under Account group, in ALL 4 portals (Practitioner, CS, SS, BP). Shared template at `_shared/templates/support.php`. Each portal gets a 3-line stub.

**Build sequence:**
1. Add `support` sidebar entry to `_shared/sidebar.php` for all 4 portals under Account group
2. Create `_shared/templates/support.php` — 3 tabs (My Tickets, Feedback, Help Center) + ticket detail modal + new-ticket modal
3. Create 4 portal stubs: `provider-portal/support.php`, `continuity-steward-portal/support.php`, `support-steward-portal/support.php`, `biz-portal/support.php`
4. Create `_shared/save_support.php` with actions: `create_ticket`, `send_feedback`, `reply_ticket`, `close_own_ticket`, `submit_questionnaire`
5. Add read helpers to `_shared/models.php`: `aegis_get_user_support_tickets`, `aegis_get_ticket_thread`, `aegis_get_user_feedback_history`
6. Add write helpers to `_shared/models_write.php`: `aegis_create_support_ticket`, `aegis_send_feedback`, `aegis_reply_to_ticket`
7. Add schema (idempotent ALTER TABLE / CREATE TABLE IF NOT EXISTS) for `complaints`, `complaint_replies`, `help_articles`
8. Add floating Feedback Button to `_shared/page_foot.php` + `openFeedbackModal()` in `_shared/_shared.js`
9. Seed 3-5 sample tickets + 2-3 sample feedback entries + 5-8 help articles in `seed.json`
10. Run `GET /reset.php?token=aegis-demo-reset` and verify

**Critical rules:**
- Reuse the `complaints` + `complaint_replies` tables from `ADMIN-PORTAL-SPEC.md` (do NOT create duplicate ticket tables)
- Surgical `str_replace` edits only — no full rewrites
- Mirror existing template patterns (study `messages.php` template for thread UI)
- `php -l` clean on every touched file before delivery

Output: ZIP with all touched files mirroring repo folder structure.

---

### TASK 3 — Skip (Moved to Dedicated Chat)

White-Glove Workflow (B3) and ADMIN-PORTAL-SPEC.md finalization moved to standalone prompt: `ADMIN_PORTAL_SPEC_PROMPT.md`. Run that prompt in its own chat — too much scope to bundle here.

**No work this chat.**

---

### TASK 4 — Implement B4 Visibility Permissions

These are user-end controls. Most toggle wiring already exists in `settings.php` per portal — what's pending is making the READ sides respect those toggles.

**Action:**
1. Grep all 4 portals' `settings.php` for visibility toggles (e.g., `public_profile_visible`, `network_visible`, `vault_share_with_cs_standby`, etc.) — confirm presence
2. Audit `public/provider.php`, `public/continuity_steward.php`, `public/support_steward.php`, `public/business.php` — verify each respects the toggle
3. For each unwired toggle, add gate logic in the relevant read helper in `_shared/models.php` (e.g., `aegis_get_public_profile_data` returns null/stripped data if visibility off)
4. Audit CS portal `providers.php` and SS portal `providers.php` to ensure per-steward visibility is respected in standby vs. incident-active state (Vault content gated by `critical_incidents.status`)
5. List any toggles found in settings that have no read-side gating — these get fixed

**Surgical only:** read helpers get `if (!user_meta_visibility_flag) return null` guards. No new tables, no new UI.

Output: ZIP with patched read helpers + audit log of which toggles were already wired vs newly gated.

---

### TASK 5 — Skip B5 Email Templates

Already covered by separate prompt `EMAIL_TEMPLATES_PROMPT.md`. Will run after use cases are finalized. **No work this chat.**

---

### TASK 6 — Skip C Own-Workflow Features

These (Sessions, API keys, Webhooks, OAuth, Practice transfer, Plan change) are all inside `settings.php` per portal and will be integrated after dev completion. **No work this chat.**

---

### TASK 7 — Skip (Moved to Dedicated Chat)

`ADMIN-PORTAL-SPEC.md` finalization moved to standalone prompt: `ADMIN_PORTAL_SPEC_PROMPT.md`. Run that prompt in its own chat.

**No work this chat.**

---

### TASK 8 — Email Draft to Carizma (E2/E3/E4)

A draft is already prepared in `EMAIL_DRAFT_TO_CARIZMA_E2_E3_E4.md`. Review it for accuracy against the current codebase state, refine if needed, and confirm ready-to-send.

**No need to rewrite if already correct** — just verify and deliver.

---

### TASK 9 — Skip Third-Party Tools

Stripe, AWS, SES, GA setup are all client-side action items. **No work this chat.**

---

## Working Style

- Tasks 1, 4, 8 are **audits** — output findings + minor patches if needed
- Task 2 is the **biggest build** — the Support & Feedback feature end-to-end
- Tasks 3, 7 are **documentation updates** to `ADMIN-PORTAL-SPEC.md`
- Tasks 5, 6, 9 are **skipped** — confirm scope, don't touch
- Surgical `str_replace` edits only — never full file rewrites
- `php -l` clean on every touched file before delivery
- Mirror exact repo folder structure in all ZIPs
- Brief confirmations between tasks, no verbose explanations

---

## Final Deliverables

1. `support_feedback_feature_build.zip` — Task 2 complete build
2. `visibility_permissions_audit.zip` — Task 4 read-side gating
3. Updated `PENDING-ITEMS-CONSOLIDATED.md` — reflecting Task 1 + Task 2 + Task 4 completion
4. Confirmation of email draft (Task 8) ready to send

`ADMIN-PORTAL-SPEC.md` finalization runs in its own chat using `ADMIN_PORTAL_SPEC_PROMPT.md`.

---

## Start Here

Complete Step 0 fully. Confirm by outputting:
- Latest commit SHA
- File counts in `_shared/`, `provider-portal/`, etc.
- Verification that B1 (Task 1) is wired or not

Then proceed with Task 1 (verify) → Task 2 (Support build) → Task 4 (visibility audit) → Task 8 (email draft review).
