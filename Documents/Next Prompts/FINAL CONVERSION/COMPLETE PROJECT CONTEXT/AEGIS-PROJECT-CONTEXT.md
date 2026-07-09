# Aegis — Comprehensive Project Context (Rev 3)

**Status:** Rev 3 — modernized for the Laravel/Vue implementation, superseding the PHP prototype era.
**Validated against live repo:** `main @ 9351e14` on 2026-07-09
**Companion docs (read together):**
- `AEGIS_BILLING_LIFECYCLE.md` — subscription billing (Provider→Aegis)
- `AEGIS_PAYMENTS_FINANCE.md` — peer-to-peer payments (Provider↔BP↔CS↔Client)
- `AEGIS_SETTINGS.md` — settings pages across all portals
- `CONTINUITY_GROUP_CONVERSION_PLAN.md` — CS engagement contract + disputes + Continuity Group Vue plan
- `ENV_REFERENCE.md` — runtime env vars
- `AEGIS_CHAPMAN_PENDING_ITEMS.md` — Dr. Chapman confirmations still needed
- `AEGIS_VUE_RULES.md`, `AEGIS_LARAVEL_STRUCTURE.md`, `AEGIS_VUE_STRUCTURE.md`, `AEGIS_FILE_TREE.md` — implementation depth

**Legend:** ✅ Verified complete · ⚠️ Partial · ❌ Not implemented · 🆕 New in Rev 3

---

## Table of Contents

1. What Aegis Is (One Read)
2. The Role Model
3. The Continuity-Plan Lifecycle
4. The Five Portals
5. Database Architecture
6. Backend Layer
7. Frontend Layer
8. Demo & Query-Param System
9. Pricing, Tiers & Limits
10. Third-Party Tools & Integrations
11. Public Profile System
12. Aegis Design System
13. Onboarding Flow
14. Communication Layer (Messages, Activity, Notifications)
15. Business Partner Marketplace
16. 🆕 Peer-Payment System (Stripe Connect)
17. 🆕 CS Engagement Contract System
18. 🆕 Dispute System
19. Client Decision Log (Dr. Chapman — Confirmed & Pending)
20. Terminology Reference
21. File Naming & Conventions
22. What's Built / What's Pending (Rev 3 status)
23. Working Conventions
24. Appendix

---

## 1. What Aegis Is (One Read)

**MAAT Practice Firm** is a boutique practice management firm in Georgia, USA, founded by **Dr. Carizma Chapman, PhD, DMFT**. MAAT serves healthcare and social-service practitioners with continuity stewardship, transition coaching, and operational support. **Aegis** is the SaaS platform MAAT is launching to deliver those services at scale.

The product's purpose in one sentence: **a backup plan for a healthcare practitioner so that when they are suddenly unavailable — death, incapacitation, missing, detained, natural disaster, geopolitical conflict — their patients, records, billing credentials, and practice do not fall into chaos.**

A pre-signed continuity plan defines what happens. Two pre-designated humans (a **Support Steward** + a **Continuity Steward**) execute it. Aegis is the system of record and the operational console for that plan.

**🆕 Rev 3 additions:**
- The Continuity Steward may be paid via a **CS Engagement Contract** (pre-agreed fee per activation, auto-invoiced on incident close)
- Either party can open a **Dispute** against any peer-payment invoice/payout/session; Aegis mediates via an admin dashboard; refunds via Stripe rails
- Native "Add Card" via Stripe Elements now available in-app (all 3 paid portals)

### Meta
- **Public-facing marketing site:** `https://maatpracticefirm.com`
- **SaaS platform:** `https://aegis.devlet.tech`
- **Local dev:** `http://localhost:8000`
- **Target launch:** June 1, 2026 (past — now soft-launched in staging)
- **Built by:** Devlet LLC (Rehan Ur Rashid / Arslan, owner)

### 🆕 Rev 3 Stack (Laravel is source of truth)
The PHP prototype (SQLite + vanilla PHP files) is now **reference only** — do not edit. The Laravel application is authoritative.

- **Backend:** PHP 8.2, Laravel 12, MySQL 8. Currently 77+ models, 127 migrations, 58 enums across 14+ domains.
- **Frontend:** Vue 3, Inertia.js v3, Pinia, Vuelidate. `<script setup>` everywhere. `useForm()` for all write forms.
- **Payments:** Laravel Cashier `^16.6` (Provider→Aegis subscriptions) + Stripe PHP SDK `^17.3` (Provider→BP, Provider→CS, Client→Provider peer-to-peer via Connect Express destination charges)
- **Design system:** CSS variables only (no Tailwind, no hex literals, no inline SVGs). All modals built on `AegisModal.vue`. `<AegisIcon name="x" :size="N" />` for every icon.
- **Universal conventions:** UUID `CHAR(36)` PKs, money always integer cents (`total_cents`, `amount_cents`), soft deletes on user-facing tables, `authorize()` returns `true` in FormRequests (real auth at Policy level), Stripe Connect means Aegis never holds funds.
- **Write-path pattern:** Vue form → Inertia POST → FormRequest → Controller → Service → `ActivityService::log()` fan-out → toast + reload. Events fire from Services, not Controllers.

---

## 2. The Role Model

Four user-facing roles + anonymous state. Role stored on `users.role` (enum) + `user_role_assignments` table (multi-role support).

### 2.1 Practitioner (Provider)
Licensed clinician — the portal owner. Fills out the continuity plan, designates the stewards, uploads sensitive credentials to the vault, manages public profile.
- Portal prefix: `/provider`
- Subscription: Access ($29/mo) or Practice ($49/mo)
- Public profile: `/public/provider/<slug>`

### 2.2 Continuity Steward (CS)
The executor — licensed colleague, attorney, or CS firm. Verifies the alert, unlocks the vault, runs the task list.
- Portal prefix: `/continuity-steward`
- Sub-types: **Business CS** (paid $49/mo, public profile, 2–40 practitioners) · **Invited CS** (free, no profile, 1 practitioner) · **Enterprise** (custom quote, 41+)
- 🆕 May be paid per-activation via CS Engagement Contract (§17)

### 2.3 Support Steward (SS)
Eyes on the ground — family, office manager, trusted staff. Spots trouble and **triggers** the alert.
- Portal prefix: `/support-steward`
- No subscription — invitation-only
- 🆕 May verify incident closure on Provider's behalf if Provider unavailable (§17)

### 2.4 Business Partner (BP)
Independent marketplace vendor. Upwork-style model: practitioners post jobs → BPs propose → contracts with milestones → milestone-by-milestone payment.
- Portal prefix: `/business-partner`
- Subtypes: **Agency** (multi-person, Team Management module) · **Freelancer** (solo, personal 1099/SSN)
- Subscription: $69/mo or $57.50/mo annual ($690/yr)
- Public profile: `/public/business/<slug>`
- **Special:** BP does NOT see the cross-portal switcher — BPs don't bridge into continuity portals

### 2.5 Admin
Platform operator. Manages users, roles, payouts, refunds, packages, 🆕 disputes queue.
- Portal prefix: `/admin`
- No subscription

### 2.6 Anonymous
No login. Sees only public profile sections + sign-in CTAs.

---

## 3. The Continuity-Plan Lifecycle

**Core workflow.** Full lifecycle:

```
[ FINANCES ] — Provider has active Access or Practice subscription
     │
     ▼
[ CONTINUITY PLAN — draft ]
     │  Configure incident types (3 default + 4 opt-in) · Define per-incident task lists
     ▼   (must exist to sign)
[ CONTINUITY STEWARDS ] — designate CS → pending → active
     │  🆕 Agreed fee_cents + payment_terms + auto_charge (if paid engagement)
     │  Business CS: verify stripe_connected=1 · Invited CS: fee_cents=0 volunteer
     │  Authorization matrix (which CS per incident)
     │  🆕 CS Engagement Contract signed (if paid)
     └──────────────► CONTINUITY PLAN: SIGN — status: draft → ACTIVE
                        (needs ≥1 enabled incident config + ≥1 ACTIVE CS)
     │
     ▼
[ SUPPORT STEWARDS ] — designate SS → pending → active
     ▼
[ IMPORTANT DOCUMENTS ] — request → sign → countersign → fully_executed
     │  (CS engagement agreement lives here — legally binds fee_cents)
     ▼
[ VAULT ] — Provider uploads freely; attests; SEALED to stewards
     │
     ▼
════════ CRITICAL INCIDENT ════════
 SS reports → CS verifies → activate():
   • Generates incident_tasks from plan_tasks
   • Fires VaultUnsealed → assigned stewards gain scoped read
 CS works through tasks, marks each complete →
   🆕 when all CS tasks complete:
     • IncidentReadyForClosure event → email Provider + SS
     • Provider verifies → close()
     • OR SS verifies (Provider unavailable > 72h) → close()
     • OR system auto-closes after 7 days (CS_INCIDENT_AUTOCLOSE_DAYS)
 On close():
   • Re-seals vault
   🆕 IF plan_steward.fee_cents > 0:
       Auto-create CsInvoice (auto-generated)
       IF auto_charge=1 AND provider PM + CS stripe_connected:
         chargeProviderToCs() fires immediately → paid
       ELSE: invoice sent → Provider pays manually via Finances
```

**Corrections vs. naïve assumptions:**
- CS must be active before the plan can be signed
- Attestation is a `vault_attested_at` timestamp on the already-active plan (not a distinct status)
- Vault unseal is incident-driven and implicit, not a Provider action
- 🆕 Payment is triggered by successful close, not by task completion alone

---

## 4. The Five Portals

All five route groups exist in `routes/web.php`:

| Portal | Prefix | Middleware stack |
|---|---|---|
| Provider | `/provider` | `auth, verified.email, subscription.active, role:practitioner, check.locked` |
| Continuity Steward | `/continuity-steward` | `auth, verified.email, subscription.active, role:continuity_steward, check.locked` |
| Support Steward | `/support-steward` | `auth, verified.email, role:support_steward, check.locked` (no subscription gate) |
| Business Partner | `/business-partner` | `auth, verified.email, subscription.active, role:business_partner, check.locked` |
| Admin | `/admin` | `auth, admin` (via `EnsureAdminRole`) |

---

## 5. Database Architecture (Rev 3)

The Laravel schema now stands at **77+ models**, **127 migrations** (4 new in batch3), **58 enums** across 14+ domains.

### Rev 3 new tables
- `disputes` (batch3) — polymorphic dispute records
- `dispute_messages` (batch3) — dispute thread
- `plan_stewards` (extended in batch3) — added `fee_cents`, `payment_terms`, `auto_charge`, `engagement_document_id`

### Rev 3 new enum cases
- `InvoiceStatus::Disputed` — added to `cs_invoices.status` + `bp_invoices.status` via ENUM MODIFY migration
- `DisputeStatus`, `DisputeReason`, `DisputeResolution` — new dispute enums

### Key naming rules (non-negotiable)
- Money: **`total_cents` / `amount_cents`** — never dollars in DB or on the wire
- Provider FK on `cs_invoices` / `bp_invoices` / `bp_contracts` = **`practitioner_id`** (not `provider_id`)
- CS FK = **`cs_id`** · BP FK = **`bp_id`**
- `bp_contracts.payment_type` = **`one_time | milestone`** (not `fixed | hourly`)
- Milestone status enum = **`pending | submitted | approved | rejected | paid`** (not `open | completed`)
- Invoice status enum = **`draft | sent | overdue | paid | void | disputed`** (added `disputed` in Rev 3)
- Stripe Connect column = **`stripe_account_id`** (not `stripe_connect_account_id` — legacy column removed in P0)
- Peer-payment PM column = **`stripe_payment_method_id`** (mirrored from Cashier PM at 3 write sites)

Full schema: `AEGIS_LARAVEL_STRUCTURE.md`.

---

## 6. Backend Layer

### Services (write-path)
| Service | Purpose |
|---|---|
| `AuthService` | Register, login, logout, MFA, lock/unlock |
| `SubscriptionService` | Subscribe, upgrade/downgrade, cancel, resume, MAAT toggle, billing portal, PM mgmt |
| `PayoutService` | Peer-payment destination charges (BP/CS/session) |
| `IncidentService` | Report/verify/activate/close incidents · 🆕 `completeTask`, `maybeReadyForClosure`, `verifyClosure`, `closeWithInvoice`, `autoClose` |
| `ContractService` | BP contracts + milestones (getForBp shaped output) |
| `InvoiceService` | BP invoices (getForBp shaped output) |
| 🆕 `DisputeService` | Open/reply/resolve/list disputes |
| `PlanService` | Continuity plan lifecycle |
| `StewardService` | CS/SS designation, accept/decline, roles, authorization |
| `DocumentService` | Continuity documents + countersigning |
| `VaultService` | Vault items, permissions, attestation |
| `ProfileService` | `user_meta` read/write |
| `ActivityService` | The write-path core — `log()` fan-out with 12 positional params, actor + affected party |
| `AdminPayoutService` / `AdminPaymentService` | Admin oversight |

### Events → Listeners
- **Fan-out pattern:** Every write action fires an event; `AppServiceProvider::boot()` registers listeners on both `ActivityFanoutListener` (writes activity rows for all recipients — jobified when >3) and `SendEmailNotificationListener` (dispatches gated emails).
- 🆕 Rev 3 events registered: `IncidentReadyForClosure`, `IncidentClosureVerified`, `IncidentAutoClosed`, `CsInvoiceAutoGenerated`, `DisputeOpened`, `DisputeReplied`, `DisputeResolved`.

### Scheduled jobs (routes/console.php)
- Daily `AnnualReviewReminderJob` @ 09:00 UTC
- Daily `VaultSealCheckJob`
- Every 6h `StaleIncidentAlertJob`
- Weekly + monthly `DigestEmailJob`
- Every 5min `StripeWebhookProcessorJob`
- Every 1min `ExpireMutedThreadsJob`
- Daily `StewardResponsivenessCheckJob` @ 10:00 UTC
- Daily `SubscriptionRenewalCheckJob` @ 08:00 UTC
- 🆕 Hourly `IncidentAutoCloseCheckJob` (auto-close incidents past window without verification)

Full backend reference: `AEGIS_LARAVEL_STRUCTURE.md`.

---

## 7. Frontend Layer

Vue 3 + Inertia.js SPA per portal. Universal `AppLayout.vue` with `AppHeader`, `AppSidebar`, `AegisUpgradeModal`.

### Shared UI components
`AegisHeroBanner`, `AegisStatChip`, `AegisCard`, `AegisBadge`, `AegisEmptyState`, `AegisModal`, `AegisIcon`, `AegisPagination`. See `AEGIS_VUE_RULES.md` for usage patterns.

### 🆕 Rev 3 new components
- `AddCardModal.vue` — reusable Stripe Elements card capture (all 3 paid portals)
- `OpenDisputeModal.vue` — reusable dispute open form
- `SettingsTierUpgradeModal.vue` — Provider Access→Practice upgrade
- Dispute list + detail pages (Provider/CS/BP/Admin — 8 files)

### Composables
- `useModal()` — open/close named modals
- `useToast()` — success/error notifications
- `useForm()` — Inertia form (only at setup top-level, never inside functions)
- `useConfirm()` / `confirmAction()` — callback-based confirm (NEVER Promise/await — silent no-op if awaited)
- `useActivity()` — timeAgo, formatters
- `useProfileRoute()` — profileHref for each role
- `useUpgrade().requiresPractice(fn)` — tier gate callback

### Critical invariants (drift = bug)
- `useForm()` only at setup top-level — never inside functions or conditionals
- `useConfirm` / `confirmAction` is callback-based, never Promise/await
- `window.axios` is the pre-configured instance — `import('axios')` returns unconfigured → CSRF mismatch
- Email OTP must use `SendEmailJob::dispatchSync()` not `::dispatch()`
- Backed enums require `.value` before any string operation
- `ActivityService::log()` — position 11 = entryType (`log`|`notification`), position 12 = actorId (swapping = MySQL ENUM error)
- `pause` state stored in `user_meta` key `account_paused` (NOT `users.paused_at`)
- No `handle` column on `users` — only `phone` is editable in account settings
- Password fields use `ob-password-wrap` / `ob-password-toggle` CSS classes (NOT `input-password-wrap` / `pw-toggle`)

Full Vue rules: `AEGIS_VUE_RULES.md`.

---

## 8. Demo & Query-Param System

| Flag | Affects | Persistence |
|---|---|---|
| `?as=<user_id>` | Impersonate identity | Session (via `ImpersonateForDemo` middleware) |
| `?emergency=true/false` | Active critical incident state | Session |
| `?invited=true` | Force Invited CS view | Session |

### Demo users
| User | Role / Tier | Notes |
|---|---|---|
| `p_sarah` | Practitioner — Practice | Real Stripe sub `sub_1Tr3QvHnj73y5cBfBd6U6JCv`, real customer `cus_` |
| `p_rehan` | Practitioner — Practice | email `rehanthewebbee@gmail.com`, fake `sub_demo_rehan_practice` |
| `p_david` | Practitioner — Access | |
| `p_maria` | Practitioner — Practice, services mode on | |
| `cs_marcus` | Continuity Steward (Business CS) | |
| `ss_linda` | Support Steward | |
| `bp_acme` | Business Partner (Agency) | |
| `bp_jamal` | Business Partner (Freelancer) | |
| `admin_root` | Admin | |

All demo passwords: `Demo1234!`. Stripe account: `acct_1OCuB1Hnj73y5cBf`.

---

## 9. Pricing, Tiers & Limits

_(See `AEGIS_BILLING_LIFECYCLE.md` §16 for authoritative pricing table.)_

**🆕 Rev 3 — tier limits envified.** `config/aegis.php` now reads:
- `TIER_ACCESS_MAX_CS` (default 1)
- `TIER_ACCESS_MAX_SS` (default 1) — pending Dr. Chapman confirmation of 1 vs 2
- `TIER_PRACTICE_MAX_CS` (default 2)
- `TIER_PRACTICE_MAX_SS` (default 4)

Long-term plan: shift to database-backed admin panel for per-tier tuning.

---

## 10. Third-Party Tools & Integrations

_(Unchanged core list — Stripe, ESP (SES vs SendGrid — client decision pending), Google Analytics, help desk vendor, AWS.)_

**🆕 Rev 3 Stripe integration status — all pending items resolved:**
- Stripe Cashier subscriptions ✅
- Stripe Connect Express onboarding — real ✅ (was stub in Rev 2) for BP, CS Business, Provider
- Destination charges for all 4 peer-payment flows ✅
- `account.updated` webhook ✅ (was ignored in Rev 2)
- SetupIntent for native in-app Add Card ✅ (all 3 paid portals)

---

## 11. Public Profile System

_(Unchanged — 3-tier visibility rule preserved. `/public/<role>/<slug>`. Anonymous / logged-in / owner tiers. `PublicProfileController` handles all three roles.)_

---

## 12. Aegis Design System

_(Full detail in `AEGIS-DESIGN-SYSTEM.md` and the deep design prompts.)_

**Core rules that never drift:**
- CSS variables only (`var(--gold-dark)`, not `#a0813e`)
- No Tailwind
- All icons via `<AegisIcon name="x" :size="N" />`
- All modals built on `AegisModal.vue`
- Password fields use `ob-password-wrap` / `ob-password-toggle` (NOT `input-password-wrap` / `pw-toggle`)
- Bullet lists rendered as data tables when >3 items
- No emojis
- Hero banner + stat chips + card grid = the standard page skeleton

---

## 13. Onboarding Flow

_(Full detail in `AEGIS_BILLING_LIFECYCLE.md` §9 + §18.)_

10-step wizard: identity → email verify → intent → role → plan → payment → subscribe → dashboard.

**🆕 Rev 3 — one change** — during onboarding subscribe, PaymentMethod ID is now mirrored to `users.stripe_payment_method_id` (previously only Cashier's default was set, breaking peer-payment resolution).

---

## 14. Communication Layer

_(Unchanged core — Messages module + Activity Feed + Notifications gated via `user_meta` keys.)_

**🆕 Rev 3 — 3 new notification categories** (`user_meta` gate keys):
- `notify_dispute_{push|email|inapp}`
- `notify_finance_{push|email|inapp}`
- `notify_steward_{push|email|inapp}`

See `AEGIS_SETTINGS.md` Panel 4.

---

## 15. Business Partner Marketplace

_(Full detail in `AEGIS_PAYMENTS_FINANCE.md` §3 for money side + `AEGIS_LARAVEL_STRUCTURE.md` for CRUD detail.)_

**🆕 Rev 3 improvements:**
- BP `Contracts.vue`, `Milestones.vue`, `Invoices.vue` use correct field names (`payment_type`, `client_name`, `amount_cents`, real milestone statuses)
- BP invoice create modal with contract picker + line items (P1)
- Real BP Stripe Connect Express onboarding (P0)
- BP subscription management wired (swap/cancel/resume) (P0)

---

## 16. 🆕 Peer-Payment System (Rev 3)

Full detail: `AEGIS_PAYMENTS_FINANCE.md`.

**Four money flows:**
1. Provider → Aegis (Cashier subscription) — see `AEGIS_BILLING_LIFECYCLE.md`
2. Provider → BP (Support Services / job marketplace) — destination charge, Aegis $0 net
3. Provider → CS (invoice or engagement auto-charge) — destination charge
4. Client → Provider (My Services / clinical sessions) — destination charge

All 4 flows now have live destination-charge code paths ✅.

---

## 17. 🆕 CS Engagement Contract System (Rev 3)

Full detail: `AEGIS_PAYMENTS_FINANCE.md` §10 + `CONTINUITY_GROUP_CONVERSION_PLAN.md` §0.7.

**One-line summary:** When a Provider designates a Business CS with a pre-agreed fee per activation (`plan_stewards.fee_cents > 0`), incident close auto-generates a `CsInvoice`. If `auto_charge=1`, the Provider's card is charged immediately.

**Verification flow:**
- Normal: Provider verifies closure → CS closes with invoice
- Fallback: SS may verify if Provider unavailable 72h+
- Failsafe: System auto-verifies + closes after 7 days (`IncidentAutoCloseCheckJob`)

**Env knobs:** `CS_INCIDENT_AUTOCLOSE_DAYS` (default 7), `CS_INCIDENT_SS_FALLBACK_HOURS` (default 72).

---

## 18. 🆕 Dispute System (Rev 3)

Full detail: `AEGIS_PAYMENTS_FINANCE.md` §11 + `CONTINUITY_GROUP_CONVERSION_PLAN.md` §0.8.

**Scope:** Either party may open a dispute against a peer invoice/payout/session. Freezes the invoice (status → `disputed`) until admin resolves. Refunds fire via Stripe rails. Aegis mediates but does NOT arbitrate — non-binding on external legal action.

**Portal touchpoints:** all 4 portals have Disputes list + detail pages. Admin has queue + resolution UI.

**Env knobs:** `DISPUTE_RESPONDENT_REPLY_DAYS` (default 5), `DISPUTE_CLOSE_AFTER_RESOLUTION_DAYS` (default 7 — reserved).

⚠️ **Pending:** "Open dispute" button wire-up in Provider Finances + CS Invoices + BP Invoices (component exists, just needs button placement).

---

## 19. Client Decision Log (Dr. Chapman — Confirmed & Pending)

Full detail: `AEGIS_CHAPMAN_PENDING_ITEMS.md`.

### ✅ Confirmed
- Canonical term for core doc → "Continuity Plan"
- MAAT add-on naming → "MAAT Professional Continuity Steward Service"
- Alternate vs Support CS → "Alternate CS"
- Certification granularity → whole-list with optional per-task exception flag
- Access tier price → $29/mo · Practice tier → $49/mo (confirmed June 2026)
- Escrow language removed from all peer-payment CTAs (per attorney direction)
- W-9 gating → soft-warn only (Stripe Connect handles 1099-K reporting)

### ⏳ Pending Dr. Chapman
1. **Access tier max Support Stewards** — env default 1; verbally suggested 2 but not confirmed
2. **Founding Member perks** — 4 open questions (time-limited vs for-life, cancel/re-subscribe retention, "+2 CS free" cap behavior, marketing ad placement + redemption)
3. **Signature mechanics** — per-line typed name + title + date vs click-to-apply-on-file
4. **Financial copy** — attorney/accountant sign-off before publishing
5. **"Provider" terminology change** — Chapman was going to send replacement; still pending

---

## 20. Terminology Reference

| ❌ Legacy / Wrong | ✅ Current / Correct |
|---|---|
| Executor | Continuity Steward (CS) |
| DSR / Death Steward Rep | Support Steward (SS) |
| KALINK | Aegis |
| Death Certificate Vault | Vault (or Continuity Document Vault) |
| Provider (informal, plural) | Practitioner (formal, singular) — but "Provider" is the portal name |
| Patient | Client |
| Escrow / holds funds | Facilitates transfer / never holds funds |
| Fixed / Hourly | one_time / milestone (payment_type) |
| provider_id (on cs_invoices etc.) | practitioner_id |

---

## 21. File Naming & Conventions

### Vue pages
- `resources/js/pages/{portal}/PascalCase.vue` where portal is `provider|continuity-steward|support-steward|business-partner|admin`
- Layouts: `resources/js/layouts/AppLayout.vue`
- Shared components: `resources/js/components/ui/*.vue`
- Modals: `resources/js/components/modals/*.vue`
- Settings shared sections: `resources/js/components/settings/*.vue`

### Controllers
- `app/Http/Controllers/{Portal}/PascalCaseController.php`

### Services
- `app/Services/PascalCaseService.php`

### Migrations
- `YYYY_MM_DD_XXXXXX_verb_object.php`

Full inventory: `AEGIS_FILE_TREE.md`.

---

## 22. What's Built / What's Pending (Rev 3)

### 22.1 Portal Build Status (Laravel/Vue)
| Portal | Backend | Frontend | Notes |
|---|---|---|---|
| **Provider** | ✅ Complete | 🟡 Mostly complete | Legacy static pages: ContinuityPlan, ContinuityStewards, SupportStewards, ImportantDocuments, Vault — pending rebuild per `CONTINUITY_GROUP_CONVERSION_PLAN.md` |
| **Continuity Steward** | ✅ Complete | ✅ Mostly complete | Settings, ContinuityManagement, Providers, Invoices all wired. 🆕 Disputes list + detail added. |
| **Support Steward** | ✅ Backend complete | 🟡 Scaffolded | Vue write path partial |
| **Business Partner** | ✅ Complete | ✅ Mostly complete | Contracts, Milestones, Invoices, Settings all wired. 🆕 Disputes added. |
| **Admin** | ✅ Complete | ✅ Mostly complete | 🆕 Disputes queue + resolution added. |

### 22.2 Cross-Cutting Infrastructure ✅
- 55 Enums, 77+ Models, Auth/Middleware/Policies/Routes ✅
- 127 migrations across 14+ domains ✅
- Services / Events / Jobs / FormRequests ✅
- `ActivityService::log()` fan-out with jobified fan-out for >3 recipients ✅
- Multi-role identity + `has_cs_portal`/`has_ss_portal` in Inertia shared props ✅
- 4 portal Settings pages fully wired ✅ (Rev 3 — was ❌ in Rev 2 for CS + BP)
- MFA (TOTP + Email OTP) ✅
- Sessions tracking + revoke ✅
- Stripe subscription flow all 3 paid roles ✅
- Real Stripe Connect Express onboarding all 3 paid roles ✅ (Rev 3 — was stub in Rev 2)
- All 4 peer-payment flows built end-to-end ✅ (Rev 3 — Provider→CS was ❌ in Rev 2)
- Native "Add Card" (Stripe Elements) — Provider end-to-end; CS + BP need `storePaymentMethod` handler
- 🆕 Dispute system: data model + service + admin dashboard ✅
- 🆕 CS engagement contract: fields + service methods + auto-close job ✅
- 🆕 Tier limits envified ✅
- 🆕 `account.updated` webhook ✅
- 🆕 `SubscriptionRenewalUpcoming` email wired ✅
- 🆕 `UserTier::monthlyCents()` correct at $29/$49 ✅

### 22.3 Rev 3 pending items
| Priority | Item | Effort |
|---|---|---|
| 🔴 P1 | CS + BP `storePaymentMethod` methods | 30 min |
| 🟡 P2 | 7 email blade templates for CS engagement + dispute events | 2 hr |
| 🟡 P2 | "Open dispute" button placement in Finances tables (3 portals) | 1.5 hr |
| 🟡 P2 | Continuity Group Provider Vue rebuild (5 pages) | Multi-day workstream — see `CONTINUITY_GROUP_CONVERSION_PLAN.md` |
| 🟢 P3 | Confirm `TIER_ACCESS_MAX_SS` (1 vs 2) with Dr. Chapman | 5 min once decided |
| 🟢 P3 | Founding Member perks scope | Requires Dr. Chapman answers |
| 🟢 P3 | Admin payout oversight UI end-to-end QA | 2 hr |
| 🟢 P3 | Un-stub non-dispute refund path in `AdminPaymentService::refundPayment` | 15 min |

---

## 23. Working Conventions

1. **Laravel app is source of truth.** PHP prototype is reference only — do not edit.
2. **Fresh clone every session.** `rm -rf aegis && git clone --depth=1` — never assume the working directory state.
3. **Surgical edits only.** `str_replace` over rewrites. Never touch files outside stated scope.
4. **ZIP delivery for multi-file changes.** Mirror exact repo folder structure for drop-in at repo root.
5. **Verification gates.** `php -l` on every PHP file, `node --check` on JS, grep verification after every change.
6. **Cross-reference routes.** Compare route names between `routes/web.php` and Vue `route()` call sites — the most effective pattern for finding broken wiring.
7. **Docs go stale within sessions.** When auditing billing/payments vs. docs, read actual service files (`sed -n 'X,Yp'`) not just docs.
8. **`services_mode`** is a direct column on `users` table (not a `user_meta` key) — sidebar reads `user.services_mode` from Inertia shared data.
9. **`LOG_STACK` in `.env`** overrides `logging.php` channel config entirely — when logs don't rotate, check `.env` first.
10. **Never edit uploaded files in `/mnt/project/` or `/mnt/user-data/uploads`.** They're read-only mirrors; use `project_knowledge_search` and edit the copy at repo root.
11. **Money = integer cents everywhere.** No dollars in DB, on the wire, or in `useForm()` state (convert dollars→cents via `watch()` at submit time).
12. **Don't add scope.** Refine, simplify, restyle, unify — don't expand.
13. **One question per response.** Ask the most important; infer the rest.

---

## 24. Appendix

### 24.1 The Seven Critical-Incident Types
| Type | Always-on or Opt-in | Typical documentation |
|---|---|---|
| Death | Always | Death certificate |
| Short-Term Incapacitation | Always | Doctor's note, hospital admin |
| Long-Term Incapacitation | Always | Medical POA, doctor's letter |
| Missing Person | Opt-in | Police wellness check, missed-appointment log |
| Detainment | Opt-in | Court documentation |
| Natural Disaster | Opt-in | News confirmation, location verification |
| Geopolitical or Conflict-Related | Opt-in | News confirmation, embassy contact |

### 24.2 The Persistent Demo Flags
| Flag | Affects |
|---|---|
| `?as=<user_id>` | Identity / who's signed in |
| `?tier=access|practice` | Practitioner plan simulation |
| `?services=1|0` | Provider services mode |
| `?emergency=true|false` | Active critical incident |
| `?invited=true|false` | CS onboarding pathway (Business vs Invited) |

### 24.3 Quick Domain Cheat Sheet
- **MAAT** = the firm (`maatpracticefirm.com`)
- **Aegis** = the SaaS (`aegis.devlet.tech`)
- **Devlet LLC** = the agency building it (Rehan Ur Rashid / Arslan)
- **Dr. Carizma Chapman** = founder/client (PhD, DMFT, GA, USA)
- **Watkinsville, Georgia** = MAAT HQ
- **Client contact:** `support@maatpracticefirm.com`, phone (909) 488-9415
- **Arslan's test email:** `rehanthewebbee@gmail.com`

### 24.4 What a New Chat Can Productively Engage On
- Wiring any Vue page to live Inertia props per its controller's shape
- Adding routes + controller methods within existing service patterns
- Building shared components following `AEGIS_VUE_RULES.md`
- Producing email blade templates per `EMAIL_TEMPLATES_PROMPT.md`
- Extending seeders
- Adding migrations following the `YYYY_MM_DD_XXXXXX_verb_object` convention
- Continuity Group Provider Vue rebuild per `CONTINUITY_GROUP_CONVERSION_PLAN.md`

### 24.5 Out of Scope Without Explicit Approval
- Renaming canonical files
- Changing design tokens or palette
- Introducing new third-party JS/CSS dependencies
- Building alternative auth (Cashier + Sanctum is the stack)
- Adding emojis anywhere
- Changing "Provider" terminology (still pending Chapman's replacement)
- Finalizing financial copy (pending legal review)
- Implementing founding-member perks UI (pending Chapman answers)

---

**End of Comprehensive Context (Rev 3).**

*Rev 3 — validated against live repo commit `9351e14` on 2026-07-09*
*Rev 2 — May-June 2026 prototype-era doc, now superseded*

Pair with the four wiring blueprints, the design system reference, and the CONTINUITY_GROUP_CONVERSION_PLAN.md.
