# Aegis — Pending Items (Consolidated)

**As of:** June 19, 2026
**Reconciles:** `AEGIS-CLIENT-SETUP-GUIDE.md` + `PENDING-ITEMS.md` (Jun 6) + `AEGIS-PLAN-OF-ACTION.md` (Jun 6) + Client email (latest, third-party tools list)

---

## Headline

| Category | Status |
|---|---|
| **Provider Portal write-path** | ✅ Complete |
| **CS Portal** | ✅ Complete (all 12 pages live, design + wiring + tone) |
| **SS Portal** | ✅ Complete (all 11 pages live) |
| **BP Portal** | ✅ Complete (all 14 pages, Stripe Connect Express stubs) |
| **Carizma's Master Change List** | ✅ ~145 of ~150 items done |
| **Carizma's Apr 12 / Apr 19 emails** | ✅ Fully addressed (incl. Vault Attestation, terminology, copy fixes) |
| **Frontend** | ✅ 99% final, design system mature |
| **Backend** | ✅ Functional, dynamic, DB-driven, ~90% wired |
| **Admin portal** | ❌ Empty folder — spec written (`ADMIN-PORTAL-SPEC.md`), 6 pages to build |

---

## 🔴 A. Client-side action items (Dr. Chapman / MAAT)

Per the latest email, the helpdesk tool decision is now off the table (will be built into admin portal). Remaining client tasks:

| # | Item | Status | Notes |
|---|---|---|---|
| A.1 | **Stripe account** | ⏳ Pending — Carizma to start FIRST (2–3 day verification) | Practitioner subscriptions + CS payouts + BP invoices |
| A.2 | **AWS account** | ⏳ Pending | Hosting, DB, file storage |
| A.3 | **Amazon SES setup** | ⏳ Pending | Transactional emails — covered under AWS BAA |
| A.4 | **Google Analytics + GTM** | ⏳ Pending | Usage + ad campaign tracking, PHI-excluded config approved |
| A.5 | **AWS BAA execution** | ⏳ Pending | Free via AWS Artifact |
| A.6 | **AWS BAA covers SES** | ⏳ Pending | Same BAA, no separate vendor |
| A.7 | **Privacy Policy + BAA copy review** | ⏳ Pending | Vault access-model language already drafted |

**No longer pending** (per the latest email):
- ~~Helpdesk tool decision~~ → built into admin portal instead
- ~~Separate ESP BAA~~ → SES covered by AWS BAA
- ~~File storage approach~~ → confirmed AWS S3 + signed URLs

---

## 🟡 B. Devlet build commitments — status check

### B1. Authentication & Onboarding

| # | Item | Status |
|---|---|---|
| B1.1 | Password reset flow | ⏳ Pending — needs SES wired |
| B1.2 | Multi-factor authentication (MFA) | ⏳ Phase 2 / post-launch |

### B2. Support & Feedback

| # | Item | Status |
|---|---|---|
| B2.1 | Help ticket submission (now native, not 3rd-party) | ❌ **Move into Admin Portal build** — was the `complaints.php` page in admin spec |
| B2.2 | Feedback Button (always-visible) | ❌ Pending |
| B2.3 | In-app contextual questionnaires | ❌ Pending |
| B2.4 | Open free-form feedback | ❌ Pending |
| B2.5 | Internal feedback dashboard (MAAT view) | ❌ Pending — likely a new admin portal page or merge with complaints |

### B3. White-Glove Service Workflow

| # | Item | Status |
|---|---|---|
| B3.1 | MAAT internal staff role (excludes Vault) | ❌ Schema work — needs new role enum value + permission gating |
| B3.2 | Tracked-changes audit log | ❌ Pending — partial: `admin_audit_log` table is in admin v16 spec |
| B3.3 | Practitioner authorization workflow (notify → review → approve/reject) | ❌ Pending |
| B3.4 | Digital authorization record storage | ❌ Pending |

### B4. Visibility Permissions

| # | Item | Status |
|---|---|---|
| B4.1 | Public profile visibility toggle | ⚠️ Toggle exists in Settings, needs reads to respect it on `public/*.php` pages |
| B4.2 | Network visibility scope | ⚠️ Same — toggle exists, reads need to gate |
| B4.3 | Per-steward visibility (standby vs incident-active) | ⚠️ Vault zone permissions exist; needs full audit across all CS/SS portal reads |

### B5. Email Integration

| # | Item | Status |
|---|---|---|
| B5.1 | Transactional email pipeline (SES wired) | ⏳ Blocked on A.3 |
| B5.2 | Email templates (invitations, password reset, incident alerts, digests) | ❌ Pending — design first, then HTML |

### B6. Integration Checklist Document

| # | Item | Status |
|---|---|---|
| B6.1 | Detailed integration checklist sent to Carizma | ✅ **Done** — `AEGIS-CLIENT-SETUP-GUIDE.md` is this deliverable |

---

## 🟠 C. Own-Workflow Features (not blocking launch)

| # | Item | Status |
|---|---|---|
| C.1 | OAuth integrations (Calendar, EHR) | ❌ Pending — per integration |
| C.2 | Sessions table + revoke flow | ❌ Pending — needs `user_sessions` schema (column exists, no UI/flow) |
| C.3 | API keys table + revoke flow | ❌ Pending — needs `user_api_keys` schema |
| C.4 | Webhooks delivery + test event | ❌ Pending — needs `user_webhooks` schema |
| C.5 | Practice transfer workflow | ❌ Pending — multi-step own workflow |
| C.6 | Plan change request | ❌ Pending — tied to Stripe Connect |

---

## 🔵 D. Other Portals — Now Complete

Previously listed as not-started. All 3 are now ✅ live:

| Portal | Build state |
|---|---|
| **CS Portal** | ✅ 12 pages — full design + wiring + tone + Vault attestation feature + role-change requests + Annual Re-Attestation |
| **SS Portal** | ✅ 11 pages — full design + wiring + Critical Incident Log + Vault attestation chip |
| **BP Portal** | ✅ 14 pages — Agency + Freelancer architecture, Stripe Connect Express stubs, 5 DB tables, 5 user columns, 9 documented + 12 auxiliary helpers |

**Cross-portal stubs from `PENDING-ITEMS.md` E section** — most are now wired since BP portal exists. Audit needed for the ~15 hire/proposal/contract handshakes to confirm two-way flows are functional vs still partial.

---

## 🟣 E. Newly Surfaced Items (from this chat's audits)

### E1. Admin Portal (greenfield — 6 pages)

Spec written (`ADMIN-PORTAL-SPEC.md`). Nothing built yet.

| Page | Status |
|---|---|
| `dashboard.php` | ❌ Build needed |
| `packages.php` | ❌ Build needed |
| `users.php` | ❌ Build needed |
| `roles.php` | ❌ Build needed |
| `payments.php` | ❌ Build needed |
| `complaints.php` | ❌ Build needed (covers help ticket from B2.1) |

**Schema additions for admin (v16):**
- `package_overrides`, `admin_audit_log`, `roles`, `role_permissions`, `complaints`, `complaint_replies`
- `users.locked_at`, `users.locked_reason`, `users.deactivated_at`

**New write endpoints:** `save_admin_user.php`, `save_admin_package.php`, `save_admin_role.php`, `save_admin_payment.php`, `save_admin_complaint.php`

**`aegis_require_admin()` middleware** — to add in `models.php`

### E2. Carizma's New MA'AT Continuity Reserve copy block

From the master change list bottom block — introduces a new product feature:
- "$3,000 Continuity Response Reserve" copy
- 6 paragraphs of MA'AT brand messaging
- **Status:** ❌ Awaiting Carizma's confirmation on where it lives (dashboard banner? new onboarding step? marketing page?). 5 specific questions sent in `Aegis_Provider_Build_Status_Report.md`.

### E3. Supporting Continuity Steward role

- Email Key Terms section defined 3 CS roles: Primary / Support / Alternate
- Current schema supports only Primary + Alternate (Support was retired at v12)
- **Status:** ❌ Awaiting Carizma's decision on restoring the Support CS role — requires schema migration + UI work

### E4. Discoverability — "Offers Services" filter

- Carizma's Apr question: "How will providers discover other providers offering services?"
- Recommendation given: Option D — add "Offers Services" filter tab to `network.php` two-tier nav
- **Status:** ❌ Awaiting Carizma's go-ahead

---

## 🟢 F. Resolved Since Last PENDING-ITEMS.md (Jun 6 → Jun 19)

| Item | How resolved |
|---|---|
| Vault Attestation (Apr 12 email — CS workflow step 7) | ✅ Built end-to-end — schema v15, helper, endpoint, Provider UI, CS chip, SS chip, 11-recipient fan-out verified |
| Carizma Apr 12 copy fixes (5 items: upgrade modal, subscription panel, Danger Zone rename, IBS Mode toggle) | ✅ All applied across 4 portals |
| Provider Portal Master Change List (~145 of ~150 items) | ✅ Done, validated in audit pass |
| Overview Start Here rewrite (30+ paragraphs, 6 Why cards, 7 How steps, 16 FAQs incl. 7 new) | ✅ Done in `aegis_overview_data()` |
| Activate Continuity Support flow (title + "Incident Type" + "What happens next" rewrite) | ✅ Done in `dashboard.php` |
| Annual Re-Attestation 8-checkbox list | ✅ Built |
| Renew → Update terminology sweep (License + Insurance) | ✅ Done across `dashboard.php` |
| Network hero subtitle + Shadow Provider subtitle rewrites | ✅ Done |
| Services page hero rewrite | ✅ Done |
| CS designation page subtitle rewrite | ✅ Done |
| Counter Referral feature removal | ✅ Done |
| Patients page → Client Roster in Vault | ✅ Done |
| Job Posting → Support Request rename | ✅ Done |
| Healthcare → Practitioner sweep | ✅ Done (proper nouns preserved) |
| CENTRALIZED-SYSTEM.md | ✅ Updated with all 48 _shared files, 204 read + 122 write helpers, 21 endpoints, 42 tables, schema v15 |

---

## 📊 Summary

| Category | Pending count | Notes |
|---|---|---|
| 🔴 A. Client setup (Stripe/AWS/SES/GA + BAAs + policy review) | 7 | Stripe is the long-pole — start first |
| 🟡 B. Devlet email commitments | ~12 | Password reset, feedback channels, white-glove workflow, visibility audit, email templates |
| 🟠 C. Own-workflow features | 6 | Sessions, API keys, webhooks, OAuth, practice transfer, plan change |
| 🟣 E1. Admin Portal pages | 6 | All greenfield — spec written, build pending |
| 🟣 E2-E4. Carizma decisions pending | 3 | MA'AT Reserve placement, Supporting CS restore, Services filter |
| **Total open items** | **~34** | Down from ~50 in Jun 6 baseline |

---

## 🎯 Recommended Order

1. **Carizma → Stripe account** (2–3 day verification — start immediately per latest email)
2. **Carizma → AWS account + BAA execution** (parallel with Stripe)
3. **Devlet → Build Admin Portal Phase 1** (dashboard + users + complaints — covers B2.1 help tickets)
4. **Devlet → Password reset + SES integration** (after A.3 SES live)
5. **Devlet → Feedback channels** (B2.2–B2.5) — build into admin portal infrastructure
6. **Devlet → White-glove workflow + visibility audit** (B3, B4)
7. **Carizma → Confirm MA'AT Continuity Reserve placement + Supporting CS decision + Services filter**
8. **Devlet → Stripe Connect integration** (after A.1 verified)
9. **Devlet → AWS S3 wiring for file binaries** (Vault uploads, PDFs, exports)
10. **Devlet → MFA + post-launch own-workflow features** (Phase 7)

---

## 📂 Status of canonical project docs

| Doc | Updated |
|---|---|
| `AEGIS-PROJECT-CONTEXT.md` | Needs refresh — should reflect schema v15, CS/SS/BP portal completion, admin portal spec |
| `CENTRALIZED-SYSTEM.md` | ✅ Refreshed Jun 19 — accurate as of latest commit |
| `ADMIN-PORTAL-SPEC.md` | ✅ Current — 6 pages, full schema, controllers, endpoints |
| `PENDING-ITEMS.md` (Jun 6 version) | ⚠️ Superseded by this document |
| `AEGIS-PLAN-OF-ACTION.md` | ⚠️ Phases 4–6 are now ✅ done — needs rewrite reflecting admin portal as remaining major build |
| `AEGIS-CLIENT-SETUP-GUIDE.md` | ✅ Current — matches latest email |

---

*This document supersedes the Jun 6 `PENDING-ITEMS.md`. Refresh after each major decision or build wave.*
