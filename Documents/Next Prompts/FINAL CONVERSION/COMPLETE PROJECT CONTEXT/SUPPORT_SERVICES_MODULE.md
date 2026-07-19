# Aegis — Support Services Module
## Complete Workflow Reference · Both Portals · Rev 1 · July 2026

---

## Table of Contents

1. [Overview](#1-overview)
2. [Architecture & Stripe Pattern](#2-architecture--stripe-pattern)
3. [Data Model](#3-data-model)
4. [Status State Machines](#4-status-state-machines)
5. [Provider Journey — Step by Step](#5-provider-journey--step-by-step)
6. [Business Partner Journey — Step by Step](#6-business-partner-journey--step-by-step)
7. [Dual-Party Workflow Timeline](#7-dual-party-workflow-timeline)
8. [Payments & Escrow Flows](#8-payments--escrow-flows)
9. [Automated Jobs & Timers](#9-automated-jobs--timers)
10. [Email Notifications Matrix](#10-email-notifications-matrix)
11. [Activity Log Events (Portal Notifications)](#11-activity-log-events-portal-notifications)
12. [API Route Reference](#12-api-route-reference)
13. [Environment Configuration](#13-environment-configuration)
14. [Demo Users](#14-demo-users)

---

## 1. Overview

**Support Services** is the marketplace module connecting Practitioners to Business Partners (BPs) for non-clinical practice support — billing, compliance, IT, marketing, legal, and operations.

Unlike Clinical Services (client-facing sessions), Support Services uses an **escrow-backed milestone payment model** where Aegis holds funds in trust until the Practitioner approves a BP's deliverable. Neither party can be defrauded: the Practitioner cannot withhold payment indefinitely (auto-release timer), and the BP cannot receive funds until work is verified (escrow gate).

### Portals Involved

| Role | Portal | URL Prefix | Entry Page |
|---|---|---|---|
| Practitioner | Provider Portal | `/provider` | `/provider/support-services` |
| Business Partner | BP Portal | `/business` | `/business/find-jobs` |

### Key Invariants

- Money is **always stored in cents** (`total_value_cents`, `amount_cents`, `funded_cents`, etc.)
- Aegis **holds funds** — not the provider, not the BP — until milestone approval
- Demo entities detected by `cus_demo_*` / `pm_demo_*` / `acct_demo_*` prefix → Stripe calls stubbed
- Contract FK on invoices: `practitioner_id` (never `provider_id`)
- BP FK: `bp_id` · CS FK: `cs_id`

---

## 2. Architecture & Stripe Pattern

### Escrow via Separate Charges + Transfers

Support Services uses Stripe's **separate charges and transfers** pattern — the only Stripe model supporting indefinite fund holds (manual capture expires in 7 days, making it unsuitable for month-long milestones).

```
FUND:     Provider card → Stripe PaymentIntent (no transfer_data.destination)
                                    ↓
                          Aegis platform balance  [ESCROW HELD]
                          Tagged: transfer_group = "aegis_contract_{id}"

RELEASE:  Aegis platform balance → transfers.create(destination: bp.stripe_account_id)
                                    ↓
                          BP Stripe Connect balance

REFUND:   Stripe refunds.create(payment_intent: original_intent_id)
                                    ↓
                          Provider payment method
```

### Stripe Object Chain

```
PaymentIntent (pi_xxx)          — created per milestone fund OR per full contract
  ↓ on approve
Transfer (tr_xxx)               — from Aegis balance to BP Connect
  ↓ if refund needed
Refund (re_xxx)                 — against original PaymentIntent
```

All objects share the same `transfer_group = "aegis_contract_{contract_id}"` for Stripe-level correlation. Every operation writes an immutable row to `bp_escrow_ledger`.

### Services Involved

| Service | Responsibility |
|---|---|
| `EscrowService` | All Stripe calls: fund, release, refund, split resolution |
| `ContractService` | Contract lifecycle: sign, cancel, complete, milestone approve/revision |
| `ProposalService` | Proposal submit, accept (→ creates contract), decline, withdraw |
| `BpJobService` | Job posting CRUD, status management |
| `PayoutService` | One-time payment contracts (non-escrow legacy path) |
| `ContractReviewService` | Post-completion ratings, aggregate score computation |
| `DisputeService` | Opens disputes, freezes escrow, resolves with split/release/refund |

---

## 3. Data Model

### Core Tables

#### `bp_jobs` — Job Postings

| Column | Type | Notes |
|---|---|---|
| `id` | `char(36)` | Prefixed `bj_xxx` |
| `practitioner_id` | `char(36)` | FK → `users.id` |
| `title` | `varchar(191)` | |
| `category` | `varchar(50)` | billing, compliance, it, marketing, legal, operations, other |
| `description` | `text` | |
| `status` | `enum` | `draft \| open \| paused \| closed \| filled \| cancelled` |
| `payment_type` | `enum` | `one_time \| milestone` |
| `funding_mode` | `enum` | `full_upfront \| per_milestone` |
| `budget_min_cents` | `int` | |
| `budget_max_cents` | `int` | |
| `posted_at` | `timestamp` | |

#### `bp_proposals` — Applications

| Column | Type | Notes |
|---|---|---|
| `id` | `char(36)` | Prefixed `bpr_xxx` |
| `job_id` | `char(36)` | |
| `bp_id` | `char(36)` | FK → `users.id` |
| `cover_letter` | `text` | |
| `proposed_rate_cents` | `int` | |
| `payment_type` | `enum` | `one_time \| milestone` |
| `status` | `enum` | `pending \| submitted \| accepted \| declined \| withdrawn` |
| `pipeline_stage` | `varchar` | `new \| reviewed \| shortlisted \| interview \| hired \| rejected` |
| `internal_notes` | `text` | Provider-only private notes |
| `timeline_days` | `int` | |
| `portfolio_url` | `varchar(500)` | |

#### `bp_contracts` — Active Agreements

| Column | Type | Notes |
|---|---|---|
| `id` | `char(36)` | Prefixed `bc_xxx` |
| `job_id` | `char(36)` | |
| `proposal_id` | `char(36)` | |
| `practitioner_id` | `char(36)` | FK → `users.id` |
| `bp_id` | `char(36)` | FK → `users.id` |
| `title` | `varchar(191)` | |
| `status` | `enum` | `draft \| pending_signature \| pending_funding \| active \| completed \| cancelled \| disputed` |
| `total_value_cents` | `int` | |
| `payment_type` | `enum` | `one_time \| milestone` |
| `funding_mode` | `enum` | `full_upfront \| per_milestone` |
| `escrow_funded_cents` | `int` | Running total funded |
| `escrow_released_cents` | `int` | Running total transferred to BP |
| `escrow_refunded_cents` | `int` | Running total refunded to provider |
| `transfer_group` | `varchar(64)` | Stripe correlation ID `aegis_contract_{id}` |
| `practitioner_signed_at` | `timestamp` | |
| `practitioner_signature_name` | `varchar(120)` | |
| `bp_signed_at` | `timestamp` | |
| `bp_signature_name` | `varchar(120)` | |
| `fully_executed_at` | `timestamp` | Set when both parties have signed |
| `terms` | `text` | Contract body text |

#### `bp_milestones` — Deliverable Units

| Column | Type | Notes |
|---|---|---|
| `id` | `char(36)` | Prefixed `bm_xxx` |
| `contract_id` | `char(36)` | |
| `title` | `varchar(191)` | |
| `description` | `text` | |
| `amount_cents` | `int` | |
| `status` | `enum` | See §4 |
| `due_at` | `timestamp` | |
| `submitted_at` | `timestamp` | |
| `approved_at` | `timestamp` | |
| `auto_release_at` | `timestamp` | Set on submit; auto-release fires when past |
| `revision_count` | `tinyint` | Increments on each revision request |
| `revision_notes` | `text` | Latest revision feedback from provider |
| `escrow_intent_id` | `varchar(64)` | Stripe PaymentIntent ID |
| `escrow_charge_id` | `varchar(64)` | Stripe Charge ID |
| `transfer_id` | `varchar(64)` | Stripe Transfer ID (set on release) |
| `funded_cents` | `int` | |
| `released_cents` | `int` | |
| `refunded_cents` | `int` | |
| `refund_stripe_id` | `varchar(64)` | |
| `sort_order` | `int` | |

#### `bp_milestone_submissions` — Submission Audit Trail

Each BP submit (including resubmits after revision) writes a new immutable row.

| Column | Type | Notes |
|---|---|---|
| `id` | `char(36)` | Prefixed `bms_xxx` |
| `milestone_id` | `char(36)` | |
| `submitted_by` | `char(36)` | BP user ID |
| `submission_notes` | `text` | Required work summary |
| `attachments` | `json` | `[{filename, path, size}]` |
| `hours_logged` | `decimal(6,2)` | Optional |
| `reviewed_by` | `char(36)` | Provider who reviewed |
| `reviewed_at` | `timestamp` | |
| `review_action` | `enum` | `approved \| revision \| rejected` |
| `review_notes` | `text` | |

#### `bp_escrow_ledger` — Immutable Money Audit Trail

Never updated — only inserted. Every Stripe operation writes one row.

| Column | Type | Notes |
|---|---|---|
| `kind` | `enum` | `fund \| release \| refund \| dispute_hold \| dispute_release \| dispute_refund \| split_release \| split_refund` |
| `amount_cents` | `int` | |
| `stripe_object_id` | `varchar(64)` | `pi_xxx \| tr_xxx \| re_xxx` |
| `stripe_object_type` | `varchar(20)` | `payment_intent \| transfer \| refund` |
| `actor_id` | `char(36)` | Who triggered |

#### `bp_contract_reviews` — Post-Completion Ratings

| Column | Type | Notes |
|---|---|---|
| `reviewer_id` | `char(36)` | |
| `reviewee_id` | `char(36)` | |
| `rating` | `tinyint` | 1–5 overall |
| `communication` | `tinyint` | 1–5 |
| `quality` | `tinyint` | 1–5 |
| `timeliness` | `tinyint` | 1–5 |
| `review_text` | `text` | |
| `is_public` | `boolean` | Shown on public profile |
| `review_dismissed` | `boolean` | User skipped without reviewing |
| `UNIQUE` | | `(contract_id, reviewer_id)` — one review per party per contract |

---

## 4. Status State Machines

### Job Posting (`bp_jobs.status`)

```
draft ──────────────────→ open ──→ paused ──→ open (resume)
                           │
                           ├──→ filled (after hire)
                           ├──→ closed (manual)
                           └──→ cancelled
```

### Proposal (`bp_proposals.status`)

```
submitted → pending → accepted (→ contract created in pending_signature)
                    → declined
                    → withdrawn (BP pulls proposal)
```

**Pipeline stages** (visible in provider kanban, independent of `status`):

```
new → reviewed → shortlisted → interview → hired / rejected
```

### Contract (`bp_contracts.status`)

```
draft
  └──→ pending_signature  (after ProposalService::accept())
         └──→ pending_funding  (after both parties sign)
                └──→ active  (after escrow funded)
                       ├──→ completed  (all milestones released)
                       ├──→ cancelled  (→ triggers full refund of unreleased escrow)
                       └──→ disputed   (dispute opened on a milestone)
```

### Milestone (`bp_milestones.status`)

```
pending / pending_funding
  └──→ funded  (escrow charged to provider's card)
         └──→ in_progress  (work underway — same escrow state)
                └──→ submitted  (BP submits work; auto_release_at set)
                       ├──→ approved  (provider approves)
                       │      └──→ released / paid  (Stripe transfer sent)
                       ├──→ revision_requested  (provider sends back; revision_count++)
                       │      └──→ submitted  (BP resubmits)
                       └──→ disputed  (escrow frozen; admin mediates)
                              ├──→ released  (resolved in BP's favour)
                              └──→ refunded  (resolved in provider's favour)
  └──→ refunded  (provider self-refund before submission)
```

---

## 5. Provider Journey — Step by Step

### Step 1: Post a Support Request

**Page:** `/provider/support-services` → My Postings tab

**Action:** Click "Request Support" → opens `PostJobModal`

**Fields required:**
- Title, category (billing / compliance / IT / marketing / legal / operations / other)
- Description, budget range, location preference
- `payment_type`: `one_time` or `milestone`
- `funding_mode`: `full_upfront` or `per_milestone`

**Route:** `POST provider.jobs.store`  
**Service:** `BpJobService::create()`  
**Result:** Job created in `draft` status

**Publish:** Click "Publish" on a draft → `POST provider.jobs.status` → status → `open`

**Manage after publish:** Pause, resume, use templates, edit description.

---

### Step 2: Receive & Review Applications

**Page:** `/provider/support-services` → Applications tab

BPs who apply appear here with their bid, BP type, and current pipeline stage.

**Kanban stages:**

| Stage | Action | Route |
|---|---|---|
| `new` | Automatic on submission | — |
| `reviewed` | "Mark Reviewed" button | `POST provider.jobs.proposal.stage` |
| `shortlisted` | "Shortlist" button | `POST provider.jobs.proposal.stage` |
| `interview` | "Schedule Interview" | `POST provider.jobs.proposal.stage` |

**Private notes:** Provider can add internal notes per applicant (never shown to BP). Saved via `POST provider.jobs.proposal.notes`.

**ApplicantProfileModal** shows: cover letter, proposed rate, standard rate, jobs completed on Aegis, applied date.

---

### Step 3: Hire a Business Partner

**Action:** Click "Hire This Person" in `ApplicantProfileModal` → opens `HireModal`

**Route:** `POST provider.jobs.proposal.accept`  
**Service:** `ProposalService::accept()`

**What happens:**
1. Proposal status → `accepted`
2. All other proposals on same job → `declined` (automatically)
3. `BpContract` created in `pending_signature` status
4. `ContractCreated` event fired → email to both parties (template `50-contract-pending-signature`)
5. Activity log written for both provider and BP

---

### Step 4: Sign the Contract

**Page:** Hired tab → Contract card shows "Pending Signature" badge

**Action:** "Sign Contract" → opens `SignContractModal`

**Fields:** Signature name (pre-filled from display_name)

**Route:** `POST provider.jobs.contract.sign`  
**Service:** `ContractService::sign()`

**What happens:**
- Provider's `practitioner_signed_at` + `practitioner_signature_name` set
- If BP has already signed: `fully_executed_at` set, `ContractSigned` event fired
- If BP has not yet signed: email sent to BP prompting signature

**Contract becomes `pending_funding`** when `fully_executed_at` is set.

---

### Step 5: Fund Escrow

**Contract status must be:** `pending_funding` or `active`  
**Prerequisite:** Provider must have a saved payment method (`users.stripe_payment_method_id`)

#### Option A — Full Contract Upfront (`funding_mode = full_upfront`)

**Action:** "Fund escrow" button → opens `FundContractModal`

**Route:** `POST provider.jobs.contract.fund`  
**Service:** `EscrowService::fundContract()`

**What happens:**
1. `paymentIntents.create()` for `total_value_cents` — no `transfer_data` (pure escrow hold)
2. All milestones → `funded`
3. Contract → `active`
4. `EscrowFunded` + `ContractFullyFunded` events fired → emails to BP

#### Option B — Per Milestone (`funding_mode = per_milestone`)

**Action:** Per milestone row in ContractModal → "Fund" button → opens `FundMilestoneModal`

**Route:** `POST provider.jobs.contract.milestones.fund`  
**Service:** `EscrowService::fundMilestone()`

**What happens:**
1. `paymentIntents.create()` for `milestone.amount_cents` only
2. That milestone → `funded`
3. If contract was `pending_funding` → contract → `active`
4. `EscrowFunded` event fired → email to BP (`53-milestone-funded`)

**Modal shows:** Milestone title, amount, due date, escrow disclosure, saved card last 4 digits.

---

### Step 6: Review Milestone Submissions

When a BP submits work, the provider sees a notification and a "Review" button in ContractModal.

**Action:** "Review milestone submission" → opens `MilestoneReviewModal`

**Route:** `POST provider.jobs.contract.milestones.review`

**Three decisions:**

#### 6a. Approve & Release Payment

**Route action:** `action = "approved"`  
**Service calls:** `ContractService::approveMilestone()` → `EscrowService::releaseMilestone()`

**What happens:**
1. `transfers.create(destination: bp.stripe_account_id)` from Aegis balance
2. Milestone → `released`
3. `BpPayout` record written
4. `bp_escrow_ledger` row: `kind = 'release'`
5. `MilestoneReleased` event → email to BP (`55-milestone-approved`)
6. Activity notification to BP

#### 6b. Request Revision

**Route action:** `action = "revision_requested"` + `notes` (required, min 10 chars)  
**Service:** `ContractService::requestRevision()`

**What happens:**
1. Milestone → `revision_requested`
2. `revision_count` incremented
3. `revision_notes` saved (visible to BP)
4. Latest `BpMilestoneSubmission` marked `review_action = 'revision'`
5. `MilestoneRevisionRequested` event → email to BP (`56-milestone-revision-requested`)
6. BP may resubmit (milestone → `submitted` again)

**Separate route for revision only:** `POST provider.jobs.contract.milestones.request-revision`

#### 6c. Reject (Dispute Path)

**Route action:** `action = "rejected"` → UI opens `OpenDisputeModal` instead of submitting  
Rejection is always a dispute — provider does not self-adjudicate.

---

### Step 7: Open a Dispute (if needed)

**Available from:** `OpenDisputeModal` with `subject_type = 'bp_milestone'`  
**Service:** `DisputeService::open()`

**What happens:**
1. Milestone → `disputed`
2. Escrow frozen (no release or refund until resolved)
3. Admin dispute queue populated
4. `DisputeOpened` event → email to BP respondent (`disputes/70-opened`)

**Admin resolution options:**
- `release_to_bp` → `EscrowService::releaseMilestone()`
- `refund_to_provider` → `EscrowService::refundMilestone(full)`
- `split` → `EscrowService::splitResolution($releaseCents, $refundCents)`

---

### Step 8: Self-Service Refund (Pre-Submission)

If a milestone has been funded but the BP has NOT yet submitted work, the provider can refund without a dispute.

**Route:** `POST provider.jobs.contract.milestones.refund`  
**Service:** `EscrowService::refundMilestone()`  
**Allowed statuses:** `pending`, `funded`, `in_progress`

**What happens:**
1. `refunds.create(payment_intent: milestone.escrow_intent_id)` in Stripe
2. Milestone → `refunded`
3. `bp_escrow_ledger` row: `kind = 'refund'`
4. `MilestoneRefunded` event → emails to both parties (`59-milestone-refunded`)

---

### Step 9: Contract Completion & Review

When all milestones reach `released` / `paid`:

**Service:** `ContractService::complete()` → contract → `completed`  
**Event:** `ContractCompleted` → email to both parties with review prompt (`61-contract-completed`)

**Review:** Provider can rate the BP (1–5 stars overall + communication, quality, timeliness)  
**Route:** `POST provider.jobs.contract.review`  
**Service:** `ContractReviewService::create()` → writes to `bp_contract_reviews`, updates BP's aggregate rating in `user_meta.bp_avg_rating`

---

## 6. Business Partner Journey — Step by Step

### Step 1: Find Jobs

**Page:** `/business/find-jobs`  
**Controller:** `BusinessPartner\JobsController::index()`

Shows all `open` job postings across the platform, filtered by category, budget, and location preference.

**Save a job for later:** `POST business.jobs.save`

---

### Step 2: Submit a Proposal

**Action:** Click "Apply" → form on Find Jobs page

**Route:** `POST business.jobs.propose`  
**Service:** `ProposalService::submit()`

**Fields:**
- Cover letter (required)
- Bid amount (`proposed_rate_cents`)
- Payment type preference (`one_time` or `milestone`)
- Timeline days
- Portfolio URL (optional)

**Guards:**
- Job must be `open`
- BP cannot have an existing proposal on the same job
- BP role validation

**Result:** Proposal created with status `pending`, pipeline stage `new`  
**Email:** `32-support-request-received` → BP confirmation  
**Activity log:** Provider notified ("New application received")

---

### Step 3: Track Proposal Status

**Page:** `/business/proposals`

Shows all proposals with current status and pipeline stage. BP can see:

- `new` → submitted, awaiting provider review
- `reviewed` → provider opened the profile
- `shortlisted` → provider is interested
- `interview` → interview scheduled
- `accepted` / `declined` → terminal states

**Withdraw:** `DELETE business.proposals.withdraw` → `ProposalService::withdraw()`  
Allowed while status is `pending` (not yet accepted/declined).

---

### Step 4: Sign the Contract

When a proposal is accepted, a contract is created in `pending_signature`.

**Page:** `/business/contracts`

**Action:** "Sign contract" on pending-signature contracts → `SignContractModal`

**Route:** `POST business.contracts.sign`  
**Service:** `ContractService::sign()`

**What happens:**
- BP's `bp_signed_at` + `bp_signature_name` set
- If provider already signed: `fully_executed_at` set → contract → `pending_funding`
- Email sent to provider ("Both parties signed — please fund escrow")

---

### Step 5: Await Funding

Contract shows "Awaiting funding by provider" until the provider charges their card.

Once funded:
- Per-milestone: individual milestones flip to `funded` as provider funds them
- Full-upfront: all milestones immediately `funded`
- Email received: `52-escrow-funded` or `53-milestone-funded`
- Activity notification: "Payment is secured in escrow. You may begin work."

**BP cannot begin work on unfunded milestones** — the UI shows a clear "🔒 Awaiting funding by provider" state.

---

### Step 6: Submit Milestone Work

**Page:** `/business/milestones`

Shows all milestones grouped by contract with current escrow state.

**Allowed submission statuses:** `funded`, `in_progress`, `revision_requested`

**Action:** "Submit milestone" → `MilestoneSubmitModal`

**Route:** `POST business.milestones.submit`  
**Controller:** `BusinessPartner\MilestonesController::submit()`

**Required fields:**
- `notes` — work summary (min 10, max 2000 chars)
- `hours_logged` — optional

**What happens:**
1. `BpMilestoneSubmission` audit row created (immutable)
2. Milestone → `submitted`
3. `auto_release_at` set to `now() + MILESTONE_AUTO_RELEASE_DAYS` (default 7 days)
4. `MilestoneSubmitted` event → email to provider (`54-milestone-submitted`)
5. Activity notification to provider: "Review required — auto-releases in N days"

---

### Step 7: Handle Review Outcome

#### If Approved

- Stripe transfer processed: funds land in BP's Stripe Connect account
- Email: `55-milestone-approved`
- Payout appears in `/business/finances`
- Funds appear in Stripe Connect balance within 1–2 business days

#### If Revision Requested

- Milestone → `revision_requested`
- Email: `56-milestone-revision-requested` with provider's feedback
- BP sees feedback in Milestones page and a "Resubmit" CTA
- BP may resubmit (goes back to `submitted`)
- `revision_count` tracks how many rounds have occurred

#### If Auto-Released (Provider didn't respond)

After `auto_release_at` passes without a provider action, `MilestoneAutoReleaseJob` fires:
- Stripe transfer processed automatically
- Email to both parties: `57-milestone-auto-released`
- BP framing: "Payment approved and released"
- Provider framing: "Payment auto-released — review the auto-release policy"

---

### Step 8: Open a Dispute (if rejected unfairly)

**Available from:** Milestones page on `revision_requested` milestones after max revisions, or via Finances page.

**Route:** Provider's `OpenDisputeModal` handles this from the provider side. BP can initiate from Finances.

**While disputed:**
- Milestone shows "Under mediation — funds held by Aegis"
- Escrow frozen
- Admin mediates

---

### Step 9: Post-Contract Review

After contract is `completed`:

**Route:** `POST business.contracts.review`  
**Service:** `ContractReviewService::create()`

BP rates the provider (1–5 stars + dimensions).  
Review stored in `bp_contract_reviews`, shown on provider's public profile (if `is_public = true`).

---

## 7. Dual-Party Workflow Timeline

```
PROVIDER                              SYSTEM                              BP
────────                             ──────                              ──

1. POST /support-services             Job created (draft)
2. POST /support-services/{job}/status Job → open

                                                          3. GET /find-jobs (sees posting)
                                                          4. POST /find-jobs/{job}/propose
                                                             Proposal submitted

5. Reviews application               ActivityLog: proposal_received
6. Moves through kanban stages
7. POST .../accept                   Contract → pending_signature
                                     Email → both parties (50)

8. POST .../contracts/{id}/sign      Provider signed
                                                          9.  POST /contracts/{id}/sign
                                                              BP signed
                                     Contract → pending_funding
                                     Email → both (51)

10. POST .../contracts/{id}/fund     PaymentIntent charged
     OR                              Milestone → funded
     POST .../milestones/{id}/fund   Contract → active
                                     Email → BP (52 or 53)

                                                          11. POST /milestones/{id}/submit
                                                              Submission saved
                                                              auto_release_at set
                                     Email → provider (54)

12. Reviews submission
     a. Approves:                    Transfer → BP Connect
        POST .../review              Milestone → released
        action = "approved"          Email → BP (55)
                                                          Funds in Stripe Connect

     b. Requests revision:                                Email → BP (56)
        POST .../review              Milestone → revision_requested
        action = "revision_requested"                     BP resubmits (go to 11)

     c. Timer fires (no action):     auto_release_at passed
                                     Transfer → BP Connect
                                     Email → both (57)

13. POST .../contracts/{id}/review   Rating written         14. POST /contracts/{id}/review
    Provider rates BP                                            BP rates provider
```

---

## 8. Payments & Escrow Flows

### Funding Mode Comparison

| | `per_milestone` | `full_upfront` |
|---|---|---|
| When charged | Each milestone individually, as provider approves start | Entire contract value at signing time |
| Provider risk | Lower — only funds what's about to start | Higher — full amount locked at start |
| BP confidence | Lower — each milestone must be funded before work | Higher — all funds secured immediately |
| Stripe objects | One `PaymentIntent` per milestone | One `PaymentIntent` for full contract value |

### Escrow Balance Calculation

```php
held_cents     = escrow_funded_cents - escrow_released_cents - escrow_refunded_cents
unfunded_cents = total_value_cents - escrow_funded_cents
```

The `ContractModal` escrow bar shows three chips: **In Escrow** (gold) · **Released** (green) · **Unfunded** (grey).

### Split Resolution (Admin only)

When a dispute ends in compromise:

```php
EscrowService::splitResolution($milestone, $releaseCents, $refundCents, $admin)
// → partial transfers.create (to BP)
// → partial refunds.create (to provider)
// Two bp_escrow_ledger rows: kind='split_release' and kind='split_refund'
```

### Refund Windows

| Scenario | Mechanism | Who can trigger |
|---|---|---|
| Milestone not yet submitted | `refundMilestone()` self-service | Provider |
| Milestone submitted + rejected | `DisputeService` → admin resolves | Admin only |
| Contract cancelled (pre-work) | `cancelContractEscrow()` on all funded milestones | Provider or BP |
| Auto-release timer | `MilestoneAutoReleaseJob` | System (cron) |

---

## 9. Automated Jobs & Timers

### `MilestoneAutoReleaseJob`

- **Schedule:** Hourly
- **Trigger:** `milestone.status = 'submitted' AND auto_release_at <= now()`
- **Action:** Calls `EscrowService::releaseMilestone($milestone, null)` (system as approver)
- **Events fired:** `MilestoneAutoReleased`
- **Emails:** Both parties (`57-milestone-auto-released`)
- **Config:** `MILESTONE_AUTO_RELEASE_DAYS=7` (default)

### `MilestoneReviewReminderJob`

- **Schedule:** Daily at 08:00 UTC
- **Trigger:** `milestone.status = 'submitted' AND auto_release_at` within next `MILESTONE_REVIEW_REMINDER_HOURS` (default 48h) AND `reminder_sent_at IS NULL`
- **Action:** Sends email to provider
- **Email:** `58-milestone-review-reminder`
- **Sets:** `reminder_sent_at` to prevent duplicate emails

---

## 10. Email Notifications Matrix

All emails gated by `notify_payment` or `notify_dispute` user preference unless marked UNGATED.

| # | Template | Trigger Event | Recipient(s) | Gate |
|---|---|---|---|---|
| 40 | `business/40-proposal-accepted` | `ProposalAccepted` | BP | `notify_payment` |
| 50 | `business/50-contract-pending-signature` | `ContractCreated` | Provider + BP | `notify_payment` |
| 51 | `business/51-contract-fully-executed` | `ContractSigned` | Provider + BP | `notify_payment` |
| 52 | `business/52-escrow-funded` | `EscrowFunded` (full contract) | BP | `notify_payment` |
| 53 | `business/53-milestone-funded` | `EscrowFunded` (per milestone) | BP | `notify_payment` |
| 54 | `business/54-milestone-submitted` | `MilestoneReadyForReview` | Provider | `notify_payment` |
| 55 | `business/55-milestone-approved` | `MilestoneReleased` | BP | `notify_payment` |
| 56 | `business/56-milestone-revision-requested` | `MilestoneRevisionRequested` | BP | `notify_payment` |
| 57 | `business/57-milestone-auto-released` | `MilestoneAutoReleased` | Provider + BP | `notify_payment` |
| 58 | `business/58-milestone-review-reminder` | `MilestoneReviewReminderJob` | Provider | `notify_payment` |
| 59 | `business/59-milestone-refunded` | `MilestoneRefunded` | Provider + BP | `notify_payment` |
| 60 | `business/60-contract-cancelled-with-refund` | `ContractCancelled` | Provider + BP | `notify_payment` |
| 61 | `business/61-contract-completed` | `ContractCompleted` | Provider + BP | `notify_payment` |
| bp/32 | `bp/32-support-request-received` | `ProposalSubmitted` | BP (confirmation) | `notify_payment` |
| bp/34 | `bp/34-proposal-declined` | `ProposalDeclined` | BP | `notify_payment` |
| bp/36 | `bp/36-milestone-submitted` | `MilestoneSubmitted` | Provider | `notify_payment` |
| bp/37 | `bp/37-milestone-approved` | `MilestoneApproved` | BP | `notify_payment` |
| d/70 | `disputes/70-opened` | `DisputeOpened` | Respondent | `notify_dispute` |
| d/71 | `disputes/71-replied` | `DisputeReplied` | Counterparty | `notify_dispute` |
| d/72 | `disputes/72-resolved` | `DisputeResolved` | Provider + BP | `notify_dispute` |

---

## 11. Activity Log Events (Portal Notifications)

All written via `ActivityService::log()` with `module = 'job_postings'` and `event_type = 'job_postings'`.  
Visible at `/provider/activity?event_type=job_postings` and `/business/activity?event_type=job_postings`.

Each write produces two records: one `entry_type = 'log'` (actor's own history) and one `entry_type = 'notification'` (counterparty's feed).

| Action | Actor Log | Counterparty Notification |
|---|---|---|
| Proposal submitted | BP: "Proposal submitted: {title}" | Provider: "{BP} submitted a proposal for: {title}" |
| Proposal accepted | Provider: "You hired {BP} for: {title}" | BP: "Your proposal was accepted: {title}" |
| Proposal declined | Provider: "Proposal declined: {title}" | BP: "Your proposal was not accepted: {title}" |
| Contract created | Provider: "Contract created with {BP}" | BP: "Contract created" |
| Contract signed | Provider/BP: "You signed the contract" | Other party: "{Name} signed the contract" |
| Escrow funded (milestone) | Provider: "Milestone funded: {title}" | BP: "{amount} funded for: {title}" |
| Escrow funded (contract) | Provider: "Contract funded: {title}" | BP: "{name} funded the contract" |
| Milestone submitted | BP: "Milestone submitted: {title}" | Provider: "{BP} submitted milestone: {title}" |
| Milestone released | BP: "Payment released: {title}" | Provider: "You approved: {title}" |
| Milestone auto-released | System | Both parties notified |
| Revision requested | Provider: "Revision requested: {title}" | BP: "{Provider} requested a revision: {title}" |
| Escrow refunded | Provider: "Escrow refunded: {title}" | BP: "Escrow refunded on: {title}" |
| Review submitted | Actor: "Review submitted for {name}" | Reviewee: "{name} left you a {N}-star review" |

---

## 12. API Route Reference

### Provider Portal (`/provider` prefix, name prefix `provider.`)

| Method | Path | Route Name | Action |
|---|---|---|---|
| GET | `/support-services` | `jobs.index` | Page load — all job data |
| POST | `/support-services` | `jobs.store` | Create job posting |
| PUT | `/support-services/{job}` | `jobs.update` | Edit posting |
| POST | `/support-services/{job}/status` | `jobs.status` | Change status (open/pause/close) |
| DELETE | `/support-services/{job}` | `jobs.destroy` | Delete draft |
| POST | `/support-services/{job}/proposals/{proposal}/accept` | `jobs.proposal.accept` | Hire BP → creates contract |
| POST | `/support-services/{job}/proposals/{proposal}/decline` | `jobs.proposal.decline` | Decline proposal |
| POST | `/support-services/{job}/proposals/{proposal}/stage` | `jobs.proposal.stage` | Move pipeline stage |
| POST | `/support-services/{job}/proposals/{proposal}/notes` | `jobs.proposal.notes` | Save private notes |
| POST | `/support-services/contracts/{contract}/sign` | `jobs.contract.sign` | Provider signs contract |
| POST | `/support-services/contracts/{contract}/fund` | `jobs.contract.fund` | Fund full contract into escrow |
| POST | `/support-services/contracts/{contract}/cancel` | `jobs.contract.cancel` | Cancel contract |
| POST | `/support-services/contracts/{contract}/milestones` | `jobs.contract.milestones.store` | Add milestone |
| PUT | `/support-services/contracts/{contract}/milestones/{milestone}` | `jobs.contract.milestones.update` | Edit milestone |
| DELETE | `/support-services/contracts/{contract}/milestones/{milestone}` | `jobs.contract.milestones.destroy` | Remove milestone |
| POST | `/support-services/contracts/{contract}/milestones/{milestone}/fund` | `jobs.contract.milestones.fund` | Fund single milestone |
| POST | `/support-services/contracts/{contract}/milestones/{milestone}/review` | `jobs.contract.milestones.review` | Unified review (approve/revise/reject) |
| POST | `/support-services/contracts/{contract}/milestones/{milestone}/approve` | `jobs.contract.milestones.approve` | Approve milestone only |
| POST | `/support-services/contracts/{contract}/milestones/{milestone}/request-revision` | `jobs.contract.milestones.revision` | Request revision only |
| POST | `/support-services/contracts/{contract}/milestones/{milestone}/pay` | `jobs.contract.milestones.pay` | Release payment via PayoutService |
| POST | `/support-services/contracts/{contract}/milestones/{milestone}/refund` | `jobs.contract.milestones.refund` | Self-service refund (pre-submission) |
| GET | `/support-services/contracts/{contract}/pdf` | `jobs.contract.pdf` | Download signed contract PDF |
| POST | `/support-services/contracts/{contract}/review` | `jobs.contract.review` | Submit post-completion review |
| POST | `/support-services/contracts/{contract}/review/dismiss` | `jobs.contract.review.dismiss` | Skip review prompt |

### Business Partner Portal (`/business` prefix, name prefix `bp.`)

| Method | Path | Route Name | Action |
|---|---|---|---|
| GET | `/find-jobs` | `jobs.index` | Browse open postings |
| POST | `/find-jobs/{job}/save` | `jobs.save` | Save job to watchlist |
| POST | `/find-jobs/{job}/propose` | `jobs.propose` | Submit proposal |
| GET | `/proposals` | `proposals.index` | My proposals list |
| DELETE | `/proposals/{proposal}` | `proposals.withdraw` | Withdraw proposal |
| GET | `/contracts` | `contracts.index` | My contracts |
| POST | `/contracts/{contract}/sign` | `contracts.sign` | BP signs contract |
| POST | `/contracts/{contract}/cancel` | `contracts.cancel` | Cancel contract |
| GET | `/contracts/{contract}/pdf` | `contracts.pdf` | Download contract PDF |
| POST | `/contracts/{contract}/review` | `contracts.review` | Submit post-completion review |
| POST | `/contracts/{contract}/review/dismiss` | `contracts.review.dismiss` | Skip review |
| GET | `/milestones` | `milestones.index` | All milestones |
| POST | `/milestones/{milestone}/submit` | `milestones.submit` | Submit milestone work |

---

## 13. Environment Configuration

```env
# Milestone auto-release: days before submitted milestone auto-approves
MILESTONE_AUTO_RELEASE_DAYS=7

# Review reminder: hours before auto-release to send reminder email
MILESTONE_REVIEW_REMINDER_HOURS=48

# Dispute: days respondent has to reply before default ruling
DISPUTE_RESPONDENT_REPLY_DAYS=5

# Stripe sandbox keys
STRIPE_KEY=pk_test_51OCuB1Hnj73y5cBf...
STRIPE_SECRET=sk_test_51OCuB1Hnj73y5cBf...

# Tier limits (affects CS/SS steward slots — not Support Services directly)
TIER_ACCESS_MAX_CS=2
TIER_PRACTICE_MAX_CS=2
```

---

## 14. Demo Users

### Providers with Active Contracts

| Username | Email | Notes |
|---|---|---|
| `p_sarah` | `sarah_johnson@demo.aegis` | Practice tier; real Stripe sub; services mode ON |
| `p_maria` | `maria@demo.aegis` | Practice tier; has active contract with Acme Health |

### Business Partners

| Username | Email | BP Type | Notes |
|---|---|---|---|
| `bp_acme` | `contact@acmehealth.demo.aegis` | Agency | Active contract with p_maria |
| `bp_jamal` | `jamal@demo.aegis` | Freelancer | Pending proposal, submitted milestone |
| `bp_team_owner` | `contact@nexus.demo.aegis` | Agency | Team with members |
| `bp_team_member` | `tanya@demo.aegis` | Freelancer | Member of Nexus team |

**All demo passwords:** `Demo1234!`

### Query-Param Shortcuts

```
/provider/support-services?as=p_maria          — Open as Maria
/business/find-jobs?as=bp_jamal                — Open as Jamal
/business/milestones?as=bp_jamal               — Jamal's milestones
```

---

## Key Demo Contract IDs

| Contract ID | Provider | BP | Status | Notes |
|---|---|---|---|---|
| `contract_acme_maria` | `p_maria` | `bp_acme` | `active` | Milestone 1 approved, Milestone 2 submitted |
| `contract_jamal_sarah_cred` | `p_sarah` | `bp_jamal` | `active` | Milestone `ms_jamal_sarah_1` funded, `ms_jamal_sarah_2` pending |

---

*Document generated from `main @ aegis-laravel` · Aegis v2.4.1 · July 2026*  
*For architecture decisions and escrow rationale see `AEGIS_PAYMENTS_FINANCE.md`*  
*For billing/subscription flows see `AEGIS_BILLING_LIFECYCLE.md`*
