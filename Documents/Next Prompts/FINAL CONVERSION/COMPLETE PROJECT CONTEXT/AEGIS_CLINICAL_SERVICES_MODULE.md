# Aegis — Clinical Services Module
## Complete Workflow Reference

**Stack:** PHP 8.2 / Laravel 12 / Vue 3 / Inertia.js v3 / Stripe Connect Express  
**Module path:** `/provider/services`  
**Last updated:** July 2026

---

## Table of Contents

1. [Overview](#1-overview)
2. [Access Requirements](#2-access-requirements)
3. [Database Schema](#3-database-schema)
4. [State Machines](#4-state-machines)
5. [Tab Structure — Services.vue](#5-tab-structure)
6. [Workflow A — Provider: Manage Listings](#6-workflow-a--provider-manage-listings)
7. [Workflow B — Provider: Handle Incoming Requests](#7-workflow-b--provider-handle-incoming-requests)
8. [Workflow C — Client: Browse & Request (Explore)](#8-workflow-c--client-browse--request-explore)
9. [Workflow D — Client: Track Outgoing Requests](#9-workflow-d--client-track-outgoing-requests)
10. [Workflow E — Payment: Deposit (30%)](#10-workflow-e--payment-deposit-30)
11. [Workflow F — Payment: Balance (70%)](#11-workflow-f--payment-balance-70)
12. [Workflow G — Refund Lifecycle](#12-workflow-g--refund-lifecycle)
13. [Workflow H — Dispute Escalation](#13-workflow-h--dispute-escalation)
14. [Notifications & Activity Logging](#14-notifications--activity-logging)
15. [Stripe Integration](#15-stripe-integration)
16. [Route Map](#16-route-map)
17. [Service Layer — Key Methods](#17-service-layer--key-methods)
18. [Demo Mode](#18-demo-mode)

---

## 1. Overview

The Clinical Services module enables practitioners to offer peer clinical services (supervision, consultation, training, coaching, practice continuity) to other practitioners on the Aegis platform. It operates on a **provider ↔ client** model where both parties are practitioners — one offers a service, the other books and pays for it.

**Two-charge payment model:**
- **Deposit (30%)** — charged at booking confirmation to hold the slot
- **Balance (70%)** — charged when the client confirms the session complete

Funds flow directly from the client's Stripe customer to the provider's Stripe Connect account via destination charges. Aegis never holds funds.

---

## 2. Access Requirements

### To OFFER services (provider side)
| Requirement | Value |
|---|---|
| Plan | Continuity Practice (`tier = 'practice'`) |
| Services Mode | `users.services_mode = 1` |
| Stripe Connect | Must complete Express onboarding to receive payouts |
| Middleware | `services.mode` gates listing CRUD, accepting requests, cancel, notes |

### To BOOK services (client side)
| Requirement | Value |
|---|---|
| Plan | Any authenticated practitioner (Access or Practice) |
| Stripe | Must have a saved payment method (`stripe_id` + `stripe_payment_method_id`) |
| Middleware | No `services.mode` required — deposit/balance/refund routes are outside this gate |

### Settings toggle
Services Mode is enabled in **Settings → My Services**. Enabling it sets `users.services_mode = 1` and makes the provider's public profile appear in the Explore grid.

---

## 3. Database Schema

### `services` table
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `practitioner_id` | CHAR(36) | FK → users |
| `title` | VARCHAR(191) | |
| `description` | TEXT | |
| `category` | VARCHAR(64) | `supervision \| consultation \| training \| coaching \| practice_continuity \| other` |
| `price_cents` | INTEGER | Always stored as cents |
| `price_type` | ENUM | `fixed \| hourly \| session \| inquiry` |
| `duration_min` | INTEGER | Session length in minutes |
| `format` | VARCHAR | `telehealth \| in_person \| both` |
| `availability` | VARCHAR | `open \| limited` |
| `availability_label` | VARCHAR | Custom label e.g. "3 spots left" |
| `status` | ENUM | `active \| draft \| paused \| archived` |
| `is_public` | TINYINT | 1 = appears in Explore |

### `service_requests` table
| Column | Type | Notes |
|---|---|---|
| `id` | CHAR(36) PK | |
| `service_id` | CHAR(36) | FK → services |
| `practitioner_id` | CHAR(36) | Provider (who owns the service) |
| `inquirer_id` | CHAR(36) | Client (who sent the request) |
| `message` | TEXT | Client's initial message |
| `status` | ENUM | `new \| accepted \| declined \| withdrawn` |
| `response_note` | TEXT | Provider's response message |
| `responded_at` | TIMESTAMP | |

### `service_sessions` table
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | |
| `service_request_id` | CHAR(36) | Source request |
| `service_id` | CHAR(36) | |
| `practitioner_id` | CHAR(36) | Provider |
| `client_id` | CHAR(36) | Client (bookee) |
| `status` | ENUM | `scheduled \| completed \| cancelled \| no_show` |
| `scheduled_at` | TIMESTAMP | |
| `completed_at` | TIMESTAMP | |
| `timezone` | VARCHAR | e.g. `America/New_York` |
| `amount_cents` | INTEGER | Agreed total (for backwards compat) |
| `original_amount_cents` | INTEGER | Listing price at time of request |
| `negotiated_amount_cents` | INTEGER\|NULL | Override if provider countered |
| `deposit_cents` | INTEGER | Actual 30% charged (0 until paid) |
| `deposit_charge_id` | VARCHAR(64) | Stripe PaymentIntent ID |
| `deposit_paid_at` | TIMESTAMP | |
| `balance_cents` | INTEGER | Actual 70% charged (0 until paid) |
| `balance_charge_id` | VARCHAR(64) | Stripe PaymentIntent ID |
| `balance_paid_at` | TIMESTAMP | |
| `total_refunded_cents` | INTEGER | Cumulative refunds issued |
| `payment_status` | ENUM | See state machine below |

### `session_refund_requests` table
| Column | Type | Notes |
|---|---|---|
| `id` | VARCHAR(36) PK | `srr_` + 12-char random |
| `session_id` | VARCHAR(36) | |
| `requested_by_id` | VARCHAR(36) | Client |
| `provider_id` | VARCHAR(36) | Provider |
| `reason` | ENUM | `session_did_not_occur \| provider_no_show \| quality_issue \| duplicate_charge \| session_cancelled_by_provider \| other` |
| `reason_detail` | TEXT | Optional extended explanation |
| `refund_type` | ENUM | `deposit_only \| balance_only \| full` |
| `amount_requested_cents` | INTEGER | |
| `status` | ENUM | See state machine below |
| `provider_response` | TEXT | Provider's approve/deny note |
| `responded_at` | TIMESTAMP | |
| `provider_deadline_at` | TIMESTAMP | `DISPUTE_RESPONDENT_REPLY_DAYS` from creation |
| `stripe_refund_id` | VARCHAR(64) | Refund ID for deposit charge |
| `stripe_refund_id_balance` | VARCHAR(64) | Refund ID for balance charge |
| `refunded_cents` | INTEGER | Actual amount refunded |
| `escalated_dispute_id` | VARCHAR(36) | FK → disputes if escalated |

---

## 4. State Machines

### Session lifecycle (`service_sessions.status`)
```
scheduled → completed   (client confirms; triggers balance payment)
scheduled → cancelled   (provider or client cancels)
scheduled → no_show     (admin/system marks)
```

### Payment lifecycle (`service_sessions.payment_status`)
```
unpaid
  └─→ deposit_paid      (30% charged; session confirmed)
        └─→ paid              (70% balance charged; fully complete)
        └─→ refunded          (deposit refunded after client request)
        └─→ partially_refunded

paid / partially_refunded
  └─→ refunded          (full refund issued)
  └─→ partially_refunded  (partial refund issued)
```

**UI labels:**
| Value | Badge label | Badge colour |
|---|---|---|
| `unpaid` | Deposit Due | Gold |
| `deposit_paid` | Balance Due | Blue |
| `paid` | Paid | Green |
| `refunded` | Refunded | Neutral |
| `partially_refunded` | Partially Refunded | Neutral |

### Refund request lifecycle (`session_refund_requests.status`)
```
pending_review
  ├─→ approved          (provider approves; Stripe refund issued immediately)
  ├─→ denied            (provider denies; client may escalate)
  └─→ auto_approved     (future: system auto-approves overdue requests)

denied
  └─→ escalated_to_dispute  (client escalates; formal Dispute record created)
```

### Available refund types by payment status
| `payment_status` | Available refund types |
|---|---|
| `deposit_paid` | Deposit only (30%) |
| `paid` | Deposit only, Balance only, Full refund |
| `partially_refunded` | Deposit only, Balance only |
| `unpaid` | None — nothing charged yet |

---

## 5. Tab Structure

`Services.vue` at `/provider/services` has 5 sidebar-nav tabs:

| Tab key | Label | Who uses it | Contents |
|---|---|---|---|
| `listings` | My Listings | Provider | My published service listings |
| `requests` | Service Requests | Provider | Incoming requests from other practitioners |
| `bookings` | Bookings & Sessions | Provider | Sessions I am running for others |
| `outgoing` | My Requests | Client | Sessions I booked as client of others |
| `settings` | Settings | Both | Links to Settings → My Services |

---

## 6. Workflow A — Provider: Manage Listings

### 6.1 Prerequisites
- `tier = 'practice'` AND `services_mode = 1`
- Stripe Connect Express onboarding completed

### 6.2 Create a listing
**UI:** Services tab → "New Service" button → Create Service modal  
**Route:** `POST /provider/services` → `ServicesController::store`  
**Validation:**
```
title         required, max 200
description   optional, max 5000
category      optional: supervision|consultation|training|coaching|practice_continuity|other
price_cents   optional integer (stored as cents)
price_type    fixed|hourly|session|inquiry
duration_min  5–480 minutes
format        telehealth|in_person|both
availability  open|limited
is_public     boolean (shows in Explore grid)
```
**Service layer:** `ServiceService::create()` → writes `services` row, fires `service_created` activity log for provider.

### 6.3 Edit a listing
**Route:** `PUT /provider/services/{service}` → `ServicesController::update`  
**Allowed updates:** title, description, category, price_cents, price_type, duration_min, format, availability, availability_label, status, is_public  
**Activity log:** `service_updated` for provider

### 6.4 Publish / Pause / Archive
- **Publish:** Sets `status = active`, `is_public = 1`
- **Pause:** Sets `status = paused` (remains in listings, not in Explore)
- **Archive:** Sets `status = archived` (hidden everywhere) — `service_archived` log

### 6.5 Listing status display
| Status | Public | In Explore | Badge |
|---|---|---|---|
| `active` | Yes (if is_public) | Yes | Green |
| `draft` | No | No | Neutral |
| `paused` | No | No | Gold |
| `archived` | No | No | Neutral |

---

## 7. Workflow B — Provider: Handle Incoming Requests

### 7.1 Request arrives
When another practitioner submits a request for your service, it appears in the **Service Requests** tab.

### 7.2 Accept a request
**UI:** Service Requests tab → "Accept" button on request card → Accept modal  
**Route:** `POST /provider/services/{service}/requests/{serviceRequest}/accept` → `ServicesController::acceptRequest`  
**Request data:**
```
session_date           YYYY-MM-DD
session_time           HH:MM (default 10:00)
timezone               e.g. America/New_York
negotiated_amount_cents  optional (override listing price)
```
**Service layer flow:**
1. `ServiceService::acceptRequest()` → marks request `status = accepted`
2. Calls `bookSession()` → creates `service_sessions` row with:
   - `payment_status = unpaid`
   - `original_amount_cents` = listing price
   - `negotiated_amount_cents` = provider override (null if using listing price)
   - `amount_cents` = agreed total
   - `deposit_cents = 0`, `balance_cents = 0`
3. Activity log: `service_request_accepted` for provider (log) + client (notification)
4. Event: `ServiceRequestResponded` → email to client

### 7.3 Decline a request
**Route:** `POST /provider/services/{service}/requests/{serviceRequest}/decline`  
**Service layer:** `declineRequest()` → marks `status = declined`, logs `service_request_declined`  
**Email:** Client notified with response note

### 7.4 Counter-offer (negotiate price)
During accept, provider can set `negotiated_amount_cents` to override the listing price. The client sees the negotiated amount on their deposit confirmation.

### 7.5 Cancel a session (provider)
**Route:** `POST /provider/services/sessions/{session}/cancel`  
**Validation:** `reason` (required), `note` (optional)  
**Service layer:** `cancelSession()` → sets `status = cancelled`  
**Activity log:** `session_cancelled` for provider, `session_cancelled_by_other` (warning) for client

---

## 8. Workflow C — Client: Browse & Request (Explore)

### 8.1 Browse the Explore grid
**Tab:** Browse Services  
**Route:** `GET /provider/services` (initial SSR) + `GET /provider/services/explore?page=N` (infinite scroll JSON)  
**Filters:** Category, Format, Availability  
**Excludes:** Provider's own listings (cannot book your own service)

Each card shows:
- Service title + description (2 lines)
- Category badge, availability badge
- Duration pill, format pill  
- Provider name (linked to `/public/provider/{slug}`)
- Stripe Connect indicator dot (green = connected)
- Price + "Request" button

### 8.2 Submit a request from Explore
**UI:** Click "Request" on any card → `ServiceRequestModal` opens  
**Modal wiring:** Same centralized `ServiceRequestModal` used in Network page (`ref + preselect`)  
**Fields:**
```
service      prefilled (readonly if from Explore)
provider     readonly
date         required (Vuelidate: required, min today)
time         Morning|Afternoon|Evening|Flexible
timezone     auto-detected, editable
format       Virtual|In-Person|No preference
message      optional
```
**Route:** `POST /provider/services/explore/request` → `ServicesController::storeExploreRequest`  
**Service layer:** `ServiceService::submitRequest()` → creates `service_requests` row  
**Activity log:** `service_request_sent` for client, `service_request_received` (notification) for provider  
**Email:** Provider receives `58-service-inquiry-received`

### 8.3 Request from a provider's public profile
Same `ServiceRequestModal`, but uses:  
**Route:** `POST /public/profiles/{user}/service-request` → `PublicProfileController`

---

## 9. Workflow D — Client: Track Outgoing Requests

**Tab:** My Requests → Section A (My Booked Sessions) + Section B (My Service Requests)

### Section A — My Booked Sessions
Sessions where you are the client (accepted requests that became sessions).  
Shows `SessionInvoiceCard` for each session with:
- Service title, provider name
- Session date + status badge + payment status badge
- Pay Deposit button (when `payment_status = unpaid`)
- Pay Balance button (when `payment_status = deposit_paid` AND session `status = scheduled`)
- Request Refund button (when payment has been made)

### Section B — My Service Requests
Pending/responded/withdrawn requests you sent.  
Shows compact `orq-card` with provider avatar, service, status badge, relative time.  
Click → detail modal showing:
- Full provider info + status
- Service, type, date sent, responded date
- Your original message
- Provider's response (green block if responded)
- Gold "Awaiting response" banner if still pending
- "Withdraw" button in footer (only when `status = new`)

### 9.1 Withdraw a request
**Route:** `DELETE /provider/services/requests/{serviceRequest}/withdraw`  
**Guard:** Only when `status = new`  
**Service layer:** `withdrawRequest()` → `status = withdrawn`, logs `service_request_withdrawn`

---

## 10. Workflow E — Payment: Deposit (30%)

### 10.1 Prerequisites
- Session `status = scheduled` AND `payment_status = unpaid`
- Client is the `client_id` on the session
- Client has `stripe_id` (Stripe customer) + `stripe_payment_method_id` (saved card)
- Provider has `stripe_account_id` (Stripe Connect account)

### 10.2 UI trigger
**My Requests tab → Session card → "Pay Deposit" button → `PayDepositModal`**

Modal shows:
- Service title + provider name
- Agreed total (negotiated or listing price)
- 30% deposit amount = `floor(agreed_amount_cents × 0.30)`
- Remaining 70% balance note
- "I agree to the session terms" checkbox (required)
- Confirm button

### 10.3 Backend flow
**Route:** `POST /provider/services/sessions/{session}/deposit`  
**Validation:** `agree_terms: required|accepted`

```
ServicesController::payDeposit()
  └─ ServiceService::payDeposit()
       └─ Guards:
            • client_id === auth user
            • payment_status === unpaid
            • status === scheduled
       └─ PayoutService::chargeSessionDeposit()
            ├─ isDemo()? → stub pi_demo_dep_* → update DB → logDepositActivity(stub: true)
            ├─ !stripe.secret? → stub pi_stub_dep_* → update DB → logDepositActivity(stub: true)
            └─ Live Stripe:
                 → paymentIntents->create(amount, destination: provider.stripe_account_id)
                 → update payment record (status, stripe_payment_intent_id, paid_at)
                 → update session (deposit_cents, deposit_charge_id, deposit_paid_at)
                 → payment_status = 'deposit_paid'
                 → logDepositActivity(stub: false)
       └─ event(SessionDepositPaid) → email + activity notification
```

### 10.4 DB updates on success
| Column | Value set |
|---|---|
| `deposit_cents` | Actual deposit amount |
| `deposit_charge_id` | Stripe PaymentIntent ID |
| `deposit_paid_at` | Timestamp |
| `payment_status` | `deposit_paid` |

### 10.5 `PractitionerPayment` record created
| Field | Value |
|---|---|
| `kind` | `service_session_deposit` |
| `amount_cents` | 30% of agreed total |
| `status` | `paid` (demo/stub) or `paid/pending` (live) |

---

## 11. Workflow F — Payment: Balance (70%)

### 11.1 Prerequisites
- Session `status = scheduled` AND `payment_status = deposit_paid`
- Client confirms session occurred

### 11.2 UI trigger
**My Requests tab → Session card → "Pay Balance & Confirm" button → `PayBalanceModal`**

Modal shows:
- Confirmation that the session is complete
- Balance amount = `agreed_amount_cents - deposit_cents`
- Warning: action cannot be undone

### 11.3 Backend flow
**Route:** `POST /provider/services/sessions/{session}/complete`

```
ServicesController::completeSession()
  └─ ServiceService::completeSession()
       └─ Guard: client_id === actorId, status === scheduled
       └─ Updates session: status = completed, completed_at = now()
       └─ Checks payment_status:
            ├─ deposit_paid → PayoutService::chargeSessionBalance()
            │    └─ Same demo/stub/live detection as deposit
            │    └─ Updates: balance_cents, balance_charge_id, balance_paid_at
            │    └─ payment_status = 'paid'
            │    └─ event(SessionBalancePaid)
            └─ unpaid (legacy) → single charge path via releaseServiceSessionPayout()
       └─ Activity logs: session_completed (notification → provider), session_payment_sent (log → client)
       └─ event(SessionCompleted) → email to provider
```

---

## 12. Workflow G — Refund Lifecycle

### 12.1 Prerequisites for opening a refund
- Client is `client_id` on the session
- `payment_status.depositCharged()` is true (at least deposit was paid)
- No existing pending refund request on this session
- Selected refund type must be available for current payment status (see §4)

### 12.2 Open a refund request
**UI:** My Requests → Session card → "Request Refund" → `RequestRefundModal`

Modal shows available refund types based on `payment_status`:
- **Deposit only (30%)** — if deposit paid but session not yet complete
- **Balance only (70%)** — if session fully paid
- **Full refund (100%)** — if session fully paid

Plus reason dropdown + optional detail text.

**Route:** `POST /provider/services/sessions/{session}/refund-requests`  
**Validation:** `reason`, `refund_type` required; `reason_detail` optional

```
SessionRefundService::open()
  └─ Guards: client ownership, payment status eligible, no pending request
  └─ Validates refund_type against payment_status
  └─ Calculates amount_requested_cents:
       deposit_only  → deposit_cents (or expected_deposit_cents)
       balance_only  → balance_cents (or expected_balance_cents)
       full          → deposit_cents + balance_cents
  └─ Creates session_refund_requests row:
       status = pending_review
       provider_deadline_at = now() + DISPUTE_RESPONDENT_REPLY_DAYS (default 5)
  └─ Activity logs: refund_requested (log → client), refund_request_received (notification → provider)
  └─ event(SessionRefundRequested) → email to provider
```

### 12.3 Provider reviews a refund request
**UI:** Bookings tab → Session card → alert icon → `ReviewRefundRequestModal` OR refund alert bar at top

Modal shows:
- Client name + session details
- Amount requested + refund type
- Client's reason + detail
- Deadline countdown
- Approve / Deny buttons

#### Approve
**Route:** `POST /provider/services/refund-requests/{refund}/approve`

```
SessionRefundService::approve()
  └─ Guard: provider_id === auth user, status.isActionable()
  └─ Calls PayoutService::refundSessionCharge() for each charge type:
       deposit_only  → refund deposit_charge_id
       balance_only  → refund balance_charge_id
       full          → refund both (two separate Stripe refund calls)
  └─ All refunds use reverse_transfer: true (funds pulled from provider's Connect account)
  └─ Updates refund request: status = approved, stripe_refund_id(s), refunded_cents
  └─ Updates session: total_refunded_cents += refunded, payment_status → refunded|partially_refunded
  └─ Activity logs: refund_approved → client (notification), refund_issued → provider (log)
  └─ event(SessionRefundApproved) → email to client
```

#### Deny
**Route:** `POST /provider/services/refund-requests/{refund}/deny`  
**Validation:** `note: required, max 1000`

```
SessionRefundService::deny()
  └─ Guard: provider ownership, status.isActionable()
  └─ Updates refund request: status = denied, provider_response = note
  └─ Activity logs: refund_denied → client (warning notification), refund_denied_by_you → provider (log)
  └─ event(SessionRefundDenied) → email to client (includes escalation option)
```

### 12.4 After denial — client's options
- **Accept** the denial (no further action)
- **Escalate** to a formal dispute (see §13)

---

## 13. Workflow H — Dispute Escalation

### 13.1 Prerequisites
- Refund request `status = denied`
- Client is `requested_by_id`

### 13.2 Escalate
**UI:** My Requests → Session → "Escalate" button (shown when refund denied)  
**Route:** `POST /provider/services/refund-requests/{refund}/escalate`

```
SessionRefundService::escalate()
  └─ Guard: client ownership, status.canEscalate()
  └─ DisputeService::open():
       subject_type = 'session'
       subject_id   = session.id
       claimant     = client
       respondent   = provider
       reason       = ServiceNotDelivered (admin can reassign)
       description  = escalation context + original reason + amount + provider response
  └─ Links dispute back: refund_request.escalated_dispute_id = dispute.id
  └─ refund_request.status = escalated_to_dispute
  └─ Activity logs: refund_escalated → client (log), dispute_opened → provider (Critical notification)
  └─ event(SessionRefundEscalated) → email to both parties
```

### 13.3 Dispute handling
Once escalated, the dispute enters the standard Aegis dispute system:
- Visible in both parties' **Finances** tab → Disputes section
- Provider has `DISPUTE_RESPONDENT_REPLY_DAYS` (default 5) to respond
- Admin reviews via Admin portal → Disputes queue
- Resolution issued by admin → `DisputeResolved` event

---

## 14. Notifications & Activity Logging

All activity is written via `ActivityService::log()` with `module = 'services'` and visible at `/provider/activity?module=services`.

### Entry types
- `entry_type = 'log'` → appears in "My Activity" tab (actor's own history)
- `entry_type = 'notification'` → appears in "Notifications" tab (received by other party)

### Complete event matrix

| Action | Actor log | Other-party notification | Email |
|---|---|---|---|
| Service created | ✅ provider | — | — |
| Service updated | ✅ provider | — | — |
| Service archived | ✅ provider | — | — |
| Request sent | ✅ client | ✅ provider | 58-service-inquiry-received |
| Request accepted | ✅ provider | ✅ client | 59-service-inquiry-responded |
| Request declined | ✅ provider | ✅ client | 59-service-inquiry-responded |
| Request withdrawn | ✅ client | — | — |
| Deposit paid | ✅ client | ✅ provider | 62-session-deposit-paid (both) |
| Balance paid | ✅ client | ✅ provider | 63-session-balance-paid |
| Session completed | ✅ client | ✅ provider | 61-session-completed |
| Session cancelled | ✅ actor | ✅ other party (Warning) | 60-session-cancelled |
| Session notes saved | ✅ provider | — | — |
| Payout failed | — | ✅ provider (Critical) | — |
| Refund requested | ✅ client | ✅ provider (Warning) | 64-session-refund-requested |
| Refund approved | ✅ provider | ✅ client | 65-session-refund-approved |
| Refund denied | ✅ provider | ✅ client (Warning) | 66-session-refund-denied |
| Refund escalated | ✅ client | ✅ provider (Critical) | 67-session-refund-escalated (both) |

### Activity log icon & badge
All `module = 'services'` events display with:
- Icon: `calendar`
- Badge label: `My Services`
- Badge class: `services`

---

## 15. Stripe Integration

### Architecture
- **Subscriptions** (Cashier): Provider → Aegis platform (monthly/annual plan)
- **Destination charges** (Connect): Client → Provider, via Aegis as platform

Aegis **never holds funds**. All session charges are destination charges that transfer immediately to the provider's Stripe Connect account.

### Charge flow (simplified)
```
Client card (stripe_payment_method_id)
  → Stripe PaymentIntent
    → transfer_data.destination: provider.stripe_account_id
    → on_behalf_of: provider.stripe_account_id
  → Funds land in provider's Stripe Connect balance
```

### Refund flow
```
Aegis calls refunds->create(payment_intent, amount, reverse_transfer: true)
  → Stripe pulls funds FROM provider's Connect account
  → Returns funds to client's original payment method
  → Aegis platform balance is never touched
```

### Demo detection (`isDemo()`)
Checked before every Stripe call. Returns `true` if ANY of:
- `client.stripe_id` starts with `cus_demo_`
- `client.stripe_payment_method_id` starts with `pm_demo_`
- `provider.stripe_account_id` starts with `acct_demo_`

When `true`: charge is stubbed with `pi_demo_dep_*` / `pi_demo_bal_*` ID, DB is updated identically to a real charge, no Stripe API call is made.

### Stub detection (no Stripe secret)
If `config('services.stripe.secret')` is null/empty:
- Charge stubbed with `pi_stub_dep_*` / `pi_stub_bal_*`
- Payment status set to `pending` (not `paid` — real charges confirm via webhook)

### PaymentIntent metadata
Every charge includes:
```json
{
  "payment_id": "pp_...",
  "session_id": "ss_...",
  "charge_type": "session_deposit" | "session_balance",
  "practitioner_id": "p_...",
  "client_id": "p_...",
  "agreed_total": 22000
}
```

---

## 16. Route Map

### Outside `services.mode` middleware (any authenticated practitioner)
| Method | Path | Controller method | Name |
|---|---|---|---|
| POST | `/services/sessions/{session}/deposit` | `payDeposit` | `services.session.deposit` |
| POST | `/services/sessions/{session}/complete` | `completeSession` | `services.session.complete` |
| GET | `/services/sessions/{session}/invoice` | `downloadInvoice` | `services.session.invoice` |
| GET | `/services/explore` | `explore` (JSON) | `services.explore` |
| POST | `/services/explore/request` | `storeExploreRequest` | `services.explore.request` |
| POST | `/services/sessions/{session}/refund-requests` | `storeRefundRequest` | `services.session.refund.store` |
| POST | `/services/refund-requests/{refund}/escalate` | `escalateRefundRequest` | `services.refund.escalate` |
| POST | `/services/refund-requests/{refund}/approve` | `approveRefundRequest` | `services.refund.approve` |
| POST | `/services/refund-requests/{refund}/deny` | `denyRefundRequest` | `services.refund.deny` |

### Inside `services.mode` middleware (Practice tier + services_mode = 1)
| Method | Path | Controller method | Name |
|---|---|---|---|
| GET | `/services` | `index` | `services.index` |
| POST | `/services` | `store` | `services.store` |
| PUT | `/services/{service}` | `update` | `services.update` |
| DELETE | `/services/{service}` | `destroy` | `services.destroy` |
| POST | `/services/{service}/requests/{serviceRequest}/accept` | `acceptRequest` | `services.request.accept` |
| POST | `/services/{service}/requests/{serviceRequest}/decline` | `declineRequest` | `services.request.decline` |
| DELETE | `/services/requests/{serviceRequest}/withdraw` | `withdrawRequest` | `services.request.withdraw` |
| POST | `/services/sessions/{session}/cancel` | `cancelSession` | `services.session.cancel` |
| POST | `/services/sessions/{session}/notes` | `saveSessionNotes` | `services.session.notes` |

---

## 17. Service Layer — Key Methods

### `ServiceService`
| Method | Purpose |
|---|---|
| `create(User, array)` | Create new listing |
| `update(Service, array)` | Update listing fields |
| `archive(Service)` | Archive listing |
| `submitRequest(Service, User, array)` | Client sends request |
| `acceptRequest(ServiceRequest, array)` | Provider accepts + books session |
| `declineRequest(ServiceRequest, string?)` | Provider declines |
| `bookSession(ServiceRequest, array)` | Internal: creates session row |
| `payDeposit(ServiceSession, User)` | Initiates 30% charge |
| `completeSession(ServiceSession, string)` | Confirms + initiates 70% charge |
| `cancelSession(ServiceSession, array)` | Cancel session |
| `saveSessionNotes(ServiceSession, array)` | Save session notes |
| `withdrawRequest(ServiceRequest, string)` | Client withdraws pending request |
| `shapeForListing(Service)` | Shape for Listings tab |
| `shapeForExplore(Service)` | Shape for Explore grid |
| `shapeRequest(ServiceRequest)` | Shape for incoming requests tab |
| `shapeOutgoingRequest(ServiceRequest)` | Shape for My Requests tab |
| `shapeSession(ServiceSession)` | Shape for Bookings tab (provider view) |
| `shapeClientSession(ServiceSession)` | Shape for My Requests tab (client view) |
| `statsForPractitioner(User)` | Hero stat chips |
| `getForExplore(array, string, int)` | Paginated explore results |

### `PayoutService`
| Method | Purpose |
|---|---|
| `chargeSessionDeposit(session, provider, client)` | Issues 30% destination charge |
| `chargeSessionBalance(session, provider, client)` | Issues 70% destination charge |
| `refundSessionCharge(intentId, cents, metadata)` | Issues Stripe refund with `reverse_transfer: true` |

### `SessionRefundService`
| Method | Purpose |
|---|---|
| `open(ServiceSession, User, array)` | Client opens refund request |
| `approve(SessionRefundRequest, User)` | Provider approves, issues Stripe refund |
| `deny(SessionRefundRequest, User, string)` | Provider denies |
| `escalate(SessionRefundRequest, User)` | Client escalates to dispute |

---

## 18. Demo Mode

All seeded demo users have prefixed IDs that trigger the demo stub path:

| User | stripe_id | stripe_payment_method_id | stripe_account_id |
|---|---|---|---|
| `p_sarah` | `cus_demo_sarah` | `pm_demo_visa_sarah` | `acct_demo_sarah` |
| `p_maria` | `cus_demo_maria` | `pm_demo_visa_maria` | `acct_demo_maria` |
| `p_david` | `cus_demo_david` | `pm_demo_david_visa` | `acct_demo_david` |

**In demo mode:**
- Charges are stubbed with `pi_demo_dep_*` or `pi_demo_bal_*` IDs
- DB is updated identically to real charges
- Activity logs and emails fire normally
- Nothing appears in Stripe dashboard
- `(demo)` suffix appears in activity log description

**To test real Stripe charges:**
Complete onboarding with a real test email, attach a real test card (Stripe test card number 4242 4242 4242 4242), and ensure the provider has completed Stripe Connect Express onboarding. Then charges will appear at: `https://dashboard.stripe.com/test/payments`

---

## Appendix A — Computed Attributes (ServiceSession model)

| Attribute | Formula | Purpose |
|---|---|---|
| `agreed_amount_cents` | `negotiated_amount_cents ?? amount_cents ?? 0` | Source of truth for all price calculations |
| `expected_deposit_cents` | `floor(agreed_amount_cents × 0.30)` | What 30% deposit should be |
| `expected_balance_cents` | `agreed_amount_cents - expected_deposit_cents` | What 70% balance should be |
| `remaining_cents` | `agreed_amount_cents - deposit_cents - balance_cents + total_refunded_cents` | What client still owes |
| `invoice_number` | `SES-YYYY-MM-{id[0:8]}` | Human-readable invoice reference |
| `has_pending_refund_request` | DB query | Shows alert badge on session card |

## Appendix B — Email Templates

| Template | Trigger event | Recipient(s) |
|---|---|---|
| `emails.gaps.58-service-inquiry-received` | `ServiceRequestSubmitted` | Provider |
| `emails.gaps.59-service-inquiry-responded` | `ServiceRequestResponded` | Client |
| `emails.services.60-session-cancelled` | `SessionCancelled` | Other party |
| `emails.services.61-session-completed` | `SessionCompleted` | Provider |
| `emails.services.62-session-deposit-paid` | `SessionDepositPaid` | Provider + Client |
| `emails.services.63-session-balance-paid` | `SessionBalancePaid` | Provider |
| `emails.services.64-session-refund-requested` | `SessionRefundRequested` | Provider |
| `emails.services.65-session-refund-approved` | `SessionRefundApproved` | Client |
| `emails.services.66-session-refund-denied` | `SessionRefundDenied` | Client |
| `emails.services.67-session-refund-escalated` | `SessionRefundEscalated` | Provider + Client |

All email notifications are gated by `notify_email` preference via `NotificationService::shouldSend()`.

---

*Document generated July 2026. Reflects repo state main@current.*
