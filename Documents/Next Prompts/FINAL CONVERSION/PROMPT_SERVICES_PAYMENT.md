# Aegis — Services Module: Payment Workflow Audit & Integration

## Objective

Audit and complete the payment workflow for `/provider/services` — the Services module where Providers offer sessions to Clients (other Practitioners). The goal is end-to-end Stripe payment from Client's saved card to Provider's Stripe Connect account, with full activity logging, webhook confirmation, and demo stub mode — matching the architecture already built for Provider→BP contract payments.

---

## Step 0 — Fresh clone + full read

```bash
cd /home/claude && rm -rf aegis
git clone --depth=1 https://github.com/rehanurrashid/aegis-laravel.git aegis
cd aegis && git log -1 --oneline

# Read all relevant files in full
cat resources/js/pages/provider/SupportServices.vue   # ignore — wrong page
cat resources/js/pages/provider/Services.vue

cat app/Http/Controllers/Provider/ServicesController.php
cat app/Services/ServiceService.php
cat app/Services/PayoutService.php

# Models
cat app/Models/Service.php
cat app/Models/ServiceRequest.php
cat app/Models/ServiceSession.php
cat app/Models/PractitionerPayment.php

# Migrations
find database/migrations -name "*service*" | sort | xargs cat
find database/migrations -name "*practitioner_payment*" | sort | xargs cat
find database/migrations -name "*stripe_payment*" | sort | xargs cat

# Seeder
cat database/seeders/ServiceSeeder.php

# Stripe columns on users
grep -n "stripe_id\|stripe_payment_method\|stripe_account\|stripe_connected" \
  app/Models/User.php database/migrations/2024_01_01_000001_create_users_table.php \
  database/migrations/2024_01_02_000004_add_stripe_payment_fields.php 2>/dev/null

# Routes
grep -n "service" routes/web.php

# Events
find app/Events -name "*Service*" -o -name "*Session*" | sort | xargs cat 2>/dev/null || echo "none"

# StripeEventListener
cat app/Listeners/StripeEventListener.php

# Existing payout pattern (reference — already working for BP contracts)
grep -n "chargeProviderToBp\|isDemoProvider\|isDemoBp\|stripe_payment_intent\|transfer_data" \
  app/Services/PayoutService.php
```

---

## Step 1 — State audit (output before any code)

```markdown
### Current payment flow state

#### service_sessions table
- Has `amount_cents` column: ✅/❌
- Has `stripe_payment_intent_id` column: ✅/❌
- Has `client_stripe_id` column: ✅/❌ (needed — client pays)
- Has `practitioner_stripe_account` snapshot: ✅/❌

#### PractitionerPayment model
- `stripe_payment_intent_id` column: ✅/❌
- `stripe_transfer_id` column: ✅/❌
- `session_id` FK: ✅/❌

#### ServicesController::completeSession()
- Currently calls PayoutService::releaseServiceSessionPayout(): ✅/❌
- Charges client's card (destination charge): ✅/❌
- Uses stub/demo detection: ✅/❌

#### PayoutService::releaseServiceSessionPayout()
- Uses platform transfer (old pattern): ✅/❌
- Uses destination charge from client card (correct pattern): ✅/❌
- Has demo stub detection: ✅/❌

#### StripeEventListener
- Handles payment_intent.succeeded for sessions: ✅/❌
- Handles payment_intent.payment_failed for sessions: ✅/❌

#### User columns
- Client has stripe_id (customer): ✅/❌
- Client has stripe_payment_method_id (saved card): ✅/❌
- Provider has stripe_account_id (receives payment): ✅/❌

#### Demo seed data
- p_sarah stripe_id set: ✅/❌
- p_sarah stripe_payment_method_id set: ✅/❌
- p_david stripe_id set: ✅/❌ (client in demo sessions)
- p_maria stripe_account_id set: ✅/❌ (provider in demo sessions)

### Missing routes
| Route | Exists? |
|---|---|
| provider.services.sessions.complete | ✅/❌ |
| provider.services.sessions.pay | ✅/❌ |

### Missing migrations
| Column | Table | Exists? |
|---|---|---|
| stripe_payment_intent_id | service_sessions | ✅/❌ |
| session_id | practitioner_payments | ✅/❌ |
```

---

## Step 2 — Correct architecture to implement

### Payment flow for Services module

```
Client (inquirer/other Practitioner) books a session
    ↓
Session scheduled (status = scheduled)
    ↓
Session completed — client confirms via UI
    ↓
Aegis charges Client's saved card (stripe_id + stripe_payment_method_id)
via PaymentIntent with transfer_data.destination = Provider's stripe_account_id
    ↓
Aegis Platform ($0 net — pass-through)
    ↓
Provider's Stripe Connect account receives 100% of session fee
    ↓
Webhook payment_intent.succeeded → mark session paid + PractitionerPayment paid
```

### Key rule
- **Client pays** (the person who booked the session — `service_sessions.client_id`)
- **Provider receives** (the practitioner who owns the service — `service_sessions.practitioner_id`)
- Same destination charge pattern as Provider→BP contract payments
- Same demo stub detection: `cus_demo_*` / `pm_demo_*` / `acct_demo_*` bypass Stripe

---

## Step 3 — Backend changes

### 3A — Migration (if columns missing)

```php
// Add stripe_payment_intent_id to service_sessions
Schema::table('service_sessions', function (Blueprint $table) {
    if (!Schema::hasColumn('service_sessions', 'stripe_payment_intent_id')) {
        $table->string('stripe_payment_intent_id', 64)->nullable()->after('amount_cents');
    }
    if (!Schema::hasColumn('service_sessions', 'paid_at')) {
        $table->timestamp('paid_at')->nullable()->after('completed_at');
    }
});

// Add session_id to practitioner_payments if missing
Schema::table('practitioner_payments', function (Blueprint $table) {
    if (!Schema::hasColumn('practitioner_payments', 'session_id')) {
        $table->string('session_id', 36)->nullable()->after('practitioner_id');
    }
});
```

### 3B — PayoutService::releaseServiceSessionPayout()

Replace the old platform-transfer pattern with the destination charge pattern, matching `chargeProviderToBp()`:

```php
public function releaseServiceSessionPayout(
    PractitionerPayment $payment,
    User $practitioner,  // receives payment
    User $client         // pays — has stripe_id + stripe_payment_method_id
): PractitionerPayment {

    // Demo detection — same pattern as chargeProviderToBp()
    $isDemoClient = str_starts_with((string) $client->stripe_id, 'cus_demo_')
        || str_starts_with((string) $client->stripe_payment_method_id, 'pm_demo_');
    $isDemoPractitioner = str_starts_with((string) $practitioner->stripe_account_id, 'acct_demo_');

    if ($isDemoClient || $isDemoPractitioner) {
        $payment->update([
            'status'                   => PractitionerPaymentStatus::Paid->value,
            'stripe_payment_intent_id' => 'pi_demo_' . Str::lower(Str::random(16)),
            'paid_at'                  => now(),
        ]);
        // activity log...
        return $payment->fresh();
    }

    // Guards
    if (!$client->stripe_id || !$client->stripe_payment_method_id) {
        throw new \RuntimeException(
            'Client has no payment method on file. They must add a card before sessions can be charged.'
        );
    }
    if (!$practitioner->stripe_account_id || !preg_match('/^acct_[a-zA-Z0-9]{16,}$/', $practitioner->stripe_account_id)) {
        throw new \RuntimeException(
            'Provider has not connected a Stripe account. Complete payment setup in Settings.'
        );
    }

    // Destination charge: client card → provider connected account
    try {
        $stripe = new StripeClient(config('services.stripe.secret'));
        $intent = $stripe->paymentIntents->create([
            'amount'               => $payment->amount_cents,
            'currency'             => strtolower($payment->currency ?? 'usd'),
            'customer'             => $client->stripe_id,
            'payment_method'       => $client->stripe_payment_method_id,
            'confirm'              => true,
            'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
            'transfer_data'        => ['destination' => $practitioner->stripe_account_id],
            'on_behalf_of'         => $practitioner->stripe_account_id,
            'description'          => 'Session payment — Aegis',
            'metadata'             => [
                'payment_id'       => $payment->id,
                'practitioner_id'  => $practitioner->id,
                'client_id'        => $client->id,
            ],
        ]);
        $payment->update([
            'status'                   => $intent->status === 'succeeded'
                ? PractitionerPaymentStatus::Paid->value
                : PractitionerPaymentStatus::Pending->value,
            'stripe_payment_intent_id' => $intent->id,
            'paid_at'                  => $intent->status === 'succeeded' ? now() : null,
        ]);
    } catch (\Throwable $e) {
        $payment->update(['status' => PractitionerPaymentStatus::Failed->value]);
        // activity log critical to practitioner...
        throw new \RuntimeException('Session payment failed: ' . $e->getMessage());
    }

    // activity log for both parties...
    return $payment->fresh();
}
```

### 3C — ServicesController::completeSession()

The `completeSession` controller method must:
1. Mark session `status = completed`, set `completed_at = now()`
2. Create a `PractitionerPayment` record (kind = `session`, amount from `session.amount_cents`)
3. Load the `client` user (from `session.client_id`)
4. Call `PayoutService::releaseServiceSessionPayout($payment, $practitioner, $client)`
5. Return `back()->with('success', '...')` or `withErrors` on failure

### 3D — StripeEventListener

Add `payment_intent.succeeded` handler for sessions (the existing handler added for BP contracts already handles this via `BpPayout::where('stripe_payment_intent_id')` lookup — but sessions use `PractitionerPayment` table, so add a second lookup path):

```php
// In handlePaymentIntentSucceeded() — add after BpPayout lookup:
if (!$payout) {
    // Check if it's a service session payment
    $practitionerPayment = \App\Models\PractitionerPayment::where('stripe_payment_intent_id', $intentId)->first();
    if ($practitionerPayment) {
        $practitionerPayment->update(['status' => 'paid', 'paid_at' => now()]);
        // activity log...
        return;
    }
}
```

### 3E — UserSeeder

Ensure demo users involved in service sessions have `stripe_id` and `stripe_payment_method_id`:
- `p_david` (client in demo sessions) needs `stripe_id = 'cus_demo_david'`, `stripe_payment_method_id = 'pm_demo_visa_david'`
- `p_sarah` already seeded
- `p_maria` (provider in demo sessions) needs `stripe_account_id = 'acct_demo_maria'` (already set)

---

## Step 4 — Frontend changes (Services.vue)

### Completion flow guard

The "Complete Session" button (client-side confirm) should:
1. Show a confirmation modal with session amount
2. On confirm → POST to `provider.services.sessions.complete`
3. `onSuccess` → `toast.success('Session completed. Payment released to provider.')`
4. `onError(e)` → `toast.error(Object.values(e)[0] || 'Could not complete session.')` — surfaces real backend error

### Payment status display

In "My Booked Sessions" and session history sections:
- Show payment status badge per session: `pending` (gold) / `paid` (green) / `failed` (red)
- If `paid`: show `stripe_payment_intent_id` (truncated, monospace) as a reference
- If `failed`: show "Payment failed — retry" button if provider has Connect account

---

## Step 5 — Gates

```bash
PAGE=resources/js/pages/provider/Services.vue

grep -c "<svg"       $PAGE   # 0
grep -c 'title="'   $PAGE   # 0
grep -c "btn-sm"    $PAGE   # 0

# Payment flow wired
grep -c "sessions.complete\|completeSession" $PAGE   # > 0
grep -c "onError.*Object.values\|onError.*e\." $PAGE  # > 0

# Backend
php -l app/Services/PayoutService.php
php -l app/Http/Controllers/Provider/ServicesController.php
php -l app/Listeners/StripeEventListener.php

# chargeProviderToBp pattern reused
grep -c "isDemoClient\|isDemoPractitioner\|cus_demo_\|acct_demo_" app/Services/PayoutService.php

# Routes
grep -q "services.sessions.complete\|services.complete-session" routes/web.php \
  && echo "✅ route exists" || echo "❌ MISSING"

# Migration
find database/migrations -name "*service_session*" -o -name "*stripe_payment*" | sort
```

---

## Step 6 — Deliverable

```bash
zip -r /mnt/user-data/outputs/aegis_services_payment.zip \
  resources/js/pages/provider/Services.vue \
  app/Http/Controllers/Provider/ServicesController.php \
  app/Services/PayoutService.php \
  app/Listeners/StripeEventListener.php \
  database/seeders/UserSeeder.php \
  $(find database/migrations -name "*service_session_stripe*" -o -name "*practitioner_payment_session*" 2>/dev/null)

unzip -l /mnt/user-data/outputs/aegis_services_payment.zip
```

---

## Context: existing patterns to replicate

The BP contract payment flow (already working) is the reference. Key methods to model after:

| Services pattern | BP contract equivalent |
|---|---|
| `releaseServiceSessionPayout(payment, practitioner, client)` | `endContractAndRelease(contract, provider)` |
| `isDemoClient` / `isDemoPractitioner` detection | `isDemoProvider` / `isDemoBp` detection |
| Client's `stripe_id` + `stripe_payment_method_id` | Provider's `stripe_id` + `stripe_payment_method_id` |
| Provider's `stripe_account_id` as destination | BP's `stripe_account_id` as destination |
| `PractitionerPayment.stripe_payment_intent_id` | `BpPayout.stripe_payment_intent_id` |
| `handlePaymentIntentSucceeded` → update PractitionerPayment | `handlePaymentIntentSucceeded` → update BpPayout |

**Architectural constant:** Aegis is always the platform ($0 net). Client pays → destination charge → Provider's connected account. Never hold funds, never platform transfer from Aegis balance.

## Start

Run Step 0. Output Step 1 audit completely. Apply Step 3 backend first (surgical str_replace only — no rewrites of working methods). Apply Step 4 frontend changes. Run Step 5 gates. Deliver Step 6 ZIP.
