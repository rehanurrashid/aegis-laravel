# Clinical Services — Terms-Based Payment Overhaul
## Wave-Based Execution Plan (Rev 3 → Rev 4)

**Companion to:** `AEGIS_CLINICAL_SERVICES_MODULE.md` (Rev 4)
**Repo baseline:** `main @ 65d3f47`
**Total effort:** ~16 hours across **8 waves** (~1.5–2.5 hr each)
**Model:** each wave is a self-contained PR/ZIP. Ships independently.

---

## Scope Recap

Remove hardcoded 30/70 escrow-flavored split from Clinical Services. Add three payment structures with per-listing defaults and per-request negotiation. Keep BP escrow module untouched (separate plan).

**Out of scope:** BP EscrowService, CS invoice flow, subscriptions, dispute mechanics, refund mechanics (labels change only).

---

## Wave Overview

| # | Wave | Effort | Ships alone? | Blocked by |
|---|---|---|---|---|
| 1 | Migrations + Enum | 1.0 hr | Yes | — |
| 2 | Models + Backfill Run | 1.5 hr | Yes | W1 |
| 3 | PayoutService Rewrite | 1.5 hr | Yes | W2 |
| 4 | ServiceService + Controller + Routes + FormRequests | 2.5 hr | Yes | W3 |
| 5 | Vue Shared Components (PaymentTermsInline + CounterTermsInline) | 2.0 hr | Yes | — (parallel with W1–4) |
| 6 | Vue Modal Rewrites (Pay*Modal + ServiceRequestModal) | 2.5 hr | Yes | W4, W5 |
| 7 | Vue Page Updates + Copy Sweep | 2.5 hr | Yes | W6 |
| 8 | Emails + Events + Activity Log | 2.5 hr | Yes | W4 |

**Total: 16.0 hr.** Waves 1–4 are pure backend. Wave 5 has no backend dependency — can start any time.

**Parallelization:** dev A runs W1→W2→W3→W4→W8; dev B runs W5→W6→W7. Rejoin at W7 for final QA.

---

## Wave 1 — Migrations + Enum (1.0 hr)

### Goal
Add 15 new columns across 3 tables + one enum. All additive. Existing rows unchanged after this wave.

### Files
- `database/migrations/2026_07_21_000001_add_default_payment_terms_to_services.php` **(new)**
- `database/migrations/2026_07_21_000002_add_proposed_payment_terms_to_service_requests.php` **(new)**
- `database/migrations/2026_07_21_000003_add_committed_payment_terms_to_service_sessions.php` **(new)**
- `app/Enums/PaymentStructure.php` **(new)**

### Schema deltas
```
services            + default_payment_structure ENUM('full_upfront','split','full_on_completion')
                    + default_upfront_percentage TINYINT(30)
                    + default_terms_note TEXT NULL
                    + allow_completion_only BOOL(0)

service_requests    + proposed_payment_structure ENUM(...)
                    + proposed_upfront_percentage TINYINT(30)
                    + proposed_terms_note TEXT NULL
                    + terms_source ENUM('provider_default','client_proposed')

service_sessions    + payment_structure ENUM(...)
                    + upfront_percentage TINYINT(30)
                    + upfront_cents INT UNSIGNED(0)
                    + completion_cents INT UNSIGNED(0)
                    + terms_note TEXT NULL
                    + terms_source ENUM('provider_default','client_proposed','provider_countered')
                    + terms_agreed_at TIMESTAMP NULL
```

### Enum
```php
enum PaymentStructure: string {
    case FullUpfront       = 'full_upfront';
    case Split             = 'split';
    case FullOnCompletion  = 'full_on_completion';

    public function label(): string { … }
    public function chipLabel(int $pct = 30): string { … }
}
```

### Verify
```bash
php artisan migrate
php artisan tinker --execute="
    dump(Schema::hasColumn('services', 'default_payment_structure'));
    dump(Schema::hasColumn('service_requests', 'proposed_payment_structure'));
    dump(Schema::hasColumn('service_sessions', 'payment_structure'));
    dump(enum_exists(App\Enums\PaymentStructure::class));
"
```
All four dumps should print `true`.

### Rollback
`php artisan migrate:rollback --step=3` reverses the 3 migrations.

### Ship gate
Existing UI unchanged after this wave. Safe to deploy in isolation.

---

## Wave 2 — Models + Backfill Run (1.5 hr)

### Goal
Wire new columns into Eloquent. Populate existing rows with defaults. Rewrite computed attrs to prefer new columns.

### Files
- `app/Models/Service.php` **(edit — fillable + casts)**
- `app/Models/ServiceRequest.php` **(edit — fillable + casts)**
- `app/Models/ServiceSession.php` **(edit — fillable + casts + rewrite `getExpectedDeposit/Balance` + new `getTermsSummary` attr)**
- `database/migrations/2026_07_21_000004_backfill_payment_terms.php` **(new — data only)**

### Backfill logic
```php
DB::table('services')->update([
    'default_payment_structure'  => 'split',
    'default_upfront_percentage' => 30,
    'allow_completion_only'      => 0,
]);
DB::table('service_requests')->update([
    'proposed_payment_structure'  => 'split',
    'proposed_upfront_percentage' => 30,
    'terms_source'                => 'provider_default',
]);
DB::statement("
    UPDATE service_sessions
       SET payment_structure    = 'split',
           upfront_percentage   = 30,
           upfront_cents        = deposit_cents,
           completion_cents     = balance_cents,
           terms_source         = 'provider_default',
           terms_agreed_at      = COALESCE(deposit_paid_at, created_at)
");
```

### Computed attr rewrites (`ServiceSession`)
- `getExpectedDepositCentsAttribute` — prefer stored `upfront_cents`; fall back to `floor(agreed × upfront_percentage / 100)` for old rows; final fallback `floor(× 0.30)`
- `getExpectedBalanceCentsAttribute` — prefer `completion_cents`; else `agreed − expected_deposit`
- **NEW:** `getTermsSummaryAttribute` — one-line summary

### Verify
```php
// Tinker
$s = Service::first();
dump($s->default_payment_structure);       // PaymentStructure::Split

$sess = ServiceSession::first();
dump($sess->payment_structure);             // PaymentStructure::Split
dump($sess->upfront_cents);                 // = old deposit_cents
dump($sess->completion_cents);              // = old balance_cents
dump($sess->terms_summary);                 // "30% upfront + 70% completion"
dump($sess->expected_deposit_cents);        // reads upfront_cents now
```

### Ship gate
Old code calls `expected_deposit_cents` / `expected_balance_cents` — both return identical numbers. Finances tab renders same amounts.

---

## Wave 3 — PayoutService Rewrite (1.5 hr)

### Goal
Add unified `chargeSessionPortion($session, $portion)`. Convert old `chargeSessionDeposit` / `chargeSessionBalance` into deprecated wrappers.

### Files
- `app/Services/PayoutService.php` **(edit)**

### New signature
```php
public function chargeSessionPortion(
    ServiceSession $session,
    string $portion   // 'upfront' | 'completion'
): PractitionerPayment
```

### Behavior
1. Guard `$portion ∈ {upfront, completion}`
2. Compute `$cents` from `$session->upfront_cents` or `$session->completion_cents`
3. Guard `$cents > 0` (else throw)
4. Load provider + client from session
5. Demo/stub/live branching (copy `chargeProviderToCs` structure)
6. Stripe destination charge with metadata: `portion`, `payment_structure`, `upfront_percentage`, `terms_source`, `session_id`
7. DB writes:
   - `upfront` → `deposit_cents` (legacy) + `deposit_charge_id` + `deposit_paid_at`
   - `completion` → `balance_cents` (legacy) + `balance_charge_id` + `balance_paid_at`
8. `PractitionerPayment` row: `kind = service_session_upfront` or `service_session_completion`
9. Stub IDs: `pi_demo_up_*` / `pi_demo_comp_*` / `pi_stub_up_*` / `pi_stub_comp_*`

### Deprecated wrappers
```php
/** @deprecated Rev 4 — use chargeSessionPortion($session, 'upfront') */
public function chargeSessionDeposit(ServiceSession $s, User $p, User $c): PractitionerPayment
{
    return $this->chargeSessionPortion($s, 'upfront');
}
/** @deprecated Rev 4 */
public function chargeSessionBalance(ServiceSession $s, User $p, User $c): PractitionerPayment
{
    return $this->chargeSessionPortion($s, 'completion');
}
```

### Verify
```php
$s = ServiceSession::where('payment_status', 'unpaid')->first();
$payment = app(PayoutService::class)->chargeSessionPortion($s, 'upfront');
dump($payment->kind);                     // service_session_upfront
dump($s->fresh()->deposit_cents);         // = upfront_cents
```
`php -l app/Services/PayoutService.php` clean.

### Ship gate
Existing callers keep working via wrappers. New method ready for W4.

---

## Wave 4 — ServiceService + Controller + Routes + FormRequests (2.5 hr)

### Goal
Wire the whole backend flow end-to-end. Backend accepts terms via API and produces correctly-charged sessions.

### Files
- `app/Services/ServiceService.php` **(edit — 4 methods)**
- `app/Http/Controllers/Provider/ServicesController.php` **(edit — rename + extend validation)**
- `app/Http/Requests/Provider/AcceptServiceRequestRequest.php` **(edit — 4 new fields)**
- `app/Http/Requests/Provider/SendServiceRequestFromExploreRequest.php` **(edit — 5 new fields)**
- Public profile equivalent FormRequest **(edit — mirror same 5 fields)**
- `routes/web.php` **(edit — add `/upfront` alias to `/deposit`)**

### `ServiceService` changes

**`create()` + `update()`:** pass-through 4 new fields from FormRequest into `Service` model persist.

**`submitRequest()`:**
```php
if (($data['proposed_payment_structure'] ?? null) === 'full_on_completion'
    && !$service->allow_completion_only) {
    throw new RuntimeException('This service does not accept "pay after session" terms.');
}
// Persist proposed_* fields to service_requests row (defaults from service.default_*)
```

**`acceptRequest()`:** Read `terms_countered` + `committed_*` from `$data`. Pass through to `bookSession()`.

**`bookSession()`:**
```php
$structure  = $data['committed_payment_structure']  ?? $req->proposed_payment_structure?->value ?? 'split';
$pct        = $data['committed_upfront_percentage'] ?? $req->proposed_upfront_percentage ?? 30;
$termsNote  = $data['committed_terms_note']         ?? $req->proposed_terms_note;
$termsSrc   = ($data['terms_countered'] ?? false)
                ? 'provider_countered'
                : ($req->terms_source ?? 'provider_default');

$upfrontCents = match ($structure) {
    'full_upfront'       => $agreedAmount,
    'full_on_completion' => 0,
    default              => (int) floor($agreedAmount * $pct / 100),
};
$completionCents = $agreedAmount - $upfrontCents;

// Write new columns (payment_structure, upfront_percentage, upfront_cents,
//                    completion_cents, terms_note, terms_source, terms_agreed_at)
// deposit_cents/balance_cents stay 0 until charge time
```

**`payDeposit()` → rename `payUpfront()`:**
Guard `payment_structure ∈ {full_upfront, split}` (`full_on_completion` doesn't call this).
Call `PayoutService::chargeSessionPortion($session, 'upfront')`.
Update payment_status: `full_upfront` → `paid`; `split` → `deposit_paid`.
Fire `SessionUpfrontPaid` event (defined in W8).

**`completeSession()`:** Branch on `payment_structure`:
- `full_upfront`: mark completed, no charge, fire `SessionCompleted`
- `split`: mark completed, `chargeSessionPortion($s, 'completion')`, fire `SessionCompletionPaid` + `SessionCompleted`
- `full_on_completion`: mark completed, `chargeSessionPortion($s, 'completion')` (amount = full), fire same events

### Controller
- Rename `payDeposit` → `payUpfront`. Keep old name as one-line alias.
- Extend `store` / `update` validation: `default_payment_structure`, `default_upfront_percentage`, `default_terms_note`, `allow_completion_only`.

### FormRequest additions

**`AcceptServiceRequestRequest`:**
```php
'terms_countered'                => 'nullable|boolean',
'committed_payment_structure'    => 'nullable|in:full_upfront,split,full_on_completion',
'committed_upfront_percentage'   => 'nullable|integer|min:1|max:99',
'committed_terms_note'           => 'nullable|string|max:2000',
```

**`SendServiceRequestFromExploreRequest`** (+ public profile mirror):
```php
'proposed_payment_structure'   => 'required|in:full_upfront,split,full_on_completion',
'proposed_upfront_percentage'  => 'required_if:proposed_payment_structure,split|integer|min:1|max:99',
'proposed_terms_note'          => 'nullable|string|max:2000',
'terms_source'                 => 'required|in:provider_default,client_proposed',
'agree_terms'                  => 'required|accepted',
```

### Route add
```php
Route::post('/services/sessions/{session}/upfront', [ServicesController::class, 'payUpfront'])
     ->name('provider.services.session.upfront');
Route::post('/services/sessions/{session}/deposit', [ServicesController::class, 'payUpfront'])
     ->name('provider.services.session.deposit');
```

### Verify (Tinker end-to-end)
```php
$provider = User::find('p_maria');
$client   = User::find('p_david');
$svc      = Service::where('practitioner_id', $provider->id)->first();
$svc->update(['default_payment_structure' => 'full_upfront', 'default_upfront_percentage' => 100]);

$req = app(ServiceService::class)->submitRequest($svc, $client, [
    'proposed_payment_structure'  => 'full_upfront',
    'proposed_upfront_percentage' => 100,
    'terms_source'                => 'provider_default',
]);
dump($req->proposed_payment_structure);   // full_upfront

app(ServiceService::class)->acceptRequest($req, [
    'session_date' => now()->addDays(3)->format('Y-m-d'),
    'terms_countered' => false,
]);
$sess = ServiceSession::where('service_request_id', $req->id)->first();
dump($sess->payment_structure);    // full_upfront
dump($sess->upfront_cents);        // = agreed_amount
dump($sess->completion_cents);     // 0

app(ServiceService::class)->payUpfront($sess, $client);
dump($sess->fresh()->payment_status);   // paid
```
`php -l` clean. `route:list | grep upfront` shows new route.

### Ship gate
Backend fully functional. Legacy Vue still hits `/deposit` route via alias.

---

## Wave 5 — Vue Shared Components (2.0 hr)

### Goal
Two reusable components — used here (W6) AND by the Support Services sibling plan. Can build in parallel with W1–4.

### Files
- `resources/js/components/ui/PaymentTermsInline.vue` **(new)**
- `resources/js/components/ui/CounterTermsInline.vue` **(new)**

### `PaymentTermsInline.vue`
Used inside `ServiceRequestModal` (and BP's `BpProposeModal` in the sibling plan).

**Props:**
```
providerDefaults: { structure, upfrontPercentage, termsNote, allowCompletionOnly }
modelValue:       { structure, upfrontPercentage, termsNote, termsSource }
allowedStructures: Array (default ['full_upfront','split','full_on_completion'])
```

**Structure:**
- Header: chip summarizing provider default + "See details" toggle
- Radio: "Accept provider's terms" (default) vs "Propose different terms"
- When "Propose different":
  - Three-radio structure picker (filter by `allowedStructures` AND `allowCompletionOnly`)
  - Number input for upfront % (visible only when `split`; range 1–99)
  - Textarea for terms note (pre-filled with provider's default)
- Sets `termsSource = provider_default` when accepting; `client_proposed` when proposing

Visual pattern mirrors existing `CounterOfferInline.vue`.

### `CounterTermsInline.vue`
Used inside `Services.vue` Accept modal (and BP's `HireModal` in the sibling plan).

**Props:**
```
requestedTerms: { structure, upfrontPercentage, termsNote, termsSource }
modelValue:     { countered, structure, upfrontPercentage, termsNote }
allowedStructures: Array
```

**Structure:**
- Toggle: "Accept terms as proposed" (default on)
- When off: same editor as `PaymentTermsInline` (structure + upfront % + note)
- `full_on_completion` always allowed here (provider countering has no gate)

### Verify
Standalone visual check — mount each in an ad-hoc route. Toggle the radio → editor appears/hides. Emitted values match doc shape.

### Ship gate
Files are additive — nothing else imports them yet. Safe to merge whenever.

---

## Wave 6 — Vue Modal Rewrites (2.5 hr)

### Goal
All payment-related modals in Clinical Services use the new terms system.

### Files
- **RENAME:** `resources/js/components/modals/PayDepositModal.vue` → `PayUpfrontModal.vue`
- **RENAME:** `resources/js/components/modals/PayBalanceModal.vue` → `PayCompletionModal.vue`
- `resources/js/components/modals/ServiceRequestModal.vue` **(edit — add PaymentTermsInline)**
- Old file paths kept as one-line re-export shims for one release cycle

### `PayUpfrontModal.vue`
- Title: "Pay Upfront Portion"
- Summary shows `session.payment_structure_label` + terms_note (if present)
- Amount = `session.upfront_cents`
- Direct-to-provider disclosure paragraph (module doc §2 UI Language Standard)
- Agreement checkbox
- Confirm button label:
  - `full_upfront` → "Pay $X (full amount)"
  - `split` → "Pay $X ({pct}% upfront)"
- Route: `provider.services.session.upfront`

### `PayCompletionModal.vue`
- Title branches on structure:
  - `full_upfront` → "Confirm Session Complete" (no charge — modal explains)
  - `split` → "Confirm & Pay Completion Portion"
  - `full_on_completion` → "Confirm & Pay Session"
- Amount = `session.completion_cents` (0 for `full_upfront`)
- Disclosure + agreement checkbox
- Route: `provider.services.session.complete`

### `ServiceRequestModal.vue`
- Add `PaymentTermsInline` block below Message textarea
- **New props (parent passes):**
  ```
  providerDefaultStructure, providerDefaultUpfrontPct,
  providerDefaultTermsNote, providerAllowCompletionOnly
  ```
- Add `payment_terms` object to `useForm({…})` initial state
- On submit: flatten into POST payload
- `agree_terms` checkbox with Vuelidate `required + sameAs(true)`

### Verify
- Dev server → `/provider/services?tab=explore` → click Request on a card → modal opens with terms preloaded
- Toggle "Propose different" → editor appears
- Submit → verify backend receives `proposed_payment_structure` in payload
- My Requests tab → "Pay Upfront" on unpaid session → modal shows correct terms + amount

### Ship gate
Old imports of `PayDepositModal`/`PayBalanceModal` still resolve via re-export shims. Backward compatible.

---

## Wave 7 — Vue Page Updates + Copy Sweep (2.5 hr)

### Goal
All Vue pages that touch clinical sessions render new terms + labels.

### Files
- `resources/js/pages/provider/Services.vue` **(edit — Accept modal + Create/Edit + Request Detail + listings chips)**
- `resources/js/pages/provider/Finances.vue` **(edit — sessions tab copy + modal imports)**
- `resources/js/pages/public/ProviderProfile.vue` **(edit — service tiles hydrate ServiceRequestModal defaults)**
- `resources/js/components/ui/BookedSessionTable.vue` **(edit — column labels + button labels dynamic)**
- `resources/js/components/ui/SessionTable.vue` **(edit — same, provider viewpoint)**
- `resources/js/components/ui/SessionInvoiceCard.vue` **(edit — terms_summary chip)**
- `resources/js/components/ui/SessionInvoiceModal.vue` **(edit — invoice shows terms)**
- `resources/js/components/modals/RequestRefundModal.vue` **(edit — labels: Upfront/Completion)**
- `app/Services/AegisPdfService.php` **(edit — session invoice PDF gains terms block)**
- `app/Http/Controllers/PublicProfileController.php` **(edit — expose default_* fields on service tile shape)**

### Services.vue diffs
**Accept modal (~line 1130):** Add `<CounterTermsInline>` under `<CounterOfferInline>`. Extend `acceptForm`:
```js
terms_countered:              false,
committed_payment_structure:  null,
committed_upfront_percentage: null,
committed_terms_note:         null,
```

**Create/Edit Service modal:** Add "Default Payment Terms" panel — 3-radio structure + upfront % + terms note + `allow_completion_only` toggle. Extend `createForm` / `editForm`.

**Request Detail modal:** Add "Proposed Terms" read-only summary (module doc §9.2).

**Listings tab:** Terms chip on each service card (`PaymentStructure::chipLabel()`).

### Finances.vue diffs
- Line ~574 (client sub-tab desc): "30% deposit due at booking, 70% balance after" → dynamic terms text
- Line ~599 (provider sub-tab desc): same for provider viewpoint
- Line ~617–624: rename imports/tags (`PayDepositModal` → `PayUpfrontModal`, `PayBalanceModal` → `PayCompletionModal`)

### ProviderProfile.vue diffs
Service tiles pass 4 new props to `ServiceRequestModal`:
```
:providerDefaultStructure="tile.default_payment_structure"
:providerDefaultUpfrontPct="tile.default_upfront_percentage"
:providerDefaultTermsNote="tile.default_terms_note"
:providerAllowCompletionOnly="tile.allow_completion_only"
```
Backend `PublicProfileController::buildServiceProfile()` must expose these — add fields if missing.

### Tables + Cards — dynamic labels
**BookedSessionTable + SessionTable:** column headers "Deposit"/"Balance" → "Upfront"/"Completion". Row button labels per structure:
- `full_upfront` + unpaid → "Pay in Full"
- `split` + unpaid → "Pay Upfront"
- `full_on_completion` + unpaid → "Confirm & Pay"
- `split` + `deposit_paid` → "Confirm & Pay Completion"
- `full_upfront` + `paid` → "Confirm Complete"

**SessionInvoiceCard:** show `terms_summary` chip + terms_note.

**SessionInvoiceModal:** printable view includes terms text.

**RequestRefundModal:** "Deposit only (30%)" → "Upfront only ({pct}%)"; "Balance only (70%)" → "Completion only ({100-pct}%)". Values from `session.upfront_percentage`.

### AegisPdfService diff
`serviceSession()` — add "Payment Terms" block below invoice header showing structure summary + terms note + who proposed.

### Copy audit sweep
Zero hits after:
```bash
grep -rn -i "deposit"  resources/js/pages/provider/Services.vue \
                       resources/js/pages/provider/Finances.vue \
                       resources/js/components/modals/PayUpfrontModal.vue \
                       resources/js/components/modals/PayCompletionModal.vue

grep -rn "30%\|70%\|Deposit Due\|Balance Due" resources/js resources/views
grep -rn "escrow" resources/js/pages/provider/Services.vue \
                  resources/js/pages/provider/Finances.vue \
                  resources/js/components/modals/PayUpfrontModal.vue \
                  resources/js/components/modals/PayCompletionModal.vue
```

### Verify
Full end-to-end walk:
1. Provider creates service with `full_upfront` default
2. Client requests → provider counters to `split @ 40%`
3. Both parties see committed 40% throughout the flow
4. Client pays 40% upfront → session runs → client confirms → 60% charged
5. `payment_structure = split`, `upfront_percentage = 40`, `terms_source = provider_countered`

Repeat for `full_on_completion` (with `allow_completion_only = 1` on the listing).

### Ship gate
Only ship after full staging smoke test — this wave is the visible one.

---

## Wave 8 — Emails + Events + Activity Log (2.5 hr)

### Goal
All notifications match new terminology. Direct-to-provider disclosure in every money email. Activity log actions renamed.

### Files
- **RENAME:** `resources/views/emails/services/62-session-deposit-paid.blade.php` → `62-session-upfront-paid.blade.php` (leave 1-line alias)
- **RENAME:** `resources/views/emails/services/63-session-balance-paid.blade.php` → `63-session-completion-paid.blade.php` (leave 1-line alias)
- `resources/views/emails/partials/direct-to-provider-disclosure.blade.php` **(new partial)**
- `resources/views/emails/gaps/58-service-inquiry-received.blade.php` **(edit — show proposed terms)**
- `resources/views/emails/gaps/59-service-inquiry-responded.blade.php` **(edit — show committed terms)**
- `app/Events/Service/SessionUpfrontPaid.php` **(new)**
- `app/Events/Service/SessionCompletionPaid.php` **(new)**
- `app/Providers/AppServiceProvider.php` **(edit — 2 new `Event::listen()`)**
- `app/Listeners/SendEmailNotificationListener.php` **(edit — 2 new match cases in `resolve()`)**
- `app/Services/ServiceService.php` **(edit — activity log action rename + dispatch new events)**

### Disclosure partial
```blade
{{-- resources/views/emails/partials/direct-to-provider-disclosure.blade.php --}}
<p style="font-size:11px;color:#888;line-height:1.5;margin-top:20px;padding:10px;background:#f5f5f5;border-radius:4px">
  Payment routes directly to the provider's Stripe account via Stripe Connect.
  Aegis does not hold, escrow, or process funds on your behalf. You are transacting
  directly with the provider.
</p>
```
`@include` at end of every service email that references money.

### Event classes
`SessionUpfrontPaid` — same constructor shape as `SessionDepositPaid`. Fires in parallel with old event for one release cycle.
`SessionCompletionPaid` — same relationship to `SessionBalancePaid`.

### AppServiceProvider
```php
Event::listen(SessionUpfrontPaid::class, SendEmailNotificationListener::class);
Event::listen(SessionCompletionPaid::class, SendEmailNotificationListener::class);
```

### `SendEmailNotificationListener::resolve()`
Add match cases:
```php
$event instanceof SessionUpfrontPaid => [
    ['user_id' => $event->provider->id, 'gate_key' => 'notify_payment',
     'template' => 'emails.services.62-session-upfront-paid', 'data' => […]],
    ['user_id' => $event->client->id,   'gate_key' => 'notify_payment',
     'template' => 'emails.services.62-session-upfront-paid', 'data' => […]],
],
$event instanceof SessionCompletionPaid => [
    // same shape → template 63-session-completion-paid
],
```

### Activity log renames (in `ServiceService::payUpfront` + `::completeSession`)
| Old action | New action |
|---|---|
| `deposit_paid` | `upfront_paid` |
| `balance_paid` | `completion_paid` |

**Critical:** every `ActivityService::log()` call — position 11 = `'log'|'notification'`, position 12 = actor ID. No named args. Grep after edits:
```bash
grep -n "ActivityService.*log(" app/Services/ServiceService.php
```

### Also dispatch new events alongside old (for one cycle)
In `payUpfront`:
```php
event(new SessionUpfrontPaid($session->fresh(), $client, $provider, $payment, $upfrontCents));
// Legacy (removed in cleanup PR):
event(new SessionDepositPaid($session->fresh(), $client, $provider, $payment, $upfrontCents));
```
Same pattern in `completeSession` for the split + full_on_completion branches.

### Verify
- Trigger full upfront payment → check email preview: new template renders, disclosure present, no "30%" hardcode
- Activity log row: action = `upfront_paid`, entry_type correct
- Legacy events still fire (BC layer intact)
- `php -l` clean

### Ship gate
Rev 3 → Rev 4 migration complete. Ship after W7 verified in staging.

---

## Cleanup PR (one release cycle later, ~1.0 hr)

- Remove `payDeposit()` method + `chargeSessionDeposit` / `chargeSessionBalance` wrappers
- Remove `SessionDepositPaid` / `SessionBalancePaid` events
- Remove template aliases (`62-session-deposit-paid.blade.php`, `63-session-balance-paid.blade.php`)
- Remove `/services/sessions/{session}/deposit` route
- Remove modal re-export shims
- Update tests to exercise only new API

---

## Dependency Chart

```
W1 (migrations + enum)
  └─▶ W2 (models + backfill)
        └─▶ W3 (PayoutService)
              └─▶ W4 (ServiceService + Controller + Routes + FormRequests)
                    ├─▶ W8 (emails + events + activity)
                    └─▶ ─────────────┐
                                      │
W5 (shared components) ──────────────▶ W6 (modal rewrites)
                                      │
                                      └─▶ W7 (page updates + copy sweep)
```

**Critical path:** W1 → W2 → W3 → W4 → W6 → W7 (11.5 hr sequential)
**With two devs parallelized:** ~10 hr real time

---

## Open Questions (decide before W2)

1. **Terms note editability post-accept:** locked once contract-formed (recommended — mirrors executed contracts), or editable?
2. **`full_on_completion` refund copy:** if session was `full_on_completion` and payment happened, then client requests full refund — mechanism unchanged, copy needs one pass.
3. **`p_sarah` real Stripe subscription:** backfill sets defaults on her services. No effect on her live sub; only affects new session bookings.

---

*Doc rev 2 — waves format. Any changes to the module doc during execution should be mirrored here.*
