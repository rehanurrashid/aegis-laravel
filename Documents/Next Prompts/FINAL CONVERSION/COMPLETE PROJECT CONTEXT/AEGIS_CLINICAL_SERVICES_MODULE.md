# Aegis — Clinical Services Module
## Complete Workflow Reference — Rev 4 (Terms-Based, Escrow-Free)

**Stack:** PHP 8.2 / Laravel 12 / Vue 3 / Inertia.js v3 / Stripe Connect (destination charges only)
**Module path:** `/provider/services`
**Last updated:** July 2026
**Supersedes:** Rev 3 (hardcoded 30/70 split model)

---

## Rev 4 — What Changed and Why

Dr. Chapman's directive (July 2026): **Aegis never holds funds, never mediates payment splits, never appears in the payment flow beyond routing.** All payment terms are negotiated between the two practitioners and the money moves directly, provider-to-provider, via Stripe destination charge.

**Removed in Rev 4:**
- ❌ Hardcoded 30% deposit / 70% balance split
- ❌ Language of "deposit," "balance due," "held," "escrow" in Clinical Services UI (BP module keeps its own escrow — separate)
- ❌ `getExpectedDepositCentsAttribute` / `getExpectedBalanceCentsAttribute` fixed-percentage math
- ❌ Aegis-imposed payment structure

**Added in Rev 4:**
- ✅ Three payment structures: `full_upfront` · `split` · `full_on_completion`
- ✅ Terms negotiation embedded in the request/accept flow — either party may propose, the accept action commits
- ✅ Per-listing default payment terms on `services` (provider seeds the offer)
- ✅ Per-request proposed terms on `service_requests` (client may propose different)
- ✅ Committed terms on `service_sessions` (source of truth once accepted)
- ✅ Explicit "direct-to-provider" language in every payment modal
- ✅ Generic `chargeSessionPortion($session, $portion)` in `PayoutService` — no fixed percentages

**Preserved:**
- ✅ Existing `deposit_cents` / `balance_cents` / `deposit_charge_id` / `balance_charge_id` columns (zero-risk backward compat — repurposed as "portion A / portion B" holders)
- ✅ Payment status enum (`unpaid → deposit_paid → paid`) with `deposit_paid` renamed conceptually to `upfront_paid` (UI copy only) — enum value stays for backward compat
- ✅ Refund system entirely (Rev 3 refund flow unchanged)
- ✅ Dispute escalation entirely

---

## Table of Contents

1. [Overview](#1-overview)
2. [Architecture — Aegis Never Holds Funds](#2-architecture--aegis-never-holds-funds)
3. [Payment Structures](#3-payment-structures)
4. [Terms Negotiation Flow](#4-terms-negotiation-flow)
5. [Database Schema (Rev 4 changes)](#5-database-schema)
6. [State Machines](#6-state-machines)
7. [Tab Structure — Services.vue](#7-tab-structure)
8. [Workflow A — Provider: Manage Listings + Default Terms](#8-workflow-a)
9. [Workflow B — Provider: Handle Incoming Requests](#9-workflow-b)
10. [Workflow C — Client: Browse & Request](#10-workflow-c)
11. [Workflow D — Client: Track Outgoing Requests](#11-workflow-d)
12. [Workflow E — Payment: Upfront Portion](#12-workflow-e)
13. [Workflow F — Payment: Completion Portion](#13-workflow-f)
14. [Workflow G — Refund Lifecycle](#14-workflow-g)
15. [Workflow H — Dispute Escalation](#15-workflow-h)
16. [Notifications & Activity Logging](#16-notifications--activity-logging)
17. [Stripe Integration](#17-stripe-integration)
18. [Route Map](#18-route-map)
19. [Service Layer — Key Methods](#19-service-layer)
20. [Demo Mode](#20-demo-mode)
21. [Appendix A — Computed Attributes](#appendix-a)
22. [Appendix B — Email Templates](#appendix-b)
23. [Appendix C — Migration From Rev 3](#appendix-c)

---

## 1. Overview

The Clinical Services module enables practitioners to offer peer clinical services (supervision, consultation, training, coaching, practice continuity) to other practitioners. It operates on a **provider ↔ client** model where both parties are practitioners — one offers a service, the other books and pays for it.

**Payment principle:** The two practitioners agree on how they want to pay. Aegis provides three structures and lets them pick and customize. All money moves via Stripe destination charge — direct from client's card to provider's Stripe Connect account. Aegis's balance is $0 net on every session, always.

**Money flow (universal):**
```
Client's card (users.stripe_payment_method_id)
    ↓  PaymentIntent { transfer_data.destination = provider.stripe_account_id }
Aegis Platform account ($0 net)
    ↓  atomic destination transfer
Provider's Stripe Connect account (users.stripe_account_id)
    ↓  provider's own payout schedule
Provider's bank
```

Aegis does not touch the money. Aegis does not mediate the split. Aegis records what was agreed and fires the charges the two parties committed to.

---

## 2. Architecture — Aegis Never Holds Funds

### The Rule
> **Aegis is not a payment processor. Aegis is a routing layer.**

Every charge in Clinical Services is a Stripe **destination charge** (`transfer_data.destination`) — the funds are atomically transferred to the provider's Connect account at authorization time. Aegis's platform balance is never debited or credited by clinical session transactions.

### What Aegis Does
- Routes the PaymentIntent with the provider's Connect account as destination
- Records the intent/charge ID on the session
- Fires activity + email notifications
- Records what the two parties agreed to
- If a refund is issued: calls Stripe's refund API with `reverse_transfer: true` — funds are pulled from the provider's Connect balance back to the client's card. Aegis's balance is untouched.

### What Aegis Does Not Do
- ❌ Hold funds pending session completion
- ❌ Escrow anything on behalf of either party
- ❌ Set or enforce a percentage split
- ❌ Guarantee the transaction — parties deal with each other directly
- ❌ Act as counterparty to any charge

### UI Language Standard
Every payment modal, invoice, email, and tooltip in Clinical Services must include (or link to) this note:

> *Payment routes directly to the provider's Stripe account via Stripe Connect. Aegis does not hold, escrow, or process funds on your behalf. You are transacting directly with the provider.*

Old copy referring to "deposit held," "balance due to Aegis," or "escrow" must not appear anywhere in Clinical Services after the Rev 4 rollout.

---

## 3. Payment Structures

### 3.1 `full_upfront` (100% at booking confirmation)
- Client pays the full agreed amount when confirming the accepted request.
- Session runs later.
- `upfront_percentage = 100`, `upfront_cents = agreed_amount`, `completion_cents = 0`.
- Payment status jumps `unpaid → paid` in one charge.

### 3.2 `split` (X% upfront + (100−X)% at completion)
- Client pays X% at booking confirmation.
- Client pays remaining (100−X)% when confirming the session was completed.
- X is any integer 1–99 (default 30, mirroring the old system so anyone who does nothing gets the previous behaviour).
- Payment status: `unpaid → deposit_paid → paid`.

### 3.3 `full_on_completion` (100% after session)
- No money moves at booking. Session is scheduled on trust.
- Client pays the full agreed amount after the session, at the same moment they confirm completion.
- **Gated by provider's `services.allow_completion_only = 1`** — if the provider doesn't opt in on the listing, this option is not offered to the client.
- Payment status: `unpaid → paid`.
- `upfront_percentage = 0`, `upfront_cents = 0`, `completion_cents = agreed_amount`.

### Structure comparison

| Structure | Booking charge | Completion charge | Payment status path |
|---|---|---|---|
| `full_upfront` | 100% | — | `unpaid → paid` |
| `split` | X% (1–99) | 100−X% | `unpaid → deposit_paid → paid` |
| `full_on_completion` | — | 100% | `unpaid → paid` |

### Default preload
When a client opens the Request modal for a service, the modal preloads:
- Structure from `services.default_payment_structure`
- Upfront % from `services.default_upfront_percentage` (if `split`)
- Terms note from `services.default_terms_note`

The client can accept the provider's default as-proposed, or change any of the three and submit their own proposal.

### Provider override at accept time
When the provider opens the Accept modal for a request, they see the client's proposed terms. They may:
- **Accept as proposed** — commit the client's terms to the session
- **Counter with different terms** — change structure / upfront % / note; the accept action commits the provider's counter (client is bound by clicking "confirm & pay" at the next step)

There is no separate multi-round negotiation — the accept action commits whichever party's terms are chosen at that moment. If either party wants a different structure, they use the request/counter/decline cycle already in place.

---

## 4. Terms Negotiation Flow

### 4.1 Who proposes what — decision matrix

| Scenario | Terms shown | Committed by |
|---|---|---|
| Client opens Request modal on a service | Provider's listing defaults | Client's Send Request click |
| Client edits terms before sending | Client's edited proposal | Client's Send Request click |
| Provider opens Accept modal | Client's proposed terms | Provider's Accept click |
| Provider counters in Accept modal | Provider's counter | Provider's Accept click |

The final `payment_structure` / `upfront_percentage` on the `service_sessions` row is authoritative. `terms_source` records who won: `provider_default`, `client_proposed`, `provider_countered`.

### 4.2 Data flow through the tables

```
services
  ├─ default_payment_structure     ("split")
  ├─ default_upfront_percentage    (30)
  ├─ default_terms_note            ("Cancellations less than 24hr are non-refundable.")
  └─ allow_completion_only         (0 = block full_on_completion option for clients)

           │  client opens Request modal, preloads from services.*
           ▼
service_requests
  ├─ proposed_payment_structure    ("split" | "full_upfront" | "full_on_completion")
  ├─ proposed_upfront_percentage   (30)
  ├─ proposed_terms_note           ("Cancellations less than 24hr are non-refundable.")
  └─ terms_source                  ("provider_default" | "client_proposed")

           │  provider opens Accept modal, may accept-as-is or counter
           ▼
service_sessions
  ├─ payment_structure             (final committed value)
  ├─ upfront_percentage            (final committed value)
  ├─ upfront_cents                 (computed at accept time from agreed_amount × pct)
  ├─ completion_cents              (agreed_amount − upfront_cents)
  ├─ terms_note                    (final committed note)
  ├─ terms_source                  ("provider_default" | "client_proposed" | "provider_countered")
  └─ terms_agreed_at               (timestamp of provider's accept click)
```

### 4.3 UI touchpoints

| Location | What's shown | What can change |
|---|---|---|
| `services.default_terms` panel in Services → Listings edit | The provider's baseline offer | Structure, upfront %, terms note, allow_completion_only |
| `ServiceRequestModal` (Explore + Public Profile) | Preloaded provider defaults + editable terms block | Structure (respects `allow_completion_only`), upfront % (if split), terms note (append-only), agreement checkbox |
| Provider "Request Detail" modal in Services → Requests | Client's proposed terms (read-only summary) | — |
| Provider "Accept Request" modal in Services → Requests | Client's proposed terms + Counter Terms inline (like CounterOfferInline) | Structure, upfront %, note |
| `PayUpfrontModal` (was PayDepositModal) | Final committed terms + upfront amount + agreement checkbox | — |
| `PayCompletionModal` (was PayBalanceModal) | Final committed terms + completion amount + agreement checkbox | — |
| Session invoice PDF | Structure + terms note + who proposed | — |

### 4.4 Agreement checkbox — every payment modal

Every payment modal (`PayUpfrontModal`, `PayCompletionModal`) requires the client to tick:

> *I understand this payment goes directly to `{provider name}` via Stripe. Aegis is not the recipient and does not hold these funds. The agreed terms for this session are: `{structure summary}`. `{terms note}`*

`agree_terms: required|accepted` server-side. Vuelidate `required + sameAs(true)` client-side.

---

## 5. Database Schema

### 5.1 `services` — Rev 4 additions

| Column | Type | Default | Notes |
|---|---|---|---|
| `default_payment_structure` | ENUM(`full_upfront`,`split`,`full_on_completion`) | `split` | Provider's baseline offer |
| `default_upfront_percentage` | UNSIGNED TINYINT | `30` | 1–99; ignored unless structure = `split` |
| `default_terms_note` | TEXT | NULL | Free-form terms text shown on listing + preloaded into requests |
| `allow_completion_only` | TINYINT(1) | `0` | If 1, clients may propose `full_on_completion`; if 0, that option is hidden client-side |

**Migration file:** `2026_07_21_000001_add_default_payment_terms_to_services.php`
All columns nullable-safe or defaulted so existing rows continue working with the previous 30/70 behaviour.

### 5.2 `service_requests` — Rev 4 additions

| Column | Type | Default | Notes |
|---|---|---|---|
| `proposed_payment_structure` | ENUM(`full_upfront`,`split`,`full_on_completion`) | `split` | What the client proposed (may equal provider default) |
| `proposed_upfront_percentage` | UNSIGNED TINYINT | `30` | 1–99; ignored unless structure = `split` |
| `proposed_terms_note` | TEXT | NULL | Terms note the client saw + agreed to at request time |
| `terms_source` | ENUM(`provider_default`,`client_proposed`) | `provider_default` | Where these terms came from |

**Migration file:** `2026_07_21_000002_add_proposed_payment_terms_to_service_requests.php`

### 5.3 `service_sessions` — Rev 4 additions

| Column | Type | Default | Notes |
|---|---|---|---|
| `payment_structure` | ENUM(`full_upfront`,`split`,`full_on_completion`) | `split` | Final committed structure |
| `upfront_percentage` | UNSIGNED TINYINT | `30` | Final committed pct; 100 for full_upfront, 0 for full_on_completion, X for split |
| `upfront_cents` | UNSIGNED INT | `0` | Computed at accept: `floor(agreed × upfront_percentage / 100)` |
| `completion_cents` | UNSIGNED INT | `0` | `agreed_amount − upfront_cents` |
| `terms_note` | TEXT | NULL | Final committed terms text (shown on invoice, in emails, in modals) |
| `terms_source` | ENUM(`provider_default`,`client_proposed`,`provider_countered`) | `provider_default` | Who won the negotiation |
| `terms_agreed_at` | TIMESTAMP | NULL | Set when provider accepts (this is the contract-formation moment) |

**Migration file:** `2026_07_21_000003_add_committed_payment_terms_to_service_sessions.php`

### 5.4 Existing columns — semantic repurpose

The existing `deposit_cents` / `deposit_charge_id` / `deposit_paid_at` / `balance_cents` / `balance_charge_id` / `balance_paid_at` columns stay untouched at the DB level.

**Semantics migrate:**
- `deposit_cents` = whatever was charged first (the upfront portion — could be 100% for `full_upfront`, could be 0 recorded for `full_on_completion` where no upfront charge fires)
- `balance_cents` = whatever was charged second (the completion portion — could be 100% for `full_on_completion`, could be 0 for `full_upfront`)

Old rows keep working. New rows use the new `upfront_cents` / `completion_cents` as the source of truth for display and calculations, and `PayoutService` writes to both old + new columns for one release cycle before old columns are deprecated.

### 5.5 `payment_status` enum — no schema change

Values unchanged: `unpaid | deposit_paid | paid | refunded | partially_refunded`

**Rev 4 semantic mapping:**

| Value | Meaning (Rev 4) | UI Label |
|---|---|---|
| `unpaid` | No portion paid yet | *Payment Pending* |
| `deposit_paid` | Upfront portion paid (split OR full_upfront both use this transition) | *Upfront Paid* |
| `paid` | Everything paid (100% received by provider) | *Paid In Full* |
| `refunded` | Fully refunded | *Refunded* |
| `partially_refunded` | Partial refund | *Partially Refunded* |

`full_upfront` and `full_on_completion` structures move `unpaid → paid` in one charge. `split` moves `unpaid → deposit_paid → paid` in two.

**Not changing the enum value name** avoids a MySQL ENUM migration, which under load can throw truncation errors on `plan_stewards`-style bugs. Purely a UI copy migration.

---

## 6. State Machines

### 6.1 Session lifecycle (`service_sessions.status`)
Unchanged from Rev 3.
```
scheduled → completed   (client confirms; may trigger completion charge depending on structure)
scheduled → cancelled   (provider or client cancels)
scheduled → no_show     (admin/system marks)
```

### 6.2 Payment lifecycle by structure

**`full_upfront`:**
```
unpaid  ── PayUpfrontModal ──▶ paid
                                └─▶ refunded | partially_refunded (refund flow)
```

**`split`:**
```
unpaid  ── PayUpfrontModal ──▶ deposit_paid ── PayCompletionModal ──▶ paid
                                                                       └─▶ refunded | partially_refunded
```

**`full_on_completion`:**
```
unpaid  ── PayCompletionModal (at session-complete confirmation) ──▶ paid
                                                                     └─▶ refunded
```

### 6.3 Refund request lifecycle
Unchanged from Rev 3. Available refund types now derive from structure:

| Structure + payment_status | Available refund types |
|---|---|
| `full_upfront` + `paid` | Full refund |
| `split` + `deposit_paid` | Upfront only (was: Deposit only) |
| `split` + `paid` | Upfront only, Completion only, Full |
| `full_on_completion` + `paid` | Full refund |
| any + `partially_refunded` | Depends on which portions remain unrefunded |
| any + `unpaid` | None |

The `session_refund_requests.refund_type` enum keeps its existing values (`deposit_only`, `balance_only`, `full`) — treated as aliases for `upfront_only`, `completion_only`, `full` in UI.

---

## 7. Tab Structure

`Services.vue` at `/provider/services` — 5 sidebar-nav tabs, unchanged from Rev 3.

| Tab key | Label | Who uses it |
|---|---|---|
| `listings` | My Listings | Provider (now includes default terms editor) |
| `requests` | Service Requests | Provider |
| `bookings` | Bookings & Sessions | Provider |
| `outgoing` | My Requests | Client |
| `explore` | Browse Services | Client |
| `settings` | Settings | Both |

---

## 8. Workflow A — Provider: Manage Listings + Default Terms

### 8.1 Create listing
**UI:** Services → Listings tab → "New Service" button → Create Service modal
**Route:** `POST /provider/services`

**New fields in Rev 4:**
```
default_payment_structure     required, in: full_upfront|split|full_on_completion
default_upfront_percentage    required_if: default_payment_structure,split, min:1, max:99
default_terms_note            nullable|string|max:2000
allow_completion_only         nullable|boolean
```

The Create/Edit modal shows a **Default Payment Terms** panel with:
- Radio group for structure (three options with icons + one-line descriptions)
- If `split`: number input for upfront % (defaults 30, range 1–99)
- Textarea for terms note
- Toggle: "Allow clients to propose 100% on completion"

### 8.2 Edit listing
**Route:** `PUT /provider/services/{service}`
Same fields editable. Provider can adjust defaults any time. Existing `service_requests` are not retroactively updated — they retain the terms captured at the time of request.

### 8.3 Publish / Pause / Archive
Unchanged.

### 8.4 Listing display
On the provider's own listings tab, each card now shows a small "Terms" chip summarizing the default (e.g. *"30% upfront + 70% completion"* or *"Pay in full at booking"*), clickable to expand the terms note.

On the public profile + Explore grid, each service card shows the same terms chip.

---

## 9. Workflow B — Provider: Handle Incoming Requests

### 9.1 Request arrives
Requests now include the client's proposed payment terms, visible in the Request Detail modal and the Accept modal.

### 9.2 Request Detail modal — new "Proposed Terms" section
Between "Message" and the footer, a read-only panel:
```
┌── Proposed Payment Terms ─────────────────────────────┐
│ Structure:      Split — 30% upfront, 70% completion   │
│ Terms note:     "Cancellations less than 24hr..."      │
│ Source:         Client accepted your listing defaults │
└───────────────────────────────────────────────────────┘
```
`Source` reads either "Client accepted your listing defaults" (`terms_source = provider_default`) or "Client proposed different terms" (`terms_source = client_proposed`).

### 9.3 Accept a request
**UI:** Service Requests tab → "Accept" button → Accept modal
**Route:** `POST /provider/services/{service}/requests/{serviceRequest}/accept`

The Accept modal now contains:
1. Date/time/timezone/format/note (as Rev 3)
2. `CounterOfferInline` for price negotiation (as Rev 3)
3. **NEW: `CounterTermsInline` component** — mirrors CounterOfferInline pattern
   - Toggle: "Accept the terms as proposed" (default on)
   - When toggled off: shows the same three-radio structure picker + upfront % input + terms note
   - The toggle-off case emits `terms_source = provider_countered` and the new values

**Request data (Rev 4 additions to `AcceptServiceRequestRequest`):**
```
committed_payment_structure    nullable|in:full_upfront,split,full_on_completion
committed_upfront_percentage   nullable|integer|min:1|max:99
committed_terms_note           nullable|string|max:2000
terms_countered                nullable|boolean
```

If `terms_countered = false` or null: session inherits the request's proposed terms + `terms_source = ${request.terms_source}`.
If `terms_countered = true`: session takes the provider's counter values + `terms_source = provider_countered`.

**Service layer flow (unchanged shape, new computations):**
1. `ServiceService::acceptRequest()` marks request accepted, calls `bookSession()`
2. `bookSession()` computes final terms:
   - Reads structure/pct from `terms_countered` payload OR from `service_requests.proposed_*`
   - Computes `upfront_cents = floor(agreed × pct / 100)` (100 for full_upfront, 0 for full_on_completion)
   - Computes `completion_cents = agreed − upfront_cents`
   - Writes `payment_structure`, `upfront_percentage`, `upfront_cents`, `completion_cents`, `terms_note`, `terms_source`, `terms_agreed_at = now()` to session
   - Also writes to legacy `deposit_cents = upfront_cents`, `balance_cents = completion_cents` for backward compat
3. Activity log + email as Rev 3

### 9.4 Decline / withdraw / cancel — unchanged

---

## 10. Workflow C — Client: Browse & Request

### 10.1 Browse — cards show terms chip
Every card in Explore + on public profiles now shows the provider's default terms chip:

| Structure | Chip label |
|---|---|
| `full_upfront` | *Pay in full at booking* |
| `split` (X%) | *X% upfront + (100−X)% completion* |
| `full_on_completion` | *Pay after session* |

Click chip → tooltip shows the full `default_terms_note`.

### 10.2 Submit request from Explore or Public Profile
**Route:** `POST /provider/services/explore/request` OR `POST /public/profiles/{user}/service-request`
Modal is the shared `ServiceRequestModal.vue`.

**New "Payment Terms" section in the modal** (below Message, above Send):
```
─── Payment Terms ──────────────────────────────────────
The provider offers:  [chip]  See details
Choose your terms:
  ○ Accept provider's terms (recommended)
  ○ Propose different terms

[when second option chosen:]
  Structure:  ( ) Pay in full at booking
              (•) Split payment: [30]% upfront
              ( ) Pay in full after session   [only if allow_completion_only=1]
  Terms note (optional): [textarea, pre-filled with provider default]

☐ I agree to these terms and understand payment routes
   directly to the provider via Stripe. Aegis does not
   hold or process these funds.
──────────────────────────────────────────────────────
```

**New form fields (in `SendServiceRequestFromExploreRequest` + public profile FormRequest):**
```
proposed_payment_structure     required|in:full_upfront,split,full_on_completion
proposed_upfront_percentage    required_if:proposed_payment_structure,split|integer|min:1|max:99
proposed_terms_note            nullable|string|max:2000
terms_source                   required|in:provider_default,client_proposed
agree_terms                    required|accepted
```

Backend guard: if `proposed_payment_structure = full_on_completion` and `service.allow_completion_only = 0`, reject with a validation error.

### 10.3 Service layer
`ServiceService::submitRequest()` writes the four new columns to the `service_requests` row.

### 10.4 Public profile — same modal
`ServiceRequestModal` is used both from Explore and from the public profile page. The terms block appears in both. `providerId` prop set → posts to `public.profile.service-request`; `serviceId` prop set → posts to `provider.services.explore.request`.

---

## 11. Workflow D — Client: Track Outgoing Requests
Unchanged from Rev 3 — session cards in the "My Requests" tab still show status and let the client pay upfront / completion / open refund. Card copy is scrubbed for "deposit/balance" → "upfront/completion" (see §12–13).

---

## 12. Workflow E — Payment: Upfront Portion

### 12.1 Prerequisites
- Session `status = scheduled`
- Session `payment_status = unpaid`
- Session `payment_structure ∈ {full_upfront, split}` (for `full_on_completion`, no upfront charge — this modal doesn't appear)
- Client has `stripe_id` + `stripe_payment_method_id`
- Provider has `stripe_account_id`

### 12.2 UI trigger
**My Requests tab → Session card → "Pay Upfront" button → `PayUpfrontModal`** (renamed from `PayDepositModal`)

Modal contents:
- Service title + provider name
- Agreed rate
- **Committed terms summary** — structure + upfront % + full terms note text
- **Upfront amount** (highlighted, gold)
- **Remaining after this payment** (for split; hidden for full_upfront)
- Direct-to-provider disclosure paragraph
- "I agree" checkbox
- Confirm button labelled: *"Pay $X upfront"* (full_upfront) or *"Pay $X (Y% upfront)"* (split)

### 12.3 Backend flow
**Route:** `POST /provider/services/sessions/{session}/upfront` (Rev 4 rename — keep `/deposit` as a deprecated alias for one release for backward compat)

```
ServicesController::payUpfront()
  └─ ServiceService::payUpfront()
       └─ Guards:
            • client_id === auth user
            • payment_status === unpaid
            • session.status === scheduled
            • payment_structure IN (full_upfront, split)
       └─ Compute portion cents = session.upfront_cents
       └─ PayoutService::chargeSessionPortion($session, 'upfront')
            ├─ isDemo()? → stub pi_demo_up_* → update DB
            ├─ !stripe.secret? → stub pi_stub_up_* → update DB
            └─ Live Stripe:
                 → paymentIntents->create(
                       amount: upfront_cents,
                       destination: provider.stripe_account_id,
                       on_behalf_of: provider.stripe_account_id,
                       metadata: { session_id, portion: 'upfront', payment_structure, ... }
                   )
                 → write charge id + timestamp back to session
       └─ Update payment_status:
            • full_upfront → paid   (single charge = done)
            • split        → deposit_paid   (one more charge to come)
       └─ event(SessionUpfrontPaid) → email + activity notification
```

### 12.4 DB updates on success
For **`full_upfront`:**
```
deposit_cents        = agreed_amount (backward compat)
deposit_charge_id    = pi_*
deposit_paid_at      = now()
balance_cents        = 0
payment_status       = 'paid'
```
For **`split`:**
```
deposit_cents        = upfront_cents
deposit_charge_id    = pi_*
deposit_paid_at      = now()
payment_status       = 'deposit_paid'
```

### 12.5 `PractitionerPayment` record
```
kind         = 'service_session_upfront'  (was 'service_session_deposit')
amount_cents = upfront_cents
status       = 'paid' (demo/stub) or 'paid/pending' (live, per webhook)
```

---

## 13. Workflow F — Payment: Completion Portion

### 13.1 Prerequisites
- Session `status = scheduled`
- Client is `client_id`
- Payment status:
  - `full_upfront`: N/A — already paid, this modal doesn't appear; the "Confirm Complete" button becomes a bare confirmation (no charge fires)
  - `split`: `payment_status = deposit_paid`
  - `full_on_completion`: `payment_status = unpaid`

### 13.2 UI trigger
**My Requests tab → Session card → "Confirm Complete & Pay" button (`split` / `full_on_completion`) or "Confirm Complete" button (`full_upfront`) → `PayCompletionModal`** (renamed from `PayBalanceModal`)

Modal shows:
- Session complete confirmation
- **Committed terms summary**
- **Completion amount** = `agreed − upfront_cents` (0 for `full_upfront` — modal shows "No further payment due, this only marks the session complete")
- Direct-to-provider disclosure
- "I confirm the session occurred and agree to release payment" checkbox
- Confirm button

### 13.3 Backend flow
**Route:** `POST /provider/services/sessions/{session}/complete`

```
ServicesController::completeSession()
  └─ ServiceService::completeSession()
       └─ Guards: client_id === actor, status === scheduled
       └─ Update: session.status = completed, completed_at = now()
       └─ Branch on payment_structure:

          full_upfront:
            No charge fires. payment_status stays 'paid'.
            event(SessionCompleted) → email provider.

          split:
            PayoutService::chargeSessionPortion($session, 'completion')
              → charges completion_cents to provider
              → payment_status = 'paid'
            event(SessionCompletionPaid) → email provider + client

          full_on_completion:
            PayoutService::chargeSessionPortion($session, 'completion')
              → charges agreed_amount to provider
              → payment_status = 'paid'
            event(SessionCompletionPaid) → email provider + client
            event(SessionCompleted) → email provider
```

### 13.4 DB updates on success (split + full_on_completion)
```
balance_cents        = completion_cents  (backward compat)
balance_charge_id    = pi_*
balance_paid_at      = now()
payment_status       = 'paid'
```

### 13.5 `PractitionerPayment` record
```
kind         = 'service_session_completion'  (was 'service_session_balance')
amount_cents = completion_cents
```

---

## 14. Workflow G — Refund Lifecycle
Unchanged in mechanism from Rev 3. Only copy changes:

- "Deposit only" → "Upfront only"
- "Balance only" → "Completion only"
- "Full refund" — unchanged

The `session_refund_requests.refund_type` enum values (`deposit_only`, `balance_only`, `full`) stay put; UI is remapped. `SessionRefundService::open()` recomputes `amount_requested_cents` from the session's `upfront_cents` / `completion_cents` (not from a hardcoded 30/70 split).

Available refund-type derivation now driven by `payment_structure` + `payment_status` combination (see §6.3).

---

## 15. Workflow H — Dispute Escalation
Unchanged from Rev 3.

---

## 16. Notifications & Activity Logging

All copy is scrubbed for "deposit/balance" → "upfront/completion". Activity log actions:

| Rev 3 action | Rev 4 action |
|---|---|
| `deposit_paid` | `upfront_paid` |
| `balance_paid` | `completion_paid` |
| `session_deposit_paid` (event name) | `SessionUpfrontPaid` |
| `session_balance_paid` (event name) | `SessionCompletionPaid` |

### Complete event matrix (Rev 4)

| Action | Actor log | Other-party notification | Email |
|---|---|---|---|
| Service created (incl. default terms) | ✅ provider | — | — |
| Service updated | ✅ provider | — | — |
| Service archived | ✅ provider | — | — |
| Request sent (with proposed terms) | ✅ client | ✅ provider | 58-service-inquiry-received |
| Request accepted (with committed terms) | ✅ provider | ✅ client | 59-service-inquiry-responded |
| Request declined | ✅ provider | ✅ client | 59-service-inquiry-responded |
| Request withdrawn | ✅ client | — | — |
| Upfront paid | ✅ client | ✅ provider | 62-session-upfront-paid |
| Completion paid | ✅ client | ✅ provider | 63-session-completion-paid |
| Session completed (no charge — full_upfront) | ✅ client | ✅ provider | 61-session-completed |
| Session cancelled | ✅ actor | ✅ other party (Warning) | 60-session-cancelled |
| Refund requested | ✅ client | ✅ provider (Warning) | 64-session-refund-requested |
| Refund approved | ✅ provider | ✅ client | 65-session-refund-approved |
| Refund denied | ✅ provider | ✅ client (Warning) | 66-session-refund-denied |
| Refund escalated | ✅ client | ✅ provider (Critical) | 67-session-refund-escalated |

Blade templates `62-session-deposit-paid` and `63-session-balance-paid` are renamed to `62-session-upfront-paid` and `63-session-completion-paid`. Old file names left as thin aliases redirecting to new for one release cycle.

Every email that references money now includes the standard direct-to-provider disclosure line.

---

## 17. Stripe Integration

### Charge signature — universal
```
PayoutService::chargeSessionPortion(ServiceSession $session, string $portion): PractitionerPayment
    where portion ∈ {'upfront', 'completion'}
```

Internally computes `amountCents` from `$session->upfront_cents` or `$session->completion_cents` based on `$portion`. All other logic (demo detection, guards, live Stripe call, DB writeback, event dispatch) matches Rev 3's `chargeSessionDeposit` / `chargeSessionBalance` — those two methods are kept as thin wrappers over `chargeSessionPortion` for backward compat, marked `@deprecated`.

### PaymentIntent metadata (Rev 4)
```json
{
  "payment_id": "pp_...",
  "session_id": "ss_...",
  "portion": "upfront" | "completion",
  "payment_structure": "full_upfront" | "split" | "full_on_completion",
  "upfront_percentage": 30,
  "practitioner_id": "p_...",
  "client_id": "p_...",
  "agreed_total": 22000,
  "terms_source": "provider_default" | "client_proposed" | "provider_countered"
}
```

Demo detection + stub detection + refund flow — unchanged.

---

## 18. Route Map

### Outside `services.mode` middleware
| Method | Path | Controller method | Name |
|---|---|---|---|
| POST | `/services/sessions/{session}/upfront` | `payUpfront` | `services.session.upfront` |
| POST | `/services/sessions/{session}/deposit` | `payUpfront` (alias, deprecated) | `services.session.deposit` |
| POST | `/services/sessions/{session}/complete` | `completeSession` | `services.session.complete` |
| GET | `/services/sessions/{session}/invoice` | `downloadInvoice` | `services.session.invoice` |
| GET | `/services/explore` | `explore` | `services.explore` |
| POST | `/services/explore/request` | `storeExploreRequest` | `services.explore.request` |
| POST | `/services/sessions/{session}/refund-requests` | `storeRefundRequest` | `services.session.refund.store` |
| POST | `/services/refund-requests/{refund}/escalate` | `escalateRefundRequest` | `services.refund.escalate` |
| POST | `/services/refund-requests/{refund}/approve` | `approveRefundRequest` | `services.refund.approve` |
| POST | `/services/refund-requests/{refund}/deny` | `denyRefundRequest` | `services.refund.deny` |

### Inside `services.mode` middleware
Unchanged from Rev 3.

---

## 19. Service Layer

### `ServiceService` — Rev 4 changes
| Method | Rev 4 change |
|---|---|
| `create(User, array)` | Accepts + writes `default_payment_structure`, `default_upfront_percentage`, `default_terms_note`, `allow_completion_only` |
| `update(Service, array)` | Same |
| `submitRequest(Service, User, array)` | Accepts + writes `proposed_payment_structure`, `proposed_upfront_percentage`, `proposed_terms_note`, `terms_source`; guards `full_on_completion` against `allow_completion_only` |
| `acceptRequest(ServiceRequest, array)` | Reads client's proposed terms; if `terms_countered=true` overrides with provider's values; writes committed terms to session |
| `bookSession(ServiceRequest, array)` | Computes `upfront_cents` / `completion_cents` from committed structure + pct + agreed amount; writes new + legacy columns |
| `payDeposit()` | ⚠️ Kept as deprecated alias for `payUpfront()` |
| `payUpfront(ServiceSession, User)` | Guards structure ∈ {full_upfront, split}; delegates to `PayoutService::chargeSessionPortion($session, 'upfront')`; updates payment_status per structure |
| `completeSession(ServiceSession, string)` | Branches on `payment_structure` per §13.3 |

### `PayoutService` — Rev 4 changes
| Method | Rev 4 change |
|---|---|
| `chargeSessionPortion(ServiceSession, string $portion)` | **NEW** — unified charge method |
| `chargeSessionDeposit()` | Deprecated wrapper → `chargeSessionPortion($session, 'upfront')` |
| `chargeSessionBalance()` | Deprecated wrapper → `chargeSessionPortion($session, 'completion')` |
| `refundSessionCharge()` | Unchanged |

### `SessionRefundService` — Rev 4 changes
| Method | Rev 4 change |
|---|---|
| `open()` | `amount_requested_cents` computed from session's `upfront_cents`/`completion_cents`, not from hardcoded 30/70 |
| `approve()` / `deny()` / `escalate()` | Unchanged |

---

## 20. Demo Mode
Behaviour unchanged. Stub IDs updated:
- `pi_demo_dep_*` → `pi_demo_up_*` (upfront)
- `pi_demo_bal_*` → `pi_demo_comp_*` (completion)
- `pi_stub_dep_*` → `pi_stub_up_*`
- `pi_stub_bal_*` → `pi_stub_comp_*`

Old prefixes remain recognized as-is by refund + reconciliation paths for existing demo rows.

---

## Appendix A — Computed Attributes (ServiceSession model)

| Attribute | Formula (Rev 4) | Purpose |
|---|---|---|
| `agreed_amount_cents` | `negotiated_amount_cents ?? amount_cents ?? 0` | Source of truth for money math |
| `upfront_cents_computed` | Prefers stored `upfront_cents`; falls back to `floor(agreed × upfront_percentage / 100)` for old rows | Display + charge amount |
| `completion_cents_computed` | Prefers stored `completion_cents`; falls back to `agreed − upfront_cents_computed` | Display + charge amount |
| `remaining_cents` | `agreed − deposit_cents − balance_cents + total_refunded_cents` | What client still owes |
| `payment_structure_label` | Human string for chips/emails | UI |
| `terms_summary` | One-line human summary of committed terms | UI |
| `invoice_number` | `SES-YYYY-MM-{id[0:8]}` | Unchanged |
| `has_pending_refund_request` | DB query | Unchanged |

The old `expected_deposit_cents` / `expected_balance_cents` attributes are kept but re-implemented to read from `upfront_cents` / `completion_cents` when present, falling back to `floor(× 0.30)` only when both new columns are 0 (i.e. pre-Rev 4 rows).

---

## Appendix B — Email Templates

Rev 4 template list:

| Template | Trigger event | Recipient(s) |
|---|---|---|
| `emails.gaps.58-service-inquiry-received` | `ServiceRequestSubmitted` | Provider — now includes client's proposed terms |
| `emails.gaps.59-service-inquiry-responded` | `ServiceRequestResponded` | Client — now includes committed terms |
| `emails.services.60-session-cancelled` | `SessionCancelled` | Other party |
| `emails.services.61-session-completed` | `SessionCompleted` | Provider |
| `emails.services.62-session-upfront-paid` | `SessionUpfrontPaid` | Provider + Client |
| `emails.services.63-session-completion-paid` | `SessionCompletionPaid` | Provider + Client |
| `emails.services.64-session-refund-requested` | `SessionRefundRequested` | Provider |
| `emails.services.65-session-refund-approved` | `SessionRefundApproved` | Client |
| `emails.services.66-session-refund-denied` | `SessionRefundDenied` | Client |
| `emails.services.67-session-refund-escalated` | `SessionRefundEscalated` | Provider + Client |

Every template referencing money includes the standard direct-to-provider disclosure line.

Deprecated (kept as one-line file aliases for one release cycle):
- `emails.services.62-session-deposit-paid` → `62-session-upfront-paid`
- `emails.services.63-session-balance-paid` → `63-session-completion-paid`

---

## Appendix C — Migration From Rev 3

### Existing production data
- All existing `service_sessions` rows have `deposit_cents` / `balance_cents` populated for their historical 30/70 split.
- After Rev 4 migration runs: their `payment_structure = 'split'`, `upfront_percentage = 30`, `upfront_cents = deposit_cents`, `completion_cents = balance_cents`. A one-shot backfill migration handles this.
- All existing `services` rows get `default_payment_structure = 'split'`, `default_upfront_percentage = 30`, `default_terms_note = NULL`, `allow_completion_only = 0`.
- All existing `service_requests` rows get `proposed_payment_structure = 'split'`, `proposed_upfront_percentage = 30`, `proposed_terms_note = NULL`, `terms_source = 'provider_default'`.

### Backfill migration file
`2026_07_21_000004_backfill_payment_terms.php` — copies old values into new columns for all three tables. Idempotent.

### Route aliases retained (one release cycle)
- `/services/sessions/{session}/deposit` → same controller method as `/upfront`
- Event names `SessionDepositPaid` / `SessionBalancePaid` fire alongside new events for one cycle

### Copy audit checklist
Grep these in every `*.vue`, `*.blade.php`, `Events/*.php`, `Notifications/*.php` under Clinical Services scope:

```
"deposit" (case-insensitive) — replace with "upfront" unless in BP escrow context
"Balance Due"                — replace with "Completion Due" / "Balance Owed"
"held"                       — replace with "routed" / "paid directly"
"escrow"                     — must not appear in Clinical Services (BP module is separate)
"30%"                        — replace with dynamic ${upfront_percentage}%
"70%"                        — replace with dynamic ${100 - upfront_percentage}%
```

Grep scope for the audit:
```
resources/js/pages/provider/Services.vue
resources/js/pages/provider/Finances.vue
resources/js/pages/public/ProviderProfile.vue
resources/js/components/modals/ServiceRequestModal.vue
resources/js/components/modals/PayDepositModal.vue  (rename → PayUpfrontModal.vue)
resources/js/components/modals/PayBalanceModal.vue  (rename → PayCompletionModal.vue)
resources/js/components/modals/RequestRefundModal.vue
resources/js/components/modals/ReviewRefundRequestModal.vue
resources/js/components/ui/SessionInvoiceCard.vue
resources/js/components/ui/BookedSessionTable.vue
resources/js/components/ui/SessionTable.vue
resources/js/components/ui/SessionInvoiceModal.vue
resources/views/emails/services/*.blade.php
resources/views/emails/gaps/58-*.blade.php
resources/views/emails/gaps/59-*.blade.php
app/Services/AegisPdfService.php  (invoice PDF text)
app/Services/ServiceSessionPdfService.php
```

---

*Rev 4 — validated against live repo `main @ 65d3f47` on 2026-07-21. Supersedes Rev 3 (2026-07-09).*
*Companion doc: `CLINICAL_SERVICES_TERMS_TECHNICAL_PLAN.md` — execution roadmap for the Rev 3 → Rev 4 conversion.*
