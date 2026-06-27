# Unification Analysis — Provider Portal → CS/SS/BP

Provider Portal has 18 page files. Below: each one classified, with focus on what's already shared, what *should* become shared before CS/SS/BP build starts, and where cloning is the right call.

> **Doc correction (audit pass):** §17.1 of `AEGIS-PROJECT-CONTEXT.md` lists `continuity-stewards.php` and `support-stewards.php` with ✅ checkmarks for CS/SS in several cells, but the actual `sidebar.php` code and the sidebar prose in §4.2 (CS) and §4.3 (SS) tell a different story. Implementation + §4.2/§4.3 prose is the source of truth. §17.1 is aspirational/inconsistent. This doc reflects what's actually in the sidebar.

**Reality (per `sidebar.php`):**
- **CS sidebar:** overview, dashboard, profile, my-tasks, providers (My Practitioners), important-documents, finances, continuity-management, vault, messages, activity, settings. **No `continuity-stewards.php`. No `support-stewards.php`.**
- **SS sidebar:** overview, dashboard, profile, providers (My Practitioners), my-tasks, important-documents, continuity-stewards (read-only), critical-incident-log, messages, activity, settings. **No `support-stewards.php`.**

---

## Already unified

| File | Pattern |
|---|---|
| `_shared.css`, `_shared.js`, `aegis-favicon.svg` | Root assets, all portals consume directly |
| `db.php`, `models.php`, `models_write.php`, `seed.php`, `seed.json`, `icons.php` | Data + helper layer |
| `header.php`, `sidebar.php`, `page_head.php`, `page_foot.php`, `page.php`, `layout.php`, `theme_loader.php` | Chrome — role-aware via `aegis_current_user()` |
| `bell.php`, `profile_strip.php`, `demo_switcher.php` | Shared partials |
| `activity_body.php` | **Bucket B precedent** — body shared, each portal's `activity.php` is a thin wrapper |
| `public_chrome.php`, `public_profile.php`, `/public/{provider,continuity_steward,support_steward,business}.php` | Cross-portal public profile system |
| All 16 `save_*.php` endpoints | One per write domain, role-checked internally |
| `aegis_overview_data($user)`, `aegis_profile_data($user)`, `aegis_edit_profile_groups($user)` | Role-aware data bundles |

---

## BUCKET A — Fully unified candidates

Reviewing all 18 Provider files: **none are pure Bucket A.** Every page that's symmetric across portals has at least one of: a continuity-contacts pinned region (P/CS/SS only, not BP), a role-specific panel (Subscription differs by tier model), or a role-gated action set. The Bucket B "shared body + thin wrapper" pattern handles this cleanly. **Recommend skipping Bucket A entirely.**

---

## BUCKET B — Shared body candidates ✅ COMPLETE

All five Bucket B unifications shipped. Summary preserved for reference; per-file details intentionally trimmed since the work is done.

| # | File | Status | Shared template path |
|---|---|---|---|
| B1 | `overview.php` | ✅ Done | `_shared/templates/overview.php` |
| B2 | `edit-profile.php` | ✅ Done | `_shared/templates/edit-profile.php` |
| B3 | `messages.php` | ✅ Done | `_shared/templates/messages.php` |
| B4 | `settings.php` | 🟡 Deferred — see note below | `_shared/templates/settings.php` (planned) |
| B5 | `activity.php` | ✅ Done | `_shared/templates/activity.php` + `_shared/activity_body.php` |

**B4 settings deferred** because the role-conditional panel set (Subscription tiers, Services Mode, CS settings, SS settings, MAAT add-on) is large enough that it's cheaper to do panel-by-panel during the per-portal Bucket C pass than to unify upfront. Revisit after BP Bucket C completes.

---

## BUCKET C — Per-portal, mirror Provider design

Each portal builds its own file. Provider's markup is the visual + interaction spec — copy classes, layout, modals, but rewrite the data layer for the role's domain.

**Only files that have a sidebar entry in that portal are listed.** If a Provider file has no equivalent in the CS/SS/BP sidebar (per `sidebar.php` + §4.2/§4.3), it's out of scope for that portal.

| File | Provider | CS | SS | BP |
|---|---|---|---|---|
| `dashboard.php` | ✅ ref | ✅ build | ✅ build | ✅ build |
| `continuity-stewards.php` | ✅ ref | — | ✅ read-only | — |
| `support-stewards.php` | ✅ ref | — | — | — |
| `providers.php` | — | ✅ build | ✅ build | — |
| `my-tasks.php` | — | ✅ build | ✅ build | — |
| `important-documents.php` | ✅ ref | ✅ build (countersign inbox + own signed copies) | ✅ build (read-only) | — |
| `vault.php` | ✅ ref | ✅ build (3-tab read-only, unlocks after verify) | — (no vault by design) | — |
| `continuity-management.php` | — | ✅ build (CS-only) | — | — |
| `critical-incident-log.php` | — | — | ✅ build (SS-only) | — |
| `finances.php` | ✅ ref | ✅ build (own income from MAAT Pro Service) | — | ✅ build (BP-raised invoices, milestone payouts) |

**What differs per role on shared filenames:**
- `dashboard.php` — Hero, KPI tiles, primary widgets all role-specific. P: Continuity Plan readiness ring + network carousel + CEU tracker. CS: providers caseload + my-tasks summary + active-incident cockpit. SS: Report Critical Incident CTA + my-providers + upcoming-tasks widget. BP: Capacity panel + revenue KPIs + active contracts/proposals/milestones. Greeting block + profile_strip + activity feed *could* be shared, but the rest is so different that abstracting the shell isn't worth it.
- `continuity-stewards.php` — SS only (read-only). The lone action is "Notify Practitioner — CS Unresponsive" (writes alert event; doesn't change designation). Same `.dsr-card` style as Provider.
- `important-documents.php` — P: author, sign, manage library. CS: countersignature inbox + own copy of signed plans. SS: read-only.
- `vault.php` — P: 4 zones, full CRUD, AES-256-GCM credential envelope. CS: 3-tab read-only (Support Documents / Client Roster / Secure Credentials), unlocks after incident verification, every view/download/reveal logs to activity. SS: no vault access by design.
- `finances.php` — P: Stripe Connect + invoices + payment history (no escrow). CS: own income from MAAT Pro Service, payout schedule. BP: BP-raised invoices, milestone payouts, payment-setup link. Three fundamentally different financial domains, same card system.

---

## BUCKET D — Per-portal, fundamentally different

| File | Why |
|---|---|
| `continuity-plan.php` | Provider-only (the Builder). No CS/SS/BP equivalent. |
| `news.php`, `events.php`, `network.php`, `referrals.php`, `services.php`, `job-postings.php` | Per §17.1, all Provider-only. |
| CS-only: `continuity-management.php` | Origination-side, no Provider counterpart. (Note: `providers.php` and `my-tasks.php` are also CS-only by virtue of Provider not having them, but listed under Bucket C since they have SS twins.) |
| SS-only: `critical-incident-log.php` | Origination-side. |
| BP-only: `find-jobs.php`, `contracts.php`, `proposals.php`, `milestones.php`, `invoices.php`, `payment-setup.php`, `team.php` | Marketplace + agency-team domain, no Provider equivalent. |

---

## Build order recommendation

**Bucket B (1–5) is complete except B4 settings (deferred).** Skip ahead to Bucket C.

6. **CS portal Bucket C pages**, in order:
   `dashboard.php` → `providers.php` → `my-tasks.php` → `important-documents.php` → `vault.php` → `continuity-management.php` → `finances.php`
   *(7 files)*

7. **SS portal Bucket C/D pages**, in order:
   `dashboard.php` → `providers.php` → `my-tasks.php` → `critical-incident-log.php` → `continuity-stewards.php` (read-only) → `important-documents.php` (read-only)
   *(6 files)*

8. **BP portal Bucket C/D pages**, in order:
   `dashboard.php` → `find-jobs.php` → `proposals.php` → `contracts.php` → `milestones.php` → `invoices.php` → `payment-setup.php` → `finances.php` → `team.php`
   *(9 files)*

9. **B4 settings unification** — defer until BP Bucket C completes, then revisit with all four portals' panel needs in hand.

---

## Effort delta estimate (updated)

**Naive approach (clone Provider three times, with sidebar-realistic scope):**
- CS pages with no sidebar entry are out: drop `continuity-stewards.php` and `support-stewards.php` from CS scope.
- SS pages with no sidebar entry are out: drop `support-stewards.php` from SS scope.
- CS Bucket C: 7 substantive pages.
- SS Bucket C/D: 6 substantive pages.
- BP Bucket C/D: 9 substantive pages.
- **Total: ~22 substantive pages**, each from scratch, mirroring Provider patterns by hand.

**Unified approach (Bucket B already complete):**
- 4 shared templates already shipped (overview, edit-profile, messages, activity).
- Per-portal thin wrappers for those 4 × 3 portals = 12 wrappers, mostly trivial.
- Per-portal Bucket C pages: same ~22 as above.
- B4 settings still pending — when shipped, removes ~3 portal-specific settings rebuilds.

**Net win from Bucket B:** roughly 12 page-builds avoided (overview/edit-profile/messages/activity × 3 portals), and future tone passes, design tweaks, and bug fixes on those four happen *once* and propagate everywhere. The remaining ~22 Bucket C pages are the bulk of the work ahead.

---

## Chats already opened against this plan

- ✅ CS `my-tasks.php` — full build prompt shipped
- ✅ SS `my-tasks.php` — full build prompt shipped
- ❌ CS `support-stewards.php` — prompt drafted but **discarded** (no sidebar entry)
- ❌ SS `support-stewards.php` — prompt drafted but **discarded** (no sidebar entry)

Both `support-stewards.php` chats can be closed. The CS chat for `continuity-stewards.php` should never have been planned either — also out of scope.

---

Audit complete. Build order above is the canonical sequence going forward.







=====================================================================================================


Below are the rules which I think we should follow, you can add/remove something depending upon the nature of bucket/files:

Aegis Portal Unification Rules — Learned from previous completed buckets

1. Data is user-scoped, not portal-scoped Seed data lives in seed.json keyed by user_id (p_sarah, cs_marcus, ss_linda, bp_acme). Never seed by portal folder or derive data from URL. Each demo user needs 20–25 realistic events/records that tell a coherent story for their role.

2. One template, four stubs _shared/templates/<page>.php is the single implementation. Each portal gets a 3-line stub:

<?php declare(strict_types=1); require_once __DIR__ . '/../_shared/templates/<page>.php';


No logic, no markup in portal files.

3. Role-branched helpers belong in models.php Any per-role data structure (filter options, quick tabs, module registries, section visibility flags) gets its own aegis_<page>_<thing>(string $role): array function. Never hardcode role-specific arrays inside templates or body partials.

4. UI sections are conditionally shown per role Before rendering any section, check if it applies to the current role. Examples from activity: HIPAA legend hidden for BP, export modal copy differs for BP, hero subtitle is role-specific, quick tabs and category dropdown are role-specific. Pattern: $is_bp = $role === 'business_partner' flags at top of body partial, then <?php if (!$is_bp): ?> guards.

5. Query params are universal — handling is role-aware ?module=, ?type=, ?filter= etc. always come from $_GET. The template reads them generically; role-specific registries determine whether a given param produces visible output (e.g. ?module=vault shows a banner for provider/CS but silently filters for SS because the SS module registry stub is empty).

6. Terminology is role-specific Same underlying data, different labels. BP sees "Contracts & Documents" not "Documents", "Milestones" not "Tasks". These go in the helper functions, not scattered through templates.

7. Seed gate before wiring Validate seed data completeness for all 4 demo users before writing PHP. A template with no data looks broken regardless of how correct the code is.

8. Auth is role-agnostic aegis_current_user() with no default. Any authenticated user passes — role determines what they see, not whether they can access the page.

9. php -l every file before delivery. No exceptions.

10- Before executing any task, list every file you will need to read that is not already in project knowledge — by exact filename — and wait for the user to upload them before proceeding.


Next I need a prompt for B4. I'll provide all portals files to make sure complete it  into single go.


====================================================================================



activity.php done.
overview.php done.
messages.php done. 
edit-profile.php done. 
