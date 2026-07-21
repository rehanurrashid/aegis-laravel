# Aegis — Support Services Module
## Complete Workflow Reference · Both Portals · Rev 2 · July 2026

**Supersedes:** Rev 1 (escrow-backed model)
**Rev 2 anchor commit:** `main @ 65d3f47`

---

## Rev 2 — What Changed and Why

Dr. Chapman's directive (July 2026): **Aegis never holds funds. Payment routes directly from Provider to Business Partner via Stripe Connect. Parties negotiate terms; Aegis records what they agreed to and fires the destination charges — nothing more.**

### Removed in Rev 2
- ❌ Aegis-held escrow (funds sitting in the platform balance pending release)
- ❌ "Fund escrow" as a workflow step — providers no longer pre-fund anything
- ❌ `EscrowService` as an active pathway (kept in codebase as a deprecated shim for legacy contracts only)
- ❌ `bp_escrow_ledger` writes for new contracts (table retained for historical audit)
- ❌ `escrow_funded_cents` / `escrow_released_cents` / `escrow_refunded_cents` running totals (kept for legacy read)
- ❌ Two-step charge-then-release Stripe pattern — replaced with atomic destination charge
- ❌ `MilestoneAutoReleaseJob` (auto-transfer of held funds) → replaced by `MilestoneAutoApproveJob` (auto-fire of direct charge if provider doesn't respond)
- ❌ Milestone statuses `funded` / `in_progress` between funding and submission
- ❌ `FundContractModal` / `FundMilestoneModal` (Vue) — deleted
- ❌ Copy: *"held in escrow"*, *"funds released from Aegis balance"*, *"Aegis holds…"* — banned in Support Services UI/emails after Rev 2

### Added in Rev 2
- ✅ **Three payment structures:** `full_upfront` · `split` · `per_milestone` (milestone contracts) OR `on_completion` (one-time contracts)
- ✅ **Terms negotiation flow embedded in the proposal/hire path** — BP proposes structure + upfront % in the proposal; provider accepts as-is or counters in Hire modal
- ✅ **Direct destination charges everywhere** — `PayoutService::chargeProviderToBp` becomes the sole money-moving primitive
- ✅ **Explicit disclosure** in every payment modal + email: *funds go direct to the BP via Stripe; Aegis is not the recipient and does not hold these funds*
- ✅ **`MilestoneAutoApproveJob`** — after N days, auto-fires the direct charge if the provider didn't act (functionally similar deadline pressure to Rev 1 auto-release, but no funds were ever held)
- ✅ **Refunds via Stripe `reverse_transfer: true`** — for approved-then-disputed milestones, funds are pulled back from BP's Connect account through Stripe's rails; Aegis's platform balance is never touched
- ✅ **`bp_contract_terms` snapshot** — an immutable record of what was agreed at signing time, referenced by disputes

### Preserved
- ✅ Contract lifecycle events (create, sign, complete, cancel)
- ✅ Proposal + pipeline stage flow (submit / review / hire / decline)
- ✅ Milestone submission workflow (submit → review → approve/revise)
- ✅ Dispute system (still binds to milestones + contracts)
- ✅ Post-completion review system
- ✅ All email trigger points (some templates renamed for clarity — see §10)

---

## Table of Contents

1. [Overview](#1-overview)
2. [Architecture — Aegis Never Holds Funds](#2-architecture)
3. [Payment Structures](#3-payment-structures)
4. [Terms Negotiation Flow](#4-terms-negotiation-flow)
5. [Data Model (Rev 2 changes)](#5-data-model)
6. [Status State Machines (Rev 2)](#6-status-state-machines)
7. [Provider Journey](#7-provider-journey)
8. [Business Partner Journey](#8-business-partner-journey)
9. [Dual-Party Workflow Timeline](#9-dual-party-workflow-timeline)
10. [Payment Flows](#10-payment-flows)
11. [Automated Jobs & Timers](#11-automated-jobs--timers)
12. [Email Notifications Matrix](#12-email-notifications-matrix)
13. [Activity Log Events](#13-activity-log-events)
14. [API Route Reference](#14-api-route-reference)
15. [Environment Configuration](#15-environment-configuration)
16. [Demo Users](#16-demo-users)
17. [Appendix A — Migration From Rev 1](#appendix-a)

---

## 1. Overview

**Support Services** connects Practitioners to Business Partners (BPs) for non-clinical practice support — billing, compliance, IT, marketing, legal, and operations.

In Rev 2 the payment model is **direct-to-BP destination charges** governed by terms both parties agreed to before signing. Aegis is not a payment processor, escrow agent, or counterparty. Aegis:
- Provides a marketplace and messaging layer
- Records the terms of engagement
- Fires the Stripe destination charges the parties committed to
- Provides mediation tooling (disputes) but is not a party to the transaction

### Portals Involved
| Role | Portal | URL Prefix | Entry Page |
|---|---|---|---|
| Practitioner | Provider Portal | `/provider` | `/provider/support-services` |
| Business Partner | BP Portal | `/business` | `/business/find-jobs` |

### Key Invariants
- Money is **always stored in cents** (`total_value_cents`, `amount_cents`, etc.)
- Aegis **never holds funds** — every charge is a Stripe destination charge, atomic to the BP's Connect account
- Demo entities detected by `cus_demo_*` / `pm_demo_*` / `acct_demo_*` prefix → Stripe calls stubbed
- Contract FK on invoices: `practitioner_id` (never `provider_id`)
- BP FK: `bp_id` · CS FK: `cs_id`

---

## 2. Architecture — Aegis Never Holds Funds

### The Rule
> **Aegis is not a payment processor. Aegis is a routing layer.**

Every payment in Support Services is a Stripe **destination charge** (`transfer_data.destination = bp.stripe_account_id`). The funds are atomically routed to the BP's Connect account at authorization time. Aegis's platform balance is never debited or credited by Support Services transactions.

### Money Path (universal)
```
Provider's saved card (users.stripe_payment_method_id)
    ↓  PaymentIntent { transfer_data.destination = bp.stripe_account_id }
Aegis Platform account ($0 net)
    ↓  atomic destination transfer
BP's Stripe Connect account (users.stripe_account_id)
    ↓  BP's own payout schedule
BP's bank
```

### Refund Path (universal)
```
Aegis → refunds.create(payment_intent: original_intent_id, reverse_transfer: true)
    ↓  Stripe pulls funds from BP's Connect balance
    ↓  Returns funds to provider's card
Aegis Platform account ($0 net — never touched)
```

### What Aegis Does
- Records what the parties agreed to (payment structure, upfront %, terms note)
- Fires the direct charge at the agreed moment (signing, per milestone, on completion)
- Records the Stripe intent + charge ID on the contract/milestone
- Fires activity + email notifications
- If a refund is issued via dispute: calls Stripe with `reverse_transfer: true`

### What Aegis Does NOT Do
- ❌ Hold funds pending milestone approval
- ❌ Pre-fund milestones ("escrow")
- ❌ Enforce a fixed percentage split
- ❌ Guarantee the transaction — parties deal directly
- ❌ Act as counterparty to any charge

### UI Language Standard
Every payment modal, invoice, email, and tooltip in Support Services must include (or link to) this note:

> *Payment routes directly to the Business Partner's Stripe account via Stripe Connect. Aegis does not hold, escrow, or process funds on your behalf. You are transacting directly with the Business Partner.*

Old copy referring to "held in escrow," "Aegis balance," "released from escrow," or "escrow bar" must not appear anywhere in Support Services after the Rev 2 rollout.

---

## 3. Payment Structures

Contract-level `payment_structure` field is authoritative once signed. The valid options depend on `contract.payment_type`:

### 3.1 One-Time Contracts (`payment_type = one_time`)
Available structures:

| Structure | When charged | Payment status path |
|---|---|---|
| `full_upfront` | Full amount at both-parties-signed | `unsigned → signed → paid` |
| `on_completion` | Full amount when marked complete | `unsigned → signed → active → paid` |
| `split` | X% at signed + (100−X)% at completion | `unsigned → signed → upfront_paid → paid` |

### 3.2 Milestone Contracts (`payment_type = milestone`)
Available structures:

| Structure | When charged | Payment status path |
|---|---|---|
| `full_upfront` | Full contract value at both-parties-signed | `unsigned → signed → paid` (all milestones marked pre-paid) |
| `per_milestone` | Each milestone amount as approved | `unsigned → signed → active → (per-milestone) → paid` |
| `split` | X% at signed + per-milestone drawdown of remaining (100−X)% until 100% reached | `unsigned → signed → upfront_paid → active → paid` |

### 3.3 Default preload
When a BP opens the Propose modal on a job posting, the modal preloads:
- Structure from `bp_jobs.default_payment_structure` (the provider's baseline offer on the job)
- Upfront % from `bp_jobs.default_upfront_percentage` (if `split`)
- Terms note from `bp_jobs.default_terms_note`

The BP may propose as-is or edit any of the three fields — final proposal terms sit on `bp_proposals.proposed_*`.

### 3.4 Provider override at Hire time
When the provider opens the Hire modal on an accepted proposal, they see the BP's proposed terms. They may:
- **Accept as proposed** — commit BP's terms to the contract
- **Counter with different terms** — change structure / upfront % / note

There is no separate multi-round negotiation. The Hire click is contract-formation. If either party wants different terms after that, the mechanism is a new proposal on a new (or reopened) posting.

### 3.5 Structure comparison — trust-and-cash tradeoffs

| Structure | Provider risk | BP confidence | Best fit |
|---|---|---|---|
| `full_upfront` | High (paid before any work) | Highest | Trust-established relationships, small-value one-off tasks |
| `split` (typical 25–50%) | Medium (partial commitment) | High | Balanced standard offer — usually the default |
| `per_milestone` | Low (each unit approved before paid) | Medium — depends on provider approving on time | Multi-month work, novel BP relationship |
| `on_completion` | Lowest (pay only when satisfied) | Lowest — BP works on trust | Only used when BP is confident in provider's track record |

---

## 4. Terms Negotiation Flow

### 4.1 Who proposes what — decision matrix

| Scenario | Terms shown | Committed by |
|---|---|---|
| BP opens Propose modal on a job | Provider's job defaults | BP's Submit Proposal click |
| BP edits terms in the Propose modal | BP's edited proposal | BP's Submit Proposal click |
| Provider opens Hire modal on a proposal | BP's proposed terms | Provider's Hire click |
| Provider counters in Hire modal | Provider's counter | Provider's Hire click |

The final `payment_structure` / `upfront_percentage` on the `bp_contracts` row is authoritative. `terms_source` records who won: `provider_default`, `bp_proposed`, or `provider_countered`.

### 4.2 Data flow through the tables

```
bp_jobs
  ├─ default_payment_structure       ("split")
  ├─ default_upfront_percentage      (30)
  ├─ default_terms_note              ("Weekly check-ins required.")
  └─ allow_on_completion             (0 = block on_completion / hide from BP)

           │  BP opens Propose modal, preloads from bp_jobs.*
           ▼
bp_proposals
  ├─ proposed_payment_structure      ("split" | "full_upfront" | "per_milestone" | "on_completion")
  ├─ proposed_upfront_percentage     (30)
  ├─ proposed_terms_note             ("Weekly check-ins required.")
  └─ terms_source                    ("provider_default" | "bp_proposed")

           │  Provider opens Hire modal, may accept-as-is or counter
           ▼
bp_contracts
  ├─ payment_structure               (final committed)
  ├─ upfront_percentage              (final committed)
  ├─ upfront_cents                   (computed at hire: floor(total × pct / 100))
  ├─ remaining_cents                 (total − upfront_cents)
  ├─ terms_note                      (final committed)
  ├─ terms_source                    ("provider_default" | "bp_proposed" | "provider_countered")
  └─ terms_agreed_at                 (timestamp of provider's Hire click)

           │  Both parties sign
           ▼
bp_contract_terms                     (immutable snapshot — never updated after signing)
  ├─ contract_id
  ├─ payment_structure
  ├─ upfront_percentage
  ├─ upfront_cents
  ├─ terms_note
  └─ snapshotted_at                   (moment fully_executed_at was set)
```

The `bp_contract_terms` snapshot exists so that disputes have a legally-clean copy of what was agreed, even if UI code later evolves the field semantics.

### 4.3 UI touchpoints

| Location | What's shown | What can change |
|---|---|---|
| Job posting create/edit (Provider) | Default Payment Terms panel | Structure, upfront %, terms note, allow_on_completion toggle |
| Public job listing card | Terms chip summarizing default | — |
| BP Propose modal (`BpProposeModal`) | Preloaded defaults + editable Payment Terms Inline block | Structure, upfront %, terms note |
| Provider Hire modal (`HireModal`) | BP's proposed terms + Counter Terms Inline | Structure, upfront %, terms note |
| Contract signing modal (`SignContractModal`) | Read-only committed terms summary | — |
| Contract PDF | Full terms text | — |
| Every payment confirmation modal | Committed terms recap + agreement checkbox | — |

### 4.4 Agreement checkbox — every payment modal
Every provider-side payment modal (upfront charge at signing, per-milestone approve-and-pay, completion charge) requires:

> *I authorize this payment of $X to `{bp name}` via Stripe. I understand Aegis is not the recipient and does not hold these funds. Committed terms: `{structure summary}`. `{terms note}`*

`authorize_payment: required|accepted` server-side. Vuelidate on client side.

---

## 5. Data Model

Rev 2 changes are additive at the schema level. Old columns retained for historical audit. New columns are the source of truth for all new contracts.

### 5.1 `bp_jobs` — Rev 2 additions

| Column | Type | Default | Notes |
|---|---|---|---|
| `default_payment_structure` | ENUM(`full_upfront`,`split`,`per_milestone`,`on_completion`) | `split` | Provider's baseline offer |
| `default_upfront_percentage` | UNSIGNED TINYINT | `30` | 1–99, ignored unless `split` |
| `default_terms_note` | TEXT | NULL | Free-form terms text |
| `allow_on_completion` | TINYINT(1) | `0` | If 1, BPs may propose `on_completion` (one-time only) |

**Migration:** `2026_07_21_000010_add_default_payment_terms_to_bp_jobs.php`

Legacy columns retained: `payment_type` (`one_time`/`milestone`), `funding_mode` (deprecated — read-only in Rev 2; new contracts leave this NULL).

### 5.2 `bp_proposals` — Rev 2 additions

| Column | Type | Default | Notes |
|---|---|---|---|
| `proposed_payment_structure` | ENUM(`full_upfront`,`split`,`per_milestone`,`on_completion`) | `split` | BP's proposal |
| `proposed_upfront_percentage` | UNSIGNED TINYINT | `30` | 1–99 |
| `proposed_terms_note` | TEXT | NULL | |
| `terms_source` | ENUM(`provider_default`,`bp_proposed`) | `provider_default` | |

**Migration:** `2026_07_21_000011_add_proposed_payment_terms_to_bp_proposals.php`

### 5.3 `bp_contracts` — Rev 2 additions

| Column | Type | Default | Notes |
|---|---|---|---|
| `payment_structure` | ENUM(`full_upfront`,`split`,`per_milestone`,`on_completion`) | `per_milestone` | Final committed structure |
| `upfront_percentage` | UNSIGNED TINYINT | `0` | Committed pct — 100 for full_upfront, 0 for per_milestone/on_completion, X for split |
| `upfront_cents` | UNSIGNED INT | `0` | Computed: `floor(total_value_cents × upfront_percentage / 100)` |
| `remaining_cents` | UNSIGNED INT | `0` | `total_value_cents − upfront_cents` |
| `terms_note` | TEXT | NULL | |
| `terms_source` | ENUM(`provider_default`,`bp_proposed`,`provider_countered`) | `provider_default` | |
| `terms_agreed_at` | TIMESTAMP | NULL | Set at Hire click |
| `paid_cents` | UNSIGNED INT | `0` | Running total charged direct-to-BP (all portions summed) |
| `upfront_charge_intent_id` | VARCHAR(64) | NULL | Stripe PI ID for the upfront portion |
| `upfront_charged_at` | TIMESTAMP | NULL | |
| `completion_charge_intent_id` | VARCHAR(64) | NULL | PI ID for the on_completion / split-remainder charge |
| `completion_charged_at` | TIMESTAMP | NULL | |

**Migration:** `2026_07_21_000012_add_committed_payment_terms_to_bp_contracts.php`

Legacy columns retained (read-only for historical rows): `escrow_funded_cents`, `escrow_released_cents`, `escrow_refunded_cents`, `funding_mode`, `transfer_group`.

### 5.4 `bp_milestones` — Rev 2 changes

New columns:
| Column | Type | Default | Notes |
|---|---|---|---|
| `payment_intent_id` | VARCHAR(64) | NULL | Direct-charge PI ID for this milestone (per_milestone contracts) |
| `paid_at` | TIMESTAMP | NULL | Set when direct charge fires |
| `paid_cents` | UNSIGNED INT | `0` | Actual amount charged (may differ slightly from `amount_cents` if partial admin adjustment) |
| `auto_approve_at` | TIMESTAMP | NULL | Set on submit; auto-approve+charge fires when past |

**Migration:** `2026_07_21_000013_add_direct_charge_columns_to_bp_milestones.php`

Legacy columns retained (read-only for historical rows): `escrow_intent_id`, `escrow_charge_id`, `funded_cents`, `released_cents`, `refunded_cents`, `auto_release_at`.

### 5.5 New table: `bp_contract_terms`

| Column | Type | Notes |
|---|---|---|
| `id` | CHAR(36) PK | `bct_` + 12-char random |
| `contract_id` | CHAR(36) FK → `bp_contracts` | UNIQUE — one snapshot per contract |
| `payment_structure` | ENUM(same values) | |
| `upfront_percentage` | UNSIGNED TINYINT | |
| `upfront_cents` | UNSIGNED INT | |
| `remaining_cents` | UNSIGNED INT | |
| `total_value_cents` | UNSIGNED INT | |
| `terms_note` | TEXT | NULL |
| `terms_source` | ENUM(same values) | |
| `snapshotted_at` | TIMESTAMP | Set at `fully_executed_at` |

**Migration:** `2026_07_21_000014_create_bp_contract_terms_table.php`

Row is inserted once when the second-to-sign party signs. Never updated. Used as source of truth in disputes.

### 5.6 `bp_escrow_ledger` — retained for audit

Table untouched. No new inserts for Rev 2 contracts. Old rows readable for historical reconciliation. `EscrowService::writeLedger()` calls only fire for legacy contract IDs (see §Appendix A).

---

## 6. Status State Machines (Rev 2)

### 6.1 Job Posting (`bp_jobs.status`) — unchanged
```
draft ──▶ open ──▶ paused ──▶ open
                    │
                    ├──▶ filled
                    ├──▶ closed
                    └──▶ cancelled
```

### 6.2 Proposal (`bp_proposals.status`) — unchanged
```
submitted → pending → accepted (→ contract created in pending_signature)
                    → declined
                    → withdrawn
```
Pipeline stages: `new → reviewed → shortlisted → interview → hired / rejected`

### 6.3 Contract (`bp_contracts.status`) — Rev 2 updated

Notable removals: `pending_funding` state and its transitions.

```
draft
  └──▶ pending_signature   (after ProposalService::accept())
         └──▶ active       (after both parties sign — replaces old pending_funding step)
                │
                │  Structure-driven behavior on entering `active`:
                │   • full_upfront → immediately fire upfront charge (100%)
                │                    on success: paid_cents = total, milestones = paid
                │   • split        → immediately fire upfront charge (X%)
                │                    on success: paid_cents = upfront_cents
                │   • per_milestone → no charge fires; wait for milestone approvals
                │   • on_completion → no charge fires; wait for completion
                │
                ├──▶ completed  (all payments settled; one-time on completion event, milestone when all milestones paid)
                ├──▶ cancelled  (may trigger refund of upfront_cents if any)
                └──▶ disputed
```

Legacy `pending_funding` state kept as a valid enum value for historical rows only. New contracts skip it.

### 6.4 Milestone (`bp_milestones.status`) — Rev 2 updated

```
pending                              (default on contract activation for per_milestone)
  └──▶ in_progress                   (BP starts work — self-service state change, no money)
         └──▶ submitted              (BP submits work; auto_approve_at set)
                ├──▶ approved        (provider approves — fires direct charge)
                │      └──▶ paid    (Stripe destination charge succeeded)
                ├──▶ revision_requested  (revision_count++; BP may resubmit → submitted)
                └──▶ disputed        (admin mediates; no funds held)
                       ├──▶ paid   (resolved in BP's favour → direct charge fires)
                       └──▶ cancelled (resolved in provider's favour → no charge; if already paid: refund fires)
  └──▶ cancelled                     (provider cancels before submission — no money moved)
  └──▶ prepaid                       (full_upfront contract — milestone marked pre-paid at signing, tracked but no per-milestone charge)
```

Removed statuses: `funded` (no funding step), `released` (no release step — replaced by `paid`), `refunded` on the milestone level (refunds go through dispute or contract-cancel paths).

Legacy statuses (`funded`, `released`, `refunded`) kept as enum values for historical rows only.

---

## 7. Provider Journey

### Step 1: Post a Support Request

**Page:** `/provider/support-services` → My Postings tab
**Action:** Click "Request Support" → `PostJobModal`

**Fields (Rev 2 additions bolded):**
- Title, category, description, budget range, location preference
- `payment_type`: `one_time` or `milestone`
- **`default_payment_structure`**: one of the four (options constrained by payment_type — see §3)
- **`default_upfront_percentage`**: if `split`, integer 1–99
- **`default_terms_note`**: free-form text
- **`allow_on_completion`**: toggle (one-time only)

**Removed:** `funding_mode` field (deprecated — new posts leave this NULL).

**Route:** `POST provider.jobs.store` · **Service:** `BpJobService::create()`
**Result:** Job in `draft` status. Publish flips to `open`.

---

### Step 2: Receive & Review Applications

Unchanged in mechanism. **Applications now show the BP's proposed payment terms** as a chip on each application card; click expands to full proposed_terms_note. Provider can filter applications by proposed structure (e.g. show only `full_upfront` proposals).

---

### Step 3: Hire a Business Partner

**Action:** Click "Hire This Person" in `ApplicantProfileModal` → opens `HireModal`
**Route:** `POST provider.jobs.proposal.accept` · **Service:** `ProposalService::accept()`

**HireModal Rev 2 additions:**
- Shows BP's proposed payment terms (read-only summary at top)
- **`CounterTermsInline` component below** (mirrors CounterOfferInline pattern):
  - Toggle: "Accept terms as proposed" (default on)
  - When toggled off: three-radio structure picker + upfront % input + terms note

**Form fields:**
```
terms_countered              nullable|boolean
committed_payment_structure  nullable|in:full_upfront,split,per_milestone,on_completion
committed_upfront_percentage nullable|integer|min:1|max:99
committed_terms_note         nullable|string|max:5000
```

**What happens:**
1. Proposal → `accepted`; all other proposals on same job → `declined`
2. `BpContract` created with committed terms:
   - If `terms_countered = false`/null → inherits `proposal.proposed_*` + `terms_source = ${proposal.terms_source}`
   - If `terms_countered = true` → takes counter values + `terms_source = provider_countered`
   - Computes `upfront_cents = floor(total_value × upfront_percentage / 100)`, `remaining_cents = total − upfront_cents`
3. `terms_agreed_at = now()`
4. Contract → `pending_signature`
5. Email: `50-contract-pending-signature` to both parties (now including committed terms recap)
6. Activity log both sides

---

### Step 4: Sign the Contract

**Page:** Hired tab → "Sign Contract" → `SignContractModal`
**Route:** `POST provider.jobs.contract.sign` · **Service:** `ContractService::sign()`

`SignContractModal` shows a **read-only committed terms panel** — structure summary + upfront amount + remaining amount + terms note text. Signing is signing to *these specific terms*.

**What happens:**
- Provider's `practitioner_signed_at` + `practitioner_signature_name` set
- If BP already signed:
  - `fully_executed_at = now()`
  - **`bp_contract_terms` snapshot row inserted** (immutable audit)
  - Contract status → `active`
  - **Structure-driven post-signing behavior fires immediately:**
    - `full_upfront` → `PayoutService::chargeContractUpfront($contract)` charges 100%
    - `split` → `chargeContractUpfront` charges X%
    - `per_milestone` / `on_completion` → no charge yet
  - `ContractSigned` event fires
- If BP has not yet signed → email BP prompting signature

The signing action is the payment-authorization action for the upfront portion. Terms recap in modal + agreement checkbox make this explicit.

---

### Step 5: (Rev 2 REMOVED — no separate funding step)

Rev 1 had a distinct "Fund escrow" step between signing and active. Rev 2 eliminates it. Signing the contract IS the authorization to charge the upfront portion. Providers who want to defer any charge until milestone approvals choose `per_milestone` structure.

---

### Step 6: Review Milestone Submissions

Unchanged in trigger, changed in effect. When BP submits work, provider sees a review notification.

**Action:** "Review milestone submission" → `MilestoneReviewModal`
**Route:** `POST provider.jobs.contract.milestones.review`

**Three decisions:**

#### 6a. Approve & Pay
**Route action:** `action = "approved"`
**Service:** `ContractService::approveMilestone()` → **`PayoutService::chargeProviderToBp()`** (destination charge, direct-to-BP)

**What happens (Rev 2):**
1. Milestone → `approved`
2. Immediately: `PayoutService::chargeProviderToBp($provider, $bp, $milestone->amount_cents, ..., ['contract_id', 'milestone_id', 'portion' => 'milestone'])`
3. On success: milestone → `paid`, `payment_intent_id` + `paid_at` + `paid_cents` set
4. `BpPayout` record created with the direct-charge PI
5. `contract.paid_cents += amount`
6. If all milestones paid → contract → `completed`
7. `MilestonePaid` event → email BP (`55-milestone-approved-and-paid`)
8. **NO ledger row in `bp_escrow_ledger`** — payment is a direct charge, not an escrow release

**Modal Rev 2 additions:** committed terms recap + agreement checkbox at bottom, mirroring the payment-authorization pattern.

#### 6b. Request Revision
Same as Rev 1. No payment involved.

#### 6c. Reject (Dispute Path)
Same as Rev 1. Provider does not self-adjudicate; opens `OpenDisputeModal`.

---

### Step 7: Open a Dispute

Unchanged mechanism. What changes: **the funds either haven't been charged yet (for pending/submitted milestones) or have already reached the BP's Connect account (for previously-paid milestones)**.

- Pending/submitted milestone dispute → admin can rule "no payment" (contract stops here) or "pay" (provider's card charged direct-to-BP)
- Previously-paid milestone dispute → admin can rule refund (`reverse_transfer: true` pulls funds back from BP's Connect balance)

Split resolutions: admin can rule "pay $X of remaining + refund $Y of already-paid" — two Stripe calls, one destination charge and one refund. Neither touches Aegis's platform balance.

---

### Step 8: Self-Service Cancel (Pre-Milestone-Charge)

**Rev 2 semantic:** self-service refund only makes sense if money was actually charged. For milestones that have NOT yet been approved (still `pending`, `in_progress`, `submitted`, `revision_requested`) — **no money to refund**. Provider can simply cancel the milestone.

**Route:** `POST provider.jobs.contract.milestones.cancel` (renamed from Rev 1 `.refund`)
**Service:** `ContractService::cancelMilestone()`
**Allowed statuses:** `pending`, `in_progress`, `submitted`, `revision_requested`

If the milestone was `paid`, this route rejects with "Milestone already paid — open a dispute to seek refund."

For `full_upfront` and `split` contracts where the upfront charge has already fired, **contract-level cancellation** can trigger a refund of `paid_cents` via `PayoutService::refundContractUpfront()` — calls Stripe with `reverse_transfer: true`.

---

### Step 9: Contract Completion & Review

**One-time contracts (`on_completion`):** Provider clicks "Mark Complete & Pay" → charges `remaining_cents` (= `total_value_cents`) → contract → `completed`.

**Split contracts:** When all milestones paid AND upfront charge already fired, contract auto-completes. For contracts where a completion charge is needed (rare — mostly for one-time + split), provider clicks "Mark Complete & Pay Remainder" → charges `remaining_cents`.

**Milestone contracts (`per_milestone`, `full_upfront`):** Auto-complete when last milestone reaches its terminal state (`paid` for per_milestone; `prepaid` for full_upfront).

**Review:** Same as Rev 1. Rating goes into `bp_contract_reviews`.

---

## 8. Business Partner Journey

### Step 1: Find Jobs
Unchanged. Each open job now shows a **terms chip** summarizing the provider's default payment structure.

### Step 2: Submit a Proposal — Rev 2

**Page:** `/business/find-jobs` → BP clicks "Apply" → opens `BpProposeModal` (formerly an inline form)

**Rev 2 modal contents:**
- Cover letter, bid amount, timeline days, portfolio URL (as Rev 1)
- **New: Payment Terms section (via `PaymentTermsInline` component):**
  - Radio: "Accept provider's terms" (default) vs "Propose different terms"
  - When proposing different: structure radio (with `on_completion` hidden if `!allow_on_completion`) + upfront % (if split) + terms note textarea (pre-filled with provider's default)
- **`agree_terms` checkbox:** "I agree these are the terms I'm proposing to work under. I understand payment routes directly from provider to me via Stripe Connect; Aegis is not the paymaster."

**Route:** `POST business.jobs.propose` · **Service:** `ProposalService::submit()`

**Form fields (Rev 2 additions):**
```
proposed_payment_structure     required|in:full_upfront,split,per_milestone,on_completion
proposed_upfront_percentage    required_if:proposed_payment_structure,split|integer|min:1|max:99
proposed_terms_note            nullable|string|max:5000
terms_source                   required|in:provider_default,bp_proposed
agree_terms                    required|accepted
```

Server-side guard: if `proposed_payment_structure = on_completion` and `bp_jobs.allow_on_completion = 0`, reject.

### Step 3: Track Proposal Status — Rev 2
Same as Rev 1. Proposal card shows proposed terms chip.

### Step 4: Sign the Contract — Rev 2
`SignContractModal` shows the committed terms (may differ from BP's proposal if provider countered). BP signs to those terms. If provider already signed → `fully_executed_at` set → `bp_contract_terms` snapshot row inserted → contract → `active` → upfront charge (if any) fires.

### Step 5: (Rev 2 REMOVED — no "await funding" state)

Rev 1 had BP waiting for provider to fund escrow. Rev 2: as soon as both parties sign, work can begin. For `per_milestone` contracts BP works on trust for the first milestone, then submits + gets paid + repeats. For `full_upfront` and `split` structures, BP is already paid at least partially before beginning work.

### Step 6: Submit Milestone Work — Rev 2

**Only for `per_milestone` and `split` contracts.** `full_upfront` contracts mark all milestones `prepaid` at signing; the BP still submits deliverables so provider has a record, but there's no payment gating.

**Route:** `POST business.milestones.submit`

**What happens:**
1. `BpMilestoneSubmission` audit row created
2. Milestone → `submitted`
3. `auto_approve_at = now() + MILESTONE_AUTO_APPROVE_DAYS` (default 7)
4. `MilestoneSubmitted` event → email provider (`54-milestone-submitted`)

The auto-approve semantic changes from Rev 1's auto-release: **if the provider doesn't act by `auto_approve_at`, the system fires the direct charge on the provider's card automatically.** Same deadline pressure as before; the money-movement mechanism is different.

### Step 7: Handle Review Outcome — Rev 2

#### If Approved
- Immediate destination charge to BP's Stripe Connect account
- Milestone → `paid`
- Funds appear in BP's Stripe Connect balance instantly (subject to Stripe's own hold policies at the account level)
- Email: `55-milestone-approved-and-paid`

#### If Revision Requested
Same as Rev 1.

#### If Auto-Approved
`MilestoneAutoApproveJob` fires:
- Direct charge fires on provider's card
- Milestone → `paid`
- Email both parties (`57-milestone-auto-approved`)
- If provider's card declines → milestone marked `payment_failed`, admin alert fires, provider gets urgent email to fix card

### Step 8: Dispute — Rev 2 semantic
Milestone shows "Under mediation — no funds have moved" (for pre-approval disputes) or "Under mediation — refund pending admin review" (for post-approval disputes).

### Step 9: Post-Contract Review
Unchanged.

---

## 9. Dual-Party Workflow Timeline

```
PROVIDER                              SYSTEM                              BP
────────                             ──────                              ──

1. POST /support-services            Job created (draft)
   (with default_payment_structure)
2. POST /support-services/{job}/status Job → open

                                                          3. GET /find-jobs (sees posting + terms chip)
                                                          4. POST /find-jobs/{job}/propose
                                                             (with proposed_payment_structure)
                                                             Proposal submitted
                                                             Email → BP (bp/32)

5. Reviews application               ActivityLog: proposal_received
6. Moves through kanban stages
7. POST .../accept (via HireModal    Contract created:
   with committed terms + optional     - payment_structure committed
   counter-terms)                      - upfront_cents computed
                                       - terms_agreed_at set
                                       → pending_signature
                                     Email → both (50)

8. POST .../contracts/{id}/sign      Provider signed
                                                          9. POST /contracts/{id}/sign
                                                             BP signed
                                     Both signed:
                                       - bp_contract_terms snapshot inserted
                                       - fully_executed_at set
                                       - contract → active
                                       - IF structure ∈ {full_upfront, split}:
                                           PayoutService::chargeContractUpfront
                                           direct destination charge fires
                                           BP receives upfront portion instantly
                                     Email → both (51)

                                                          10. POST /milestones/{id}/submit
                                                              (per_milestone + split only)
                                                              Submission saved
                                                              auto_approve_at set
                                     Email → provider (54)

11. Reviews submission
     a. Approves:                    Direct charge → BP Connect
        POST .../review              Milestone → paid
        action = "approved"          Email → BP (55)
                                                          Funds in Stripe Connect

     b. Requests revision:                                Email → BP (56)
        Milestone → revision_requested

     c. Timer fires:                 auto_approve_at passed
                                     Direct charge → BP Connect
                                     Email → both (57)

12. All milestones paid              Contract → completed
    OR                               Email → both (61)
    Provider clicks "Mark Complete"
    for on_completion contract

13. POST .../contracts/{id}/review   Rating written        14. POST /contracts/{id}/review
    Provider rates BP                                          BP rates provider
```

---

## 10. Payment Flows

### 10.1 The unified primitive: `PayoutService::chargeProviderToBp()`

Every direct charge in Support Services goes through this method. It:
1. Detects demo/stub mode
2. Guards provider PM + BP Connect account
3. Creates a Stripe PaymentIntent with `transfer_data.destination = bp.stripe_account_id`, `on_behalf_of = bp.stripe_account_id`, `confirm: true`
4. Metadata includes: `contract_id`, `milestone_id?`, `portion` (`upfront|milestone|completion`), `payment_structure`, `upfront_percentage`, `terms_source`

Returns `['stripe_payment_intent_id', 'stripe_transfer_id', 'status']`.

### 10.2 Structure-driven trigger points

| Trigger event | full_upfront | split | per_milestone | on_completion |
|---|---|---|---|---|
| Both parties signed | Charge 100% | Charge X% | (nothing) | (nothing) |
| Milestone approved | (nothing — already paid) | Charge each milestone portion | Charge milestone amount | (n/a — no milestones) |
| All milestones done | Contract complete | If remainder unpaid: charge remainder | Contract complete | (n/a) |
| Provider marks complete (one-time) | (n/a) | Charge remainder | (n/a) | Charge 100% |

For `split` on milestone contracts: Rev 2 treats the upfront X% as a lump charge at signing, then each milestone approval draws down against the remaining `remaining_cents`. Since milestone amounts add up to `total_value_cents`, the drawdown accounting is: for each approved milestone with amount M, charge `M` direct-to-BP as long as `contract.paid_cents + M ≤ total_value_cents`. Behind the scenes this is identical to `per_milestone` after the upfront charge; the split flag just changes the signing-time behavior.

### 10.3 Refund flows (unchanged mechanism, Rev 2 clarified)

| Scenario | Mechanism | Who triggers |
|---|---|---|
| Milestone not yet approved | `ContractService::cancelMilestone()` — no money to refund, mark milestone `cancelled` | Provider (self-serve) |
| Milestone already paid + dispute rules refund | `PayoutService::refundBpCharge($paymentIntentId, $cents, reverse_transfer: true)` | Admin (via dispute resolution) |
| Contract cancelled after upfront paid | `PayoutService::refundContractUpfront($contract)` — refunds `paid_cents` with `reverse_transfer: true` | Provider or BP |
| Contract cancelled before any charge | Just mark `cancelled` — no money to refund | Either party |
| Auto-approve fires but provider card declined | Milestone → `payment_failed`; admin/provider retries after updating PM | System + Provider |

All refunds use Stripe's `reverse_transfer: true` — pulls funds from BP Connect balance back to provider's card. Aegis's balance is never touched.

### 10.4 Charge failure handling

If `PayoutService::chargeProviderToBp` throws (`Card declined`, `BP has no Stripe account`, etc.):
- Milestone/contract remains in the pre-charge state (`approved` for milestone approval; `active` for upfront)
- `payment_failed_at` timestamp set on the milestone/contract
- Critical activity notification to provider
- Provider prompted to update PM in Settings → Billing, then retry via a "Retry payment" button

The provider CAN'T bypass a failed charge — the milestone or contract is stuck at the pre-charge state until the charge succeeds or an admin manually resolves.

---

## 11. Automated Jobs & Timers

### `MilestoneAutoApproveJob` (replaces `MilestoneAutoReleaseJob`)
- **Schedule:** Hourly
- **Trigger:** `milestone.status = 'submitted' AND auto_approve_at <= now()`
- **Action:**
  1. Fire `PayoutService::chargeProviderToBp($provider, $bp, $milestone->amount_cents, ...)` (direct destination charge)
  2. On success: milestone → `paid`
  3. On failure: milestone → `payment_failed`, admin alert
- **Events fired:** `MilestoneAutoApproved` (success) or `MilestoneAutoApproveFailed` (failure)
- **Emails:** `57-milestone-auto-approved` (success) or `58-milestone-auto-approve-failed` (failure)
- **Config:** `MILESTONE_AUTO_APPROVE_DAYS=7`

### `MilestoneReviewReminderJob` — unchanged
- Reminds provider to review submitted milestones before auto-approve fires
- Email: `59-milestone-review-reminder`

### Legacy `MilestoneAutoReleaseJob` — deprecated

Retained in codebase for legacy contracts that still have `escrow_intent_id` populated (Rev 1 escrow flow). New contracts never enter this job's query since `auto_release_at` is not set on new milestones.

---

## 12. Email Notifications Matrix

All emails gated by `notify_payment` or `notify_dispute` unless marked UNGATED. Every email that references money includes the direct-to-BP disclosure partial (§14 UI Language Standard).

| # | Template | Trigger Event | Recipient(s) | Rev 2 |
|---|---|---|---|---|
| 40 | `business/40-proposal-accepted` | `ProposalAccepted` | BP | Now includes committed terms recap |
| 50 | `business/50-contract-pending-signature` | `ContractCreated` | Provider + BP | Includes committed terms |
| 51 | `business/51-contract-fully-executed` | `ContractSigned` | Provider + BP | Removes "please fund escrow" line |
| 52 | ~~`business/52-escrow-funded`~~ → `business/52-contract-upfront-paid` | `ContractUpfrontCharged` | BP | Was: escrow funded. Now: BP received upfront direct-to-account |
| 53 | ~~`business/53-milestone-funded`~~ → deleted | — | — | No longer a concept |
| 54 | `business/54-milestone-submitted` | `MilestoneSubmitted` | Provider | Now: "review or auto-charge fires in N days" |
| 55 | ~~`business/55-milestone-approved`~~ → `business/55-milestone-approved-and-paid` | `MilestonePaid` | BP | Now: "approved and paid direct-to-your-account" |
| 56 | `business/56-milestone-revision-requested` | `MilestoneRevisionRequested` | BP | Unchanged |
| 57 | ~~`business/57-milestone-auto-released`~~ → `business/57-milestone-auto-approved` | `MilestoneAutoApproved` | Provider + BP | Now: "auto-charged your card" (provider) / "auto-paid to your account" (BP) |
| 58 | `business/58-milestone-auto-approve-failed` | `MilestoneAutoApproveFailed` | Provider (Critical) | **NEW** — card declined during auto-approve |
| 59 | `business/59-milestone-review-reminder` | `MilestoneReviewReminderJob` | Provider | Was 58 in Rev 1 — bumped |
| 60 | `business/60-contract-cancelled` | `ContractCancelled` | Provider + BP | Includes refund status if applicable |
| 61 | `business/61-contract-completed` | `ContractCompleted` | Provider + BP | Unchanged |
| bp/32 | `bp/32-support-request-received` | `ProposalSubmitted` | BP (confirmation) | Includes proposed terms recap |
| bp/34 | `bp/34-proposal-declined` | `ProposalDeclined` | BP | Unchanged |
| d/70 | `disputes/70-opened` | `DisputeOpened` | Respondent | References `bp_contract_terms` snapshot |
| d/71 | `disputes/71-replied` | `DisputeReplied` | Counterparty | Unchanged |
| d/72 | `disputes/72-resolved` | `DisputeResolved` | Provider + BP | Includes any refund/charge that occurred |

Deprecated templates 52, 53, 55, 57 kept as thin aliases pointing to new names for one release cycle.

---

## 13. Activity Log Events (Portal Notifications)

All written via `ActivityService::log()` with `module = 'job_postings'`.

Rev 2 action rename map:
| Rev 1 action | Rev 2 action |
|---|---|
| `escrow_funded` | `contract_upfront_paid` (or removed for per_milestone contracts) |
| `milestone_funded` | *(removed — no such event)* |
| `milestone_released` | `milestone_paid` |
| `milestone_auto_released` | `milestone_auto_approved` |
| `escrow_refunded` | `contract_upfront_refunded` |

New actions:
- `contract_terms_committed` (fires when Hire creates contract with committed terms — actor: provider, notify: BP)
- `milestone_payment_failed` (fires when direct charge declines — Critical severity, actor: system, notify: provider)

Every log entry now includes `data.payment_structure` + `data.upfront_percentage` in the JSON payload for downstream reporting.

Every write still produces two records: `entry_type = 'log'` for actor, `entry_type = 'notification'` for counterparty.

---

## 14. API Route Reference

### Provider Portal (Rev 2 changes marked)
| Method | Path | Route Name | Rev 2 |
|---|---|---|---|
| GET | `/support-services` | `jobs.index` | Now includes `default_payment_structure` in job data |
| POST | `/support-services` | `jobs.store` | Accepts new default_* fields |
| PUT | `/support-services/{job}` | `jobs.update` | Same |
| POST | `/support-services/{job}/status` | `jobs.status` | Unchanged |
| DELETE | `/support-services/{job}` | `jobs.destroy` | Unchanged |
| POST | `/support-services/{job}/proposals/{proposal}/accept` | `jobs.proposal.accept` | Accepts committed_* fields via updated FormRequest |
| POST | `/support-services/{job}/proposals/{proposal}/decline` | `jobs.proposal.decline` | Unchanged |
| POST | `/support-services/{job}/proposals/{proposal}/stage` | `jobs.proposal.stage` | Unchanged |
| POST | `/support-services/{job}/proposals/{proposal}/notes` | `jobs.proposal.notes` | Unchanged |
| POST | `/support-services/contracts/{contract}/sign` | `jobs.contract.sign` | On both-signed: triggers upfront charge (if applicable) |
| ~~POST~~ | ~~`/support-services/contracts/{contract}/fund`~~ | ~~`jobs.contract.fund`~~ | **REMOVED** — no funding step |
| POST | `/support-services/contracts/{contract}/cancel` | `jobs.contract.cancel` | Now also handles upfront refund if paid |
| POST | `/support-services/contracts/{contract}/complete` | `jobs.contract.complete` | **NEW** — for `on_completion` one-time contracts + split contracts needing remainder charge |
| POST | `/support-services/contracts/{contract}/milestones` | `jobs.contract.milestones.store` | Unchanged |
| PUT | `/support-services/contracts/{contract}/milestones/{milestone}` | `jobs.contract.milestones.update` | Unchanged |
| DELETE | `/support-services/contracts/{contract}/milestones/{milestone}` | `jobs.contract.milestones.destroy` | Unchanged |
| ~~POST~~ | ~~`/support-services/contracts/{contract}/milestones/{milestone}/fund`~~ | ~~`jobs.contract.milestones.fund`~~ | **REMOVED** |
| POST | `/support-services/contracts/{contract}/milestones/{milestone}/review` | `jobs.contract.milestones.review` | Approve now fires direct charge synchronously |
| POST | `/support-services/contracts/{contract}/milestones/{milestone}/approve` | `jobs.contract.milestones.approve` | Same |
| POST | `/support-services/contracts/{contract}/milestones/{milestone}/request-revision` | `jobs.contract.milestones.revision` | Unchanged |
| ~~POST~~ | ~~`/support-services/contracts/{contract}/milestones/{milestone}/pay`~~ | ~~`jobs.contract.milestones.pay`~~ | **DEPRECATED** — direct charge now fires from approve action |
| POST | `/support-services/contracts/{contract}/milestones/{milestone}/cancel` | `jobs.contract.milestones.cancel` | **RENAMED** from `.refund` |
| POST | `/support-services/contracts/{contract}/milestones/{milestone}/retry-payment` | `jobs.contract.milestones.retry-payment` | **NEW** — retry after payment_failed |
| GET | `/support-services/contracts/{contract}/pdf` | `jobs.contract.pdf` | Includes committed terms |
| POST | `/support-services/contracts/{contract}/review` | `jobs.contract.review` | Unchanged |
| POST | `/support-services/contracts/{contract}/review/dismiss` | `jobs.contract.review.dismiss` | Unchanged |

### BP Portal (Rev 2 changes marked)
| Method | Path | Route Name | Rev 2 |
|---|---|---|---|
| GET | `/find-jobs` | `jobs.index` | Now includes `default_payment_structure` for each posting |
| POST | `/find-jobs/{job}/save` | `jobs.save` | Unchanged |
| POST | `/find-jobs/{job}/propose` | `jobs.propose` | Accepts proposed_* payment terms |
| GET | `/proposals` | `proposals.index` | Unchanged |
| DELETE | `/proposals/{proposal}` | `proposals.withdraw` | Unchanged |
| GET | `/contracts` | `contracts.index` | Includes committed terms per contract |
| POST | `/contracts/{contract}/sign` | `contracts.sign` | On both-signed: triggers upfront charge on provider's card |
| POST | `/contracts/{contract}/cancel` | `contracts.cancel` | Same as provider — triggers refund if applicable |
| GET | `/contracts/{contract}/pdf` | `contracts.pdf` | Includes committed terms |
| POST | `/contracts/{contract}/review` | `contracts.review` | Unchanged |
| GET | `/milestones` | `milestones.index` | Removes "awaiting funding" states |
| POST | `/milestones/{milestone}/submit` | `milestones.submit` | Unchanged |

---

## 15. Environment Configuration

```env
# ── Rev 2 renamed ──────────────────────────────────────
# Days after milestone submission before auto-approve+charge fires
MILESTONE_AUTO_APPROVE_DAYS=7
# (Was MILESTONE_AUTO_RELEASE_DAYS in Rev 1 — kept as alias)
MILESTONE_AUTO_RELEASE_DAYS=7

# Hours before auto-approve to send reminder email
MILESTONE_REVIEW_REMINDER_HOURS=48

# ── Dispute (unchanged) ────────────────────────────────
DISPUTE_RESPONDENT_REPLY_DAYS=5

# Stripe sandbox keys (unchanged)
STRIPE_KEY=pk_test_51OCuB1Hnj73y5cBf...
STRIPE_SECRET=sk_test_51OCuB1Hnj73y5cBf...

# Tier limits (unchanged — not Support Services)
TIER_ACCESS_MAX_CS=2
TIER_PRACTICE_MAX_CS=2
```

Removed (no longer needed): none of the escrow-specific env vars existed as such — timing was tracked per-milestone via `auto_release_at`.

---

## 16. Demo Users

Unchanged from Rev 1. All demo passwords: `Demo1234!`

| Username | Email | Notes |
|---|---|---|
| `p_sarah` | `sarah_johnson@demo.aegis` | Practice tier; real Stripe sub; services mode ON |
| `p_maria` | `maria@demo.aegis` | Practice tier; has active contract with Acme Health |
| `bp_acme` | `contact@acmehealth.demo.aegis` | Agency; active contract with p_maria |
| `bp_jamal` | `jamal@demo.aegis` | Freelancer; pending proposal, submitted milestone |
| `bp_team_owner` | `contact@nexus.demo.aegis` | Agency; team with members |
| `bp_team_member` | `tanya@demo.aegis` | Freelancer; member of Nexus team |

Rev 2 demo data adds: each existing contract gets committed terms backfilled to `payment_structure=per_milestone`, `upfront_percentage=0`, `terms_source=provider_default` — matches historical behavior.

---

## Appendix A — Migration From Rev 1

### Existing production data
- All existing `bp_contracts` rows have `escrow_funded_cents` / `escrow_released_cents` populated per Rev 1 escrow flow.
- After Rev 2 migration runs: their `payment_structure = 'per_milestone'` (or `full_upfront` if `funding_mode='full_upfront'`), `upfront_percentage = 0`, `paid_cents = escrow_released_cents`, `terms_source = 'provider_default'`.
- `bp_milestones` rows with `status='released'` or `status='paid'` get `payment_intent_id = escrow_intent_id`, `paid_at = COALESCE(released_at, updated_at)`, `paid_cents = released_cents`.
- `bp_contract_terms` snapshot rows created for every `fully_executed` contract using the values above.

### Backfill migration file
`2026_07_21_000015_backfill_support_services_terms.php` — idempotent one-shot.

### Legacy escrow contracts — dual-path behavior
Contracts created before Rev 2 rollout that still have money sitting in Aegis's platform balance (extremely rare — most Rev 1 flows were direct-charge via `PayoutService::payMilestone` already):
- Legacy `EscrowService::releaseMilestone` remains callable for those specific `contract_id`s
- Admin dashboard flags any contract with `escrow_funded_cents > escrow_released_cents + escrow_refunded_cents` as "legacy escrow requiring reconciliation"
- Once reconciled (funds all released or refunded), those contracts are marked `legacy_reconciled` and read-only

### Route + event aliases (one release cycle)
- `POST .../contracts/{id}/fund` → returns 410 Gone with body `{"message": "Escrow funding is deprecated. Contracts now charge on signing or per-milestone approval based on committed terms."}`
- `POST .../milestones/{id}/fund` → same
- `EscrowFunded` / `ContractFullyFunded` events fire only from legacy-reconciliation admin actions, not from new flows
- Old blade templates (52-escrow-funded, 53-milestone-funded, 55-milestone-approved, 57-milestone-auto-released) → thin aliases redirecting to new names

### Copy audit checklist
Grep these patterns across Vue + Blade + PHP under Support Services scope:
```
"escrow"          — must not appear in Support Services after Rev 2
"held"            — replace with "routed" or "paid directly"
"in trust"        — must not appear
"funds released"  — replace with "payment sent"
"Fund escrow"     — remove or replace with "Sign to authorize payment"
"Fund milestone"  — remove (concept deleted)
"Aegis balance"   — must not appear anywhere
```

Grep scope:
```
resources/js/pages/provider/SupportServices.vue
resources/js/pages/provider/Finances.vue           (BP tab only)
resources/js/pages/business-partner/*.vue
resources/js/pages/public/BusinessProfile.vue
resources/js/components/ui/BpFinanceTable.vue
resources/js/components/ui/BpContractRow.vue
resources/js/components/ui/BpMilestoneRow.vue
resources/js/components/modals/HireModal.vue
resources/js/components/modals/BpProposeModal.vue          (new/renamed from inline form)
resources/js/components/modals/ContractModal.vue
resources/js/components/modals/SignContractModal.vue
resources/js/components/modals/BpContractDetailModal.vue
resources/js/components/modals/MilestoneSubmitModal.vue
resources/js/components/modals/MilestoneReviewModal.vue
resources/js/components/modals/MilestoneRefundModal.vue    (rename → MilestoneCancelModal.vue)
resources/js/components/modals/FundContractModal.vue       (DELETE)
resources/js/components/modals/FundMilestoneModal.vue      (DELETE)
resources/views/emails/business/*.blade.php
resources/views/emails/bp/*.blade.php
app/Services/EscrowService.php     (mark deprecated + comment)
app/Services/ContractService.php   (rewrite approve flow)
app/Services/ProposalService.php   (accept new proposed_* fields)
app/Services/BpJobService.php      (accept new default_* fields)
app/Services/PayoutService.php     (add chargeContractUpfront + refundContractUpfront)
```

Should return zero hits for banned words in the Support Services scope after Rev 2 cutover.

---

*Rev 2 — validated against live repo `main @ 65d3f47` on 2026-07-21. Supersedes Rev 1 (July 2026).*
*Companion doc: `SUPPORT_SERVICES_TERMS_TECHNICAL_PLAN.md` — execution roadmap for the Rev 1 → Rev 2 conversion.*
*For architecture principle see also `AEGIS_PAYMENTS_FINANCE.md` §1.*
