# Support Services — Direct-Charge + Terms Overhaul
## Wave-Based Execution Plan (Rev 1 → Rev 2)

**Companion to:** `SUPPORT_SERVICES_MODULE.md` (Rev 2)
**Repo baseline:** `main @ 65d3f47`
**Total effort:** ~26 hours across **12 waves** (~1.5–3 hr each)
**Model:** each wave is a self-contained PR/ZIP. Ships independently.

More invasive than Clinical Services because BP has **real escrow code** (`EscrowService::chargeToEscrow` creates PaymentIntents WITHOUT `transfer_data.destination`, parking funds in Aegis's platform balance). Rev 2 rips that pathway out and replaces it with the destination-charge primitive that already exists at `PayoutService::chargeProviderToBp`.

---

## Scope Recap

### In scope
- Remove `EscrowService` from all new-contract flows (kept only for legacy reconciliation)
- All BP job/proposal/contract/milestone flows switch to direct destination charges
- `Provider/SupportServices.vue`, `Provider/Finances.vue` (BP tab)
- Public BP profile (`BusinessProfile.vue`)
- All BP-related modals + email templates
- `MilestoneAutoReleaseJob` → replaced by `MilestoneAutoApproveJob`
- `bp_escrow_ledger` → retained for audit only, no new writes

### Out of scope
- Clinical Services (its own plan)
- CS invoice flow, subscriptions, dispute mechanics
- Admin dashboard (only "legacy escrow reconciliation" view added)

---

## Wave Overview

| # | Wave | Effort | Ships alone? | Blocked by |
|---|---|---|---|---|
| 1 | Migrations + Enum extension | 1.5 hr | Yes | — |
| 2 | Models + BpContractTerms + Backfill | 2.0 hr | Yes | W1 |
| 3 | PayoutService new methods | 2.0 hr | Yes | W2 |
| 4 | ContractService::sign rewrite + terms snapshot | 2.0 hr | Yes | W3 |
| 5 | ContractService approve/complete/cancel | 2.0 hr | Yes | W4 |
| 6 | ProposalService + BpJobService + EscrowService guards | 2.0 hr | Yes | W2 |
| 7 | Controllers + FormRequests + Routes | 2.0 hr | Yes | W5, W6 |
| 8 | Vue deletions/renames + reuse shared components | 1.5 hr | Yes | W7 |
| 9 | Vue provider-side modals (Hire / PostJob / Sign / MilestoneReview / Contract) | 3.0 hr | Yes | W8 |
| 10 | Vue BP-side modals + pages (BpPropose + Contracts/Milestones/Finances) | 2.5 hr | Yes | W8 |
| 11 | Vue tables + copy sweep (BpFinance/BpContractRow/BpMilestoneRow + SupportServices.vue + Finances.vue BP tab + BusinessProfile) | 2.5 hr | Yes | W9, W10 |
| 12 | Emails + Events + Activity + MilestoneAutoApproveJob + Legacy admin | 3.0 hr | Yes | W7 |

**Total: 26.0 hr.** Waves 1–7 are pure backend, 8–11 pure Vue, 12 is emails + jobs.

**Parallelization:**
- Dev A: W1 → W2 → W3 → W4 → W5 → W12
- Dev B: (waits for W2) → W6 → W7 → (waits for W7) → W8 → W9
- Dev C: (waits for W8) → W10 → (waits for W9+W10) → W11

Realistic: ~14 hr with 2 devs, ~10 hr with 3 devs.

**Depends on Clinical Services waves being partially done:** W5 of Clinical creates `PaymentTermsInline` + `CounterTermsInline`. If Clinical W5 hasn't shipped, do it as part of this plan's W8 (add ~2 hr to W8).

---

## Wave 1 — Migrations + Enum Extension (1.5 hr)

### Goal
Add 27 new columns across 4 tables + 1 new table + 2 new enum cases. All additive. Existing rows unchanged after this wave.

### Files
- `database/migrations/2026_07_21_000010_add_default_payment_terms_to_bp_jobs.php` **(new)**
- `database/migrations/2026_07_21_000011_add_proposed_payment_terms_to_bp_proposals.php` **(new)**
- `database/migrations/2026_07_21_000012_add_committed_payment_terms_to_bp_contracts.php` **(new)**
- `database/migrations/2026_07_21_000013_add_direct_charge_columns_to_bp_milestones.php` **(new)**
- `database/migrations/2026_07_21_000014_create_bp_contract_terms_table.php` **(new)**
- `app/Enums/PaymentStructure.php` **(edit — add 2 cases; OR new if Clinical hasn't shipped yet)**

### Schema deltas
```
bp_jobs            + default_payment_structure ENUM('full_upfront','split','per_milestone','on_completion')
                   + default_upfront_percentage TINYINT(30)
                   + default_terms_note TEXT NULL
                   + allow_on_completion BOOL(0)

bp_proposals       + proposed_payment_structure ENUM(...)
                   + proposed_upfront_percentage TINYINT(30)
                   + proposed_terms_note TEXT NULL
                   + terms_source ENUM('provider_default','bp_proposed')

bp_contracts       + payment_structure ENUM(...)
                   + upfront_percentage TINYINT(0)
                   + upfront_cents INT UNSIGNED(0)
                   + remaining_cents INT UNSIGNED(0)
                   + terms_note TEXT NULL
                   + terms_source ENUM('provider_default','bp_proposed','provider_countered')
                   + terms_agreed_at TIMESTAMP NULL
                   + paid_cents INT UNSIGNED(0)
                   + upfront_charge_intent_id VARCHAR(64) NULL
                   + upfront_charged_at TIMESTAMP NULL
                   + completion_charge_intent_id VARCHAR(64) NULL
                   + completion_charged_at TIMESTAMP NULL
                   + payment_failed_at TIMESTAMP NULL

bp_milestones      + payment_intent_id VARCHAR(64) NULL
                   + paid_at TIMESTAMP NULL
                   + paid_cents INT UNSIGNED(0)
                   + auto_approve_at TIMESTAMP NULL
                   + payment_failed_at TIMESTAMP NULL

bp_contract_terms  NEW TABLE
                   id CHAR(36) PK
                   contract_id CHAR(36) UNIQUE FK
                   payment_structure ENUM
                   upfront_percentage TINYINT
                   upfront_cents INT UNSIGNED
                   remaining_cents INT UNSIGNED
                   total_value_cents INT UNSIGNED
                   terms_note TEXT NULL
                   terms_source ENUM
                   snapshotted_at TIMESTAMP
                   NO timestamps otherwise
```

### Enum extension
If `PaymentStructure` already exists from Clinical W1: add two cases:
```php
case PerMilestone = 'per_milestone';
case OnCompletion = 'on_completion';
```
If it doesn't exist yet: create with all four cases.

### Verify
```bash
php artisan migrate
php artisan tinker --execute="
    dump(Schema::hasColumn('bp_jobs', 'default_payment_structure'));
    dump(Schema::hasColumn('bp_proposals', 'proposed_payment_structure'));
    dump(Schema::hasColumn('bp_contracts', 'payment_structure'));
    dump(Schema::hasColumn('bp_contracts', 'paid_cents'));
    dump(Schema::hasColumn('bp_milestones', 'payment_intent_id'));
    dump(Schema::hasTable('bp_contract_terms'));
"
```
All six dumps should print `true`.

### Rollback
`php artisan migrate:rollback --step=5` reverses.

### Ship gate
UI unchanged after this wave. Safe to deploy in isolation.

---

## Wave 2 — Models + BpContractTerms + Backfill (2.0 hr)

### Goal
Wire new columns into models. Create the new snapshot model. Backfill existing rows.

### Files
- `app/Models/BpJob.php` **(edit — 4 fillable + casts)**
- `app/Models/BpProposal.php` **(edit — 4 fillable + casts)**
- `app/Models/BpContract.php` **(edit — 13 fillable + casts + new `terms()` relation + `getTermsSummaryAttribute()`)**
- `app/Models/BpMilestone.php` **(edit — 5 fillable + casts)**
- `app/Models/BpContractTerms.php` **(new — no timestamps, no `updated_at`)**
- `database/migrations/2026_07_21_000015_backfill_support_services_terms.php` **(new — data only, idempotent)**

### Backfill logic
```php
DB::table('bp_jobs')->update([
    'default_payment_structure'  => 'split',
    'default_upfront_percentage' => 30,
    'allow_on_completion'        => 0,
]);
DB::table('bp_proposals')->update([
    'proposed_payment_structure'  => 'split',
    'proposed_upfront_percentage' => 30,
    'terms_source'                => 'provider_default',
]);

// Map old funding_mode → new payment_structure
DB::statement("
    UPDATE bp_contracts
       SET payment_structure  = CASE WHEN funding_mode = 'full_upfront' THEN 'full_upfront'
                                     ELSE 'per_milestone' END,
           upfront_percentage = CASE WHEN funding_mode = 'full_upfront' THEN 100 ELSE 0 END,
           upfront_cents      = CASE WHEN funding_mode = 'full_upfront' THEN total_value_cents ELSE 0 END,
           remaining_cents    = CASE WHEN funding_mode = 'full_upfront' THEN 0 ELSE total_value_cents END,
           paid_cents         = COALESCE(escrow_released_cents, 0),
           terms_source       = 'provider_default',
           terms_agreed_at    = COALESCE(fully_executed_at, created_at)
");

// Copy legacy escrow columns → direct-charge columns for released milestones
DB::statement("
    UPDATE bp_milestones
       SET payment_intent_id = escrow_intent_id,
           paid_at           = COALESCE(released_at, updated_at),
           paid_cents        = COALESCE(released_cents, 0)
     WHERE status IN ('released', 'paid')
");

// Insert bp_contract_terms snapshot for every fully-executed contract
DB::statement("
    INSERT INTO bp_contract_terms
        (id, contract_id, payment_structure, upfront_percentage,
         upfront_cents, remaining_cents, total_value_cents,
         terms_note, terms_source, snapshotted_at)
    SELECT
        CONCAT('bct_', LOWER(SUBSTRING(MD5(id), 1, 12))),
        id, payment_structure, upfront_percentage,
        upfront_cents, remaining_cents, total_value_cents,
        terms_note, terms_source,
        COALESCE(fully_executed_at, created_at)
      FROM bp_contracts
     WHERE fully_executed_at IS NOT NULL
");
```

### Verify
```php
// Tinker
$c = BpContract::first();
dump($c->payment_structure);            // PaymentStructure::PerMilestone (or FullUpfront)
dump($c->paid_cents);                   // = escrow_released_cents
dump($c->terms);                        // BpContractTerms row
dump($c->terms_summary);                // "Per milestone" or "Pay in full at start"

$m = BpMilestone::where('status', 'released')->first();
dump($m->payment_intent_id);            // = escrow_intent_id
```

### Ship gate
Old code reads legacy columns; new code reads new columns. Both populated.

---

## Wave 3 — PayoutService New Methods (2.0 hr)

### Goal
Add all direct-charge primitives for BP flows. `PayoutService::chargeProviderToBp` (already exists) becomes the sole money-movement primitive.

### Files
- `app/Services/PayoutService.php` **(edit — 5 new methods)**

### New methods

**`chargeContractUpfront(BpContract $contract): ?BpPayout`**
- Guards: `contract.upfront_cents > 0`, `contract.upfront_charge_intent_id === null` (idempotent), provider PM + BP Connect set
- Reads provider + bp from contract
- Calls `chargeProviderToBp($provider, $bp, $contract->upfront_cents, ...)` with metadata:
  ```json
  {"contract_id": "…", "portion": "upfront",
   "payment_structure": "…", "upfront_percentage": 30}
  ```
- On success: set `contract.upfront_charge_intent_id`, `upfront_charged_at`, `paid_cents += upfront_cents`
- Create `BpPayout` row
- Fire `ContractUpfrontCharged` event (defined in W12)
- Return `BpPayout`
- On failure: `contract.payment_failed_at = now()`, throw

**`chargeContractCompletion(BpContract $contract): BpPayout`**
- Guards: `contract.total_value_cents - contract.paid_cents > 0`
- Amount = `total_value_cents - paid_cents`
- Same pattern as chargeContractUpfront (metadata `portion: completion`)
- Fires `ContractCompletionCharged`
- Sets `contract.status = 'completed'` if this closes balance

**`chargeMilestone(BpMilestone $milestone): BpPayout`**
- Wrapper — same body as existing `payMilestone` but idempotent-guard on `payment_intent_id === null`
- Old `payMilestone` → deprecated alias that calls this

**`refundBpCharge(string $paymentIntentId, int $cents, string $reason): array`**
- Direct Stripe call:
  ```php
  $stripe->refunds->create([
      'payment_intent'   => $paymentIntentId,
      'amount'           => $cents,
      'reason'           => 'requested_by_customer',
      'reverse_transfer' => true,
      'metadata'         => ['aegis_reason' => $reason],
  ]);
  ```
- Returns refund object. Caller updates its own DB row.

**`refundContractUpfront(BpContract $contract, User $actor, string $reason): void`**
- Guards: `contract.upfront_charge_intent_id !== null`, `contract.paid_cents > 0`
- Calls `refundBpCharge($contract->upfront_charge_intent_id, $contract->paid_cents, $reason)`
- Updates `contract.paid_cents = 0`, `contract.status = 'cancelled'`
- Fires `ContractUpfrontRefunded` event
- Activity log

### Verify
```php
$c = BpContract::factory()->create([
    'payment_structure' => 'full_upfront',
    'total_value_cents' => 100000,
    'upfront_cents'     => 100000,
    'remaining_cents'   => 0,
]);
$payout = app(PayoutService::class)->chargeContractUpfront($c);
dump($c->fresh()->paid_cents);              // 100000
dump($c->fresh()->upfront_charge_intent_id); // pi_demo_… or pi_…
```
`php -l app/Services/PayoutService.php` clean.

### Ship gate
Existing callers still work (`chargeProviderToBp` untouched, `payMilestone` deprecated alias).

---

## Wave 4 — ContractService::sign Rewrite + Terms Snapshot (2.0 hr)

### Goal
Signing IS payment authorization now. When both parties signed → snapshot terms → fire upfront charge if applicable → contract → active (skipping pending_funding).

### Files
- `app/Services/ContractService.php` **(edit — `sign()` method)**
- `app/Enums/ContractStatus.php` **(edit — keep `pending_funding` case for legacy but add class docblock noting it's Rev 1 only)**

### `sign()` rewrite pseudocode
```php
public function sign(BpContract $contract, User $signer, string $signatureName): BpContract
{
    // Existing: figure out which party is signing
    if ($signer->id === $contract->practitioner_id) {
        $contract->update([
            'practitioner_signed_at'      => now(),
            'practitioner_signature_name' => $signatureName,
        ]);
    } elseif ($signer->id === $contract->bp_id) {
        $contract->update([
            'bp_signed_at'      => now(),
            'bp_signature_name' => $signatureName,
        ]);
    } else {
        abort(403);
    }

    $contract->refresh();

    // Not both signed yet — email the other party, done.
    if (!$contract->practitioner_signed_at || !$contract->bp_signed_at) {
        event(new ContractSignedByOne($contract, $signer));
        return $contract;
    }

    // BOTH SIGNED — the Rev 2 magic happens here
    return DB::transaction(function () use ($contract) {
        // 1. Snapshot terms (immutable audit row)
        BpContractTerms::create([
            'id'                 => 'bct_' . Str::lower(Str::random(12)),
            'contract_id'        => $contract->id,
            'payment_structure'  => $contract->payment_structure,
            'upfront_percentage' => $contract->upfront_percentage,
            'upfront_cents'      => $contract->upfront_cents,
            'remaining_cents'    => $contract->remaining_cents,
            'total_value_cents'  => $contract->total_value_cents,
            'terms_note'         => $contract->terms_note,
            'terms_source'       => $contract->terms_source,
            'snapshotted_at'     => now(),
        ]);

        // 2. Contract → active (skip pending_funding)
        $contract->update([
            'fully_executed_at' => now(),
            'status'            => ContractStatus::Active->value,
        ]);

        // 3. Fire structure-driven upfront charge
        $structure = $contract->payment_structure;
        if (in_array($structure, [PaymentStructure::FullUpfront, PaymentStructure::Split])) {
            try {
                $this->payouts->chargeContractUpfront($contract->fresh());
            } catch (\Throwable $e) {
                $contract->update(['payment_failed_at' => now()]);
                $this->activity->log(
                    $contract->practitioner_id, 'provider', 'job_postings',
                    ActivitySeverity::Critical, 'contract_upfront_payment_failed',
                    'Upfront charge failed', $e->getMessage(),
                    'bp_contract', $contract->id, $contract->bp_id,
                    'notification', $contract->practitioner_id
                );
                // Contract remains 'active' — provider must retry via UI
            }
        }

        // 4. For full_upfront: also mark all milestones prepaid
        if ($structure === PaymentStructure::FullUpfront
            && !$contract->fresh()->payment_failed_at) {
            BpMilestone::where('contract_id', $contract->id)
                ->update([
                    'status'     => MilestoneStatus::Prepaid->value,
                    'paid_at'    => now(),
                    'paid_cents' => DB::raw('amount_cents'),
                ]);
            $contract->update(['status' => ContractStatus::Completed->value]);
        }

        event(new ContractSigned($contract->fresh()));
        return $contract->fresh();
    });
}
```

**Note:** `MilestoneStatus::Prepaid` — new enum case. Add if not present.

### Verify
```php
// Tinker — full flow
$c = BpContract::where('status', 'pending_signature')->first();
$provider = User::find($c->practitioner_id);
$bp       = User::find($c->bp_id);

// Provider signs first
app(ContractService::class)->sign($c, $provider, 'Dr. Provider');
dump($c->fresh()->status);                    // pending_signature (waiting for BP)

// BP signs — triggers everything
app(ContractService::class)->sign($c->fresh(), $bp, 'BP Inc');
dump($c->fresh()->status);                    // active (or completed if full_upfront)
dump($c->fresh()->terms);                     // BpContractTerms row present
dump($c->fresh()->upfront_charge_intent_id);  // pi_demo_… (if structure needed a charge)
```

### Ship gate
Legacy contracts (no `payment_structure`) still go through the old path — nothing writes `payment_structure = null`, so the branch is safe. All new contracts (any created post-W7) go through the new path.

---

## Wave 5 — ContractService approve/complete/cancel (2.0 hr)

### Goal
Milestone approval fires direct charge synchronously. New `completeContract` method for one-time on_completion + split-remainder. `cancelContract` triggers refund if applicable.

### Files
- `app/Services/ContractService.php` **(edit — 3 methods)**

### `approveMilestone()` rewrite
```php
public function approveMilestone(BpMilestone $milestone, ?User $approver): BpMilestone
{
    // Existing guards + status update
    $milestone->update([
        'status'      => MilestoneStatus::Approved->value,
        'approved_at' => now(),
    ]);

    try {
        // Fire direct charge synchronously
        $this->payouts->chargeMilestone($milestone->fresh());
        // chargeMilestone sets milestone.status = 'paid', payment_intent_id, paid_at, paid_cents

        // Auto-complete contract if all milestones paid
        $contract = $milestone->contract;
        if ($this->allMilestonesPaid($contract)) {
            $contract->update([
                'status'       => ContractStatus::Completed->value,
                'completed_at' => now(),
            ]);
            event(new ContractCompleted($contract->fresh()));
        }
    } catch (\Throwable $e) {
        $milestone->update([
            'status'            => MilestoneStatus::Approved->value,  // stays approved
            'payment_failed_at' => now(),
        ]);
        event(new MilestonePaymentFailed($milestone, $e->getMessage()));
        // Milestone visible in UI with "Retry payment" button
    }

    return $milestone->fresh();
}
```

### `completeContract()` NEW
For `on_completion` one-time contracts + split contracts needing remainder charge.
```php
public function completeContract(BpContract $contract, User $actor): BpContract
{
    if ($contract->practitioner_id !== $actor->id) abort(403);

    if ($contract->paid_cents < $contract->total_value_cents) {
        $this->payouts->chargeContractCompletion($contract);
    }

    $contract->update([
        'status'       => ContractStatus::Completed->value,
        'completed_at' => now(),
    ]);
    event(new ContractCompleted($contract->fresh()));
    return $contract->fresh();
}
```

### `cancelContract()` rewrite
Add refund-if-paid path:
```php
public function cancelContract(BpContract $contract, User $actor, string $reason): BpContract
{
    // Existing guards
    if ($contract->paid_cents > 0 && $contract->upfront_charge_intent_id) {
        $this->payouts->refundContractUpfront($contract, $actor, "Contract cancelled: {$reason}");
    }
    $contract->update([
        'status'       => ContractStatus::Cancelled->value,
        'cancelled_at' => now(),
    ]);
    event(new ContractCancelled($contract->fresh(), $reason));
    return $contract->fresh();
}
```

### Also add `cancelMilestone()` (replaces old escrow-refund path)
```php
public function cancelMilestone(BpMilestone $milestone, User $actor): BpMilestone
{
    // Allowed pre-charge only
    if (in_array($milestone->status->value, ['paid', 'released'])) {
        throw new RuntimeException('Milestone already paid. Open a dispute to seek refund.');
    }
    $milestone->update([
        'status'       => MilestoneStatus::Cancelled->value,
        'cancelled_at' => now(),
    ]);
    return $milestone->fresh();
}
```

### Verify
```php
// Approve → fires direct charge
$m = BpMilestone::where('status', 'submitted')->first();
app(ContractService::class)->approveMilestone($m, User::find($m->contract->practitioner_id));
dump($m->fresh()->status);                // paid
dump($m->fresh()->payment_intent_id);     // pi_demo_… or pi_…

// Cancel contract with upfront paid → refund fires
$c = BpContract::where('payment_structure', 'full_upfront')->first();
app(ContractService::class)->cancelContract($c, User::find($c->practitioner_id), 'Testing');
dump($c->fresh()->paid_cents);            // 0
dump($c->fresh()->status);                // cancelled
```

### Ship gate
Every new milestone approval now fires a direct charge. Legacy contracts (with `payment_structure = null`) still route through the escrow path via `EscrowService` — that's W6.

---

## Wave 6 — ProposalService + BpJobService + EscrowService Guards (2.0 hr)

### Goal
Persist proposed_* fields on submit. Persist committed_* on accept. Deprecate EscrowService.

### Files
- `app/Services/BpJobService.php` **(edit — persist default_* fields on create/update)**
- `app/Services/ProposalService.php` **(edit — submit + accept)**
- `app/Services/EscrowService.php` **(edit — mark deprecated, add guards)**

### `BpJobService::create()` + `::update()`
Pass through the 4 new default_* fields from FormRequest (validation happens in W7).

### `ProposalService::submit()`
```php
public function submit(BpJob $job, User $bp, array $data): BpProposal
{
    // Existing guards
    if (($data['proposed_payment_structure'] ?? null) === 'on_completion'
        && !$job->allow_on_completion) {
        throw new RuntimeException('This posting does not accept "pay on completion" terms.');
    }

    return BpProposal::create([
        // ... existing fields ...
        'proposed_payment_structure'  => $data['proposed_payment_structure']
                                          ?? $job->default_payment_structure?->value ?? 'split',
        'proposed_upfront_percentage' => $data['proposed_upfront_percentage']
                                          ?? $job->default_upfront_percentage ?? 30,
        'proposed_terms_note'         => $data['proposed_terms_note']
                                          ?? $job->default_terms_note,
        'terms_source'                => $data['terms_source'] ?? 'provider_default',
    ]);
}
```

### `ProposalService::accept()`
Accepts array with:
```
terms_countered              bool
committed_payment_structure  ?PaymentStructure
committed_upfront_percentage ?int
committed_terms_note         ?string
```

Computes final terms. Writes to contract. Sets `terms_agreed_at = now()`. Computes `upfront_cents = floor(total × pct / 100)`, `remaining_cents = total - upfront_cents`.

```php
public function accept(BpProposal $proposal, User $provider, array $data): BpContract
{
    // Existing: mark accepted, decline other proposals
    // ...

    // Rev 2: compute final terms
    $countered = $data['terms_countered'] ?? false;
    $structure = $countered
        ? ($data['committed_payment_structure'] ?? 'per_milestone')
        : ($proposal->proposed_payment_structure?->value ?? 'per_milestone');
    $pct = $countered
        ? ($data['committed_upfront_percentage'] ?? 0)
        : ($proposal->proposed_upfront_percentage ?? 0);
    $termsNote = $countered
        ? ($data['committed_terms_note'] ?? null)
        : $proposal->proposed_terms_note;
    $termsSrc = $countered ? 'provider_countered' : ($proposal->terms_source ?? 'provider_default');

    $total = $proposal->proposed_rate_cents ?? $proposal->job->budget_max_cents;
    $upfrontCents = match ($structure) {
        'full_upfront'  => $total,
        'on_completion' => 0,
        'per_milestone' => 0,
        'split'         => (int) floor($total * $pct / 100),
    };
    $remainingCents = $total - $upfrontCents;

    $contract = BpContract::create([
        // ... existing fields ...
        'total_value_cents'   => $total,
        'payment_structure'   => $structure,
        'upfront_percentage'  => $pct,
        'upfront_cents'       => $upfrontCents,
        'remaining_cents'     => $remainingCents,
        'terms_note'          => $termsNote,
        'terms_source'        => $termsSrc,
        'terms_agreed_at'     => now(),
        'status'              => 'pending_signature',
    ]);

    event(new ContractCreated($contract));
    return $contract;
}
```

### `EscrowService` deprecation
At the top of every public method (`fundMilestone`, `fundContract`, `releaseMilestone`, `refundMilestone`, `splitResolution`, `cancelContractEscrow`):
```php
// Resolve the contract from arg (may be from milestone->contract or arg)
$contract = $this->resolveContract(...);

if ($contract->payment_structure !== null) {
    throw new \LogicException(
        'EscrowService is deprecated for Rev 2 contracts. Use PayoutService::' .
        'chargeContractUpfront/chargeContractCompletion/chargeMilestone instead.'
    );
}
```
Class docblock: **`@deprecated Rev 2 — retained for legacy contract reconciliation only.`**

### Verify
```php
// Submit proposal
$job = BpJob::first();
$job->update([
    'default_payment_structure' => 'split',
    'default_upfront_percentage' => 40,
    'allow_on_completion' => 0,
]);
$bp = User::find('bp_jamal');
$p = app(ProposalService::class)->submit($job, $bp, [
    'proposed_payment_structure'  => 'split',
    'proposed_upfront_percentage' => 25,
    'terms_source'                => 'bp_proposed',
    'proposed_rate_cents'         => 100000,
    'cover_letter'                => 'Hi',
]);
dump($p->proposed_payment_structure);  // split
dump($p->proposed_upfront_percentage); // 25

// Accept with counter
$provider = User::find($job->practitioner_id);
$c = app(ProposalService::class)->accept($p, $provider, [
    'terms_countered'              => true,
    'committed_payment_structure'  => 'split',
    'committed_upfront_percentage' => 50,
    'committed_terms_note'         => 'Provider countered upfront to 50%',
]);
dump($c->payment_structure);   // split
dump($c->upfront_percentage);  // 50
dump($c->upfront_cents);       // 50000
dump($c->terms_source);        // provider_countered

// Try to use escrow on Rev 2 contract → should throw
try {
    app(EscrowService::class)->fundContract($c, $provider);
    echo "FAIL — should have thrown";
} catch (\LogicException $e) {
    echo "PASS: " . $e->getMessage();
}
```

### Ship gate
New contracts flow through direct-charge path. Legacy contracts still work through EscrowService (they have `payment_structure = null`).

---

## Wave 7 — Controllers + FormRequests + Routes (2.0 hr)

### Goal
Wire the API. New endpoints exist. Legacy `fund*` endpoints return 410. Validation extended.

### Files
- `app/Http/Controllers/Provider/JobPostingsController.php` **(edit — 4 methods)**
- `app/Http/Controllers/BusinessPartner/ProposalsController.php` **(edit — submit accepts proposed_*)**
- `app/Http/Requests/HireBpRequest.php` **(new OR edit if exists — committed_* fields)**
- `app/Http/Requests/SubmitBpProposalRequest.php` **(new OR edit — proposed_* fields)**
- `app/Http/Requests/PostBpJobRequest.php` **(new OR edit — default_* fields)**
- `routes/web.php` **(edit — add + remove routes)**

### Controller changes

**`JobPostingsController::fundContract` + `::fundMilestone`:** Return 410 Gone.
```php
public function fundContract(...) {
    return response()->json([
        'error' => 'Escrow funding is deprecated. Payment now fires automatically ' .
                   'based on committed contract terms at signing or milestone approval.',
    ], 410);
}
```

**`JobPostingsController::completeContract` NEW:**
```php
public function completeContract(BpContract $contract) {
    $this->contracts->completeContract($contract, auth()->user());
    return back()->with('success', 'Contract marked complete.');
}
```

**`JobPostingsController::cancelMilestone` (renamed from `refundMilestone`):**
```php
public function cancelMilestone(BpMilestone $milestone) {
    if (in_array($milestone->status?->value, ['paid', 'released'])) {
        return back()->withErrors(['milestone' => 'Milestone already paid. Open a dispute.']);
    }
    $this->contracts->cancelMilestone($milestone, auth()->user());
    return back()->with('success', 'Milestone cancelled.');
}
```

**`JobPostingsController::retryMilestonePayment` NEW:**
```php
public function retryMilestonePayment(BpMilestone $milestone) {
    if (!$milestone->payment_failed_at) abort(422);
    $this->payouts->chargeMilestone($milestone);
    return back()->with('success', 'Payment retried.');
}
```

**`JobPostingsController::retryContractUpfront` NEW:**
Similar retry for upfront charge failures.

**BP `ProposalsController::submit`:** Use updated FormRequest.

### FormRequest additions

**`PostBpJobRequest`:** Add rules
```php
'default_payment_structure'   => 'required|in:full_upfront,split,per_milestone,on_completion',
'default_upfront_percentage'  => 'required_if:default_payment_structure,split|integer|min:1|max:99',
'default_terms_note'          => 'nullable|string|max:5000',
'allow_on_completion'         => 'nullable|boolean',
```

**`SubmitBpProposalRequest`:** Add rules
```php
'proposed_payment_structure'   => 'required|in:full_upfront,split,per_milestone,on_completion',
'proposed_upfront_percentage'  => 'required_if:proposed_payment_structure,split|integer|min:1|max:99',
'proposed_terms_note'          => 'nullable|string|max:5000',
'terms_source'                 => 'required|in:provider_default,bp_proposed',
'agree_terms'                  => 'required|accepted',
```

**`HireBpRequest`:** Add rules
```php
'terms_countered'              => 'nullable|boolean',
'committed_payment_structure'  => 'nullable|in:full_upfront,split,per_milestone,on_completion',
'committed_upfront_percentage' => 'nullable|integer|min:1|max:99',
'committed_terms_note'         => 'nullable|string|max:5000',
```

### Route changes
```php
// Rev 2 additions
Route::post('/support-services/contracts/{contract}/complete',
            [JobPostingsController::class, 'completeContract'])
     ->name('jobs.contract.complete');

Route::post('/support-services/contracts/{contract}/milestones/{milestone}/cancel',
            [JobPostingsController::class, 'cancelMilestone'])
     ->name('jobs.contract.milestones.cancel');

Route::post('/support-services/contracts/{contract}/milestones/{milestone}/retry-payment',
            [JobPostingsController::class, 'retryMilestonePayment'])
     ->name('jobs.contract.milestones.retry-payment');

Route::post('/support-services/contracts/{contract}/retry-upfront',
            [JobPostingsController::class, 'retryContractUpfront'])
     ->name('jobs.contract.retry-upfront');

// Rev 2 deprecated (controllers return 410)
Route::post('/support-services/contracts/{contract}/fund',
            [JobPostingsController::class, 'fundContract'])
     ->name('jobs.contract.fund');
Route::post('/support-services/contracts/{contract}/milestones/{milestone}/fund',
            [JobPostingsController::class, 'fundMilestone'])
     ->name('jobs.contract.milestones.fund');
```

### Verify
```bash
php artisan route:list | grep -E "jobs.contract.(complete|milestones.cancel|milestones.retry|retry-upfront)"
# All four visible

# Manual API test
curl -X POST /provider/support-services/contracts/{legacy_contract}/fund
# → 410 Gone
```

### Ship gate
API fully functional for Rev 2. Old Vue keeps hitting `/fund` → gets 410 (breaks the old workflow — but W8+ removes the buttons that call it).

---

## Wave 8 — Vue Deletions + Renames + Shared Component Reuse (1.5 hr)

### Goal
Clean up dead Vue files. Import shared components. Prepare for the modal work in W9–10.

### Files
- **DELETE:** `resources/js/components/modals/FundContractModal.vue`
- **DELETE:** `resources/js/components/modals/FundMilestoneModal.vue`
- **RENAME:** `resources/js/components/modals/MilestoneRefundModal.vue` → `MilestoneCancelModal.vue`
- Remove all imports of the deleted modals from all pages that reference them
- `resources/js/components/ui/PaymentTermsInline.vue` **(new IF Clinical W5 hasn't shipped, else already exists)**
- `resources/js/components/ui/CounterTermsInline.vue` **(new IF Clinical W5 hasn't shipped, else already exists)**

### Import cleanup
Grep for both deleted modals and strip the imports + tags:
```bash
grep -rn "FundContractModal\|FundMilestoneModal" resources/js/
# Expected files: SupportServices.vue, Finances.vue, ContractModal.vue,
#                 BpContractDetailModal.vue
```
For each hit: remove the `import`, remove the `<Fund*Modal>` tag, remove any `v-model` state ref.

### `MilestoneCancelModal.vue` update
Update body copy:
- Title: "Refund Milestone" → "Cancel Milestone"
- Body: "You will be refunded" → "This milestone will be cancelled — no payment was made"
- Confirm button: "Refund" → "Cancel Milestone"
- POST route: `provider.jobs.contract.milestones.refund` → `provider.jobs.contract.milestones.cancel`

### If Clinical W5 hasn't shipped — build shared components here
Build `PaymentTermsInline` + `CounterTermsInline` (specs in Clinical W5 doc). Adds ~2 hr to this wave.

### Verify
```bash
# Should be zero:
find resources/js -name "FundContractModal.vue"
find resources/js -name "FundMilestoneModal.vue"
find resources/js -name "MilestoneRefundModal.vue"

# Should exist:
ls resources/js/components/modals/MilestoneCancelModal.vue
ls resources/js/components/ui/PaymentTermsInline.vue
ls resources/js/components/ui/CounterTermsInline.vue

# No unresolved imports:
grep -rn "FundContractModal\|FundMilestoneModal" resources/js/ && echo "FAIL" || echo "PASS"

# Node syntax check on rename
node --check resources/js/components/modals/MilestoneCancelModal.vue
```

### Ship gate
Nothing calls the deleted modals anymore. Safe to build the new UX on top.

---

## Wave 9 — Vue Provider-Side Modals (3.0 hr)

### Goal
All provider-side Support Services modals use new terms + direct-charge language.

### Files
- `resources/js/components/modals/HireModal.vue` **(edit — CounterTermsInline)**
- `resources/js/components/modals/PostJobModal.vue` **(edit — Default Payment Terms panel)**
- `resources/js/components/modals/SignContractModal.vue` **(edit — read-only terms panel + agreement checkbox)**
- `resources/js/components/modals/MilestoneReviewModal.vue` **(edit — approve action charge confirmation)**
- `resources/js/components/modals/ContractModal.vue` **(edit — remove escrow bar, add terms + progress bar)**
- `resources/js/components/modals/BpContractDetailModal.vue` **(edit — same treatment)**

### `HireModal.vue`
Show BP's proposed terms in a read-only summary block above `CounterTermsInline`:
```
Proposed by BP:
  Structure: 40% upfront + 60% completion
  Note: "Weekly check-ins required."
```
Add `CounterTermsInline` component below (props: `requestedTerms`, `modelValue`, `allowedStructures = ['full_upfront','split','per_milestone','on_completion']`).
Extend `hireForm`:
```js
terms_countered:              false,
committed_payment_structure:  null,
committed_upfront_percentage: null,
committed_terms_note:         null,
```
On submit, flatten into POST payload.

Preview area at bottom: "This will charge $X to your card on signing" (recomputed reactively).

### `PostJobModal.vue`
Add "Default Payment Terms" panel:
- Structure radio (4 options)
- Upfront % input (visible when `split`)
- Terms note textarea
- Toggle: "Allow BPs to propose 'pay on completion'" (visible only when `payment_type = one_time`)

Extend `jobForm` with 4 default_* fields.

### `SignContractModal.vue`
Read-only committed terms panel at top:
```
Committed terms:
  Structure:  Split — 30% upfront ($3,000) + 70% remaining ($7,000)
  Terms note: {terms_note or "None specified"}
  Total:      $10,000

By signing you authorize the upfront charge of $3,000 to your card
if you are the second signer.
```
Agreement checkbox: "I authorize this contract and any charges specified in the committed terms. Payment routes directly to the {other party}'s Stripe account. Aegis does not hold funds."

### `MilestoneReviewModal.vue`
On approve action, show confirmation step before submitting:
```
Approving this milestone will:
  • Charge $2,000 to your card
  • Send $2,000 direct to {bp name}'s Stripe Connect account
  • Mark this milestone paid

Aegis does not hold these funds.

☐ I authorize this payment.
```
Revision + dispute actions have no such confirmation.

### `ContractModal.vue` + `BpContractDetailModal.vue`
- Remove escrow bar (three chips: In Escrow, Released, Unfunded)
- Add committed terms panel showing structure summary + terms note
- Add payment progress: `paid_cents / total_value_cents` bar with tooltip breakdown
- Milestone rows use new lifecycle status badges:
  - `pending` → "Awaiting work"
  - `in_progress` → "In progress"
  - `submitted` → "Awaiting review"
  - `revision_requested` → "Revision requested"
  - `approved` (transient) → "Approved — payment fired"
  - `paid` → "Paid ({date})"
  - `prepaid` → "Pre-paid (contract paid in full upfront)"
  - `payment_failed` → "⚠️ Payment failed — [Retry payment] button"
  - `cancelled` → "Cancelled"
- Remove all "Fund escrow" buttons entirely

### Verify
- Manual: open Applications tab → click Hire → see BP's proposed terms → toggle counter → change to different structure → submit → check backend contract row has committed_* set correctly and `terms_source = provider_countered`
- Open a `pending_signature` contract → SignContractModal shows terms panel
- Sign as second party → contract → active; if structure was `full_upfront` or `split`: verify charge fired (check `contract.upfront_charge_intent_id`)
- Open a submitted milestone → MilestoneReviewModal → click Approve → see confirmation with charge amount → submit → milestone → `paid`

### Ship gate
Provider-side modals fully wired. BP side is W10.

---

## Wave 10 — Vue BP-Side Modals + Pages (2.5 hr)

### Goal
BP portal reflects new flow — proposals include terms, no "await funding" states, milestones show new lifecycle.

### Files
- `resources/js/components/modals/BpProposeModal.vue` **(new OR extract from inline propose form)**
- `resources/js/pages/business-partner/FindJobs.vue` **(edit — terms chip on job cards + wire BpProposeModal)**
- `resources/js/pages/business-partner/Contracts.vue` **(edit — remove awaiting-funding state)**
- `resources/js/pages/business-partner/Milestones.vue` **(edit — remove funded/awaiting states)**
- `resources/js/pages/business-partner/Finances.vue` **(edit — direct-charge payout tracking)**

### `BpProposeModal.vue`
Extract the existing inline propose form on FindJobs into a proper modal. Contents:
- Cover letter, bid amount, timeline, portfolio URL (as Rev 1)
- `PaymentTermsInline` component with props from job posting
- Agreement checkbox: "I agree to these terms. Payment routes directly from provider to me via Stripe Connect; Aegis is not the paymaster."

**Props:**
```
jobId, jobTitle,
providerDefaultStructure,
providerDefaultUpfrontPct,
providerDefaultTermsNote,
providerAllowOnCompletion
```

**Emits:** `submit` with the flattened form payload.

Extend `proposeForm`:
```js
proposed_payment_structure:  '',
proposed_upfront_percentage: 30,
proposed_terms_note:         '',
terms_source:                'provider_default',
agree_terms:                 false,
```

### `FindJobs.vue`
- Each job card shows terms chip (provider's default) — chip label from `PaymentStructure::chipLabel()` shape sent by backend
- "Apply" button opens `BpProposeModal`, passing the 4 new prop values from the job data

### `Contracts.vue`
- Remove "Awaiting funding by provider" state
- Contracts show only: "Awaiting signature" (either party) → "Active — you may begin work"
- SignContractModal shows committed terms + agreement

### `Milestones.vue`
- Remove "🔒 Awaiting funding by provider" state
- Milestone rows use new lifecycle: `pending / in_progress / submitted / approved / paid / prepaid / payment_failed / cancelled`
- For `paid` milestones: "Paid on {date} — funds in your Stripe Connect balance"
- For `prepaid`: "Pre-paid at contract signing — deliver work when ready"

### `Finances.vue` (BP portal)
- Remove escrow-based payout tracking
- Replace with direct-charge payout tracking (data already exists in `bp_payouts` table)
- Copy: "Escrow released" → "Payment received"

### Verify
- Log in as `bp_jamal`
- Browse `/business/find-jobs` — cards show terms chips
- Click Apply on a `p_sarah` posting → BpProposeModal opens with `p_sarah`'s defaults preloaded
- Toggle "Propose different terms" → change structure → submit
- Verify `bp_proposals` row has `terms_source = bp_proposed`
- Log in as `p_sarah` → see the proposal in the pipeline with the proposed terms chip

### Ship gate
BP-side fully functional. Both parties can complete a full contract lifecycle through the new UX.

---

## Wave 11 — Vue Tables + Pages + Copy Sweep (2.5 hr)

### Goal
Provider-side listing pages + shared table components + public profile + final copy sweep.

### Files
- `resources/js/pages/provider/SupportServices.vue` **(edit — proposals list, applications with terms chips)**
- `resources/js/pages/provider/Finances.vue` **(edit — BP tab, swap escrowSummary prop for paymentSummary)**
- `resources/js/components/ui/BpFinanceTable.vue` **(edit — remove escrow strip, add payment stat chips)**
- `resources/js/components/ui/BpContractRow.vue` **(edit — remove escrow line, add terms chip + progress)**
- `resources/js/components/ui/BpMilestoneRow.vue` **(edit — new status lifecycle)**
- `resources/js/pages/public/BusinessProfile.vue` **(edit — service tiles + engagement blocks with terms chips)**
- Backend `Provider/FinancesController.php` **(edit — expose `paymentSummary` prop, deprecate `escrowSummary`)**

### `SupportServices.vue`
- Remove any "Fund escrow" button leftovers
- Applications tab: each application card shows the BP's proposed terms chip
- Add filter dropdown: "Filter by structure" (all / full_upfront / split / per_milestone / on_completion)

### `Finances.vue` BP tab
Swap `escrowSummary` prop for `paymentSummary`:
```
paymentSummary: {
    total_upfront_charged_cents,
    total_milestones_paid_cents,
    total_pending_cents,       // approved but payment_failed
    active_contract_count,
}
```
Backend controller change needed — add computation in Provider/FinancesController's page data assembly.

### `BpFinanceTable.vue`
- Remove `escrowSummary` prop
- Add `paymentSummary` prop
- Replace escrow chip strip with payment stat chips
- Remove `.bpft-escrow-strip` CSS block

### `BpContractRow.vue`
- Remove `.bpcr-escrow-line`
- Add `.bpcr-terms-chip` (structure summary)
- Payment progress bar: `paid_cents / total_value_cents`

### `BpMilestoneRow.vue`
- Remove all "In Escrow" / "Funded" / "Released" labels
- Add lifecycle badges per §5.8 above
- Add "Retry payment" button when `payment_failed`

### `BusinessProfile.vue` (public)
Engagement blocks + service tiles now include terms chips where applicable. The BP-inbound public profile doesn't propose their own payment structure — they're the *provider* of the service on their profile, but for Support Services flows the *practitioner* posts the job. On this page the terms chip is contextual to which engagement path (e.g. "Book Consultation Call" tile shows the BP's own default consultation terms).

### Copy audit — zero-hit grep
```bash
grep -rn -iE "escrow|held in trust|funds released|aegis balance|fund escrow|fund milestone" \
  resources/js/pages/provider/SupportServices.vue \
  resources/js/pages/provider/Finances.vue \
  resources/js/pages/business-partner/ \
  resources/js/pages/public/BusinessProfile.vue \
  resources/js/components/modals/ \
  resources/js/components/ui/Bp*.vue
```
Zero hits acceptable in Support Services scope (one acceptable ref: `bp_escrow_ledger` mention in admin reconciliation view, but that's Wave 12).

### Verify
Full end-to-end walk on demo:
1. `p_sarah` posts a job with `split @ 30%` default + `allow_on_completion=0`
2. `bp_jamal` browses `/business/find-jobs` → sees terms chip → applies with `on_completion` proposal → validation error (not allowed)
3. `bp_jamal` re-applies with `split @ 25%` proposal
4. `p_sarah` sees `bp_jamal`'s application with terms chip → hires with counter to `split @ 40%`
5. Both sign → contract → `active`; upfront charge (40%) fires direct-to-`bp_jamal`
6. `bp_jamal` submits milestone → `p_sarah` approves → per-milestone charge fires direct-to-`bp_jamal`
7. All milestones paid → contract → `completed`
8. Cancel scenario: create contract, sign, cancel post-upfront → refund fires via `reverse_transfer`

### Ship gate
Entire Support Services UI matches the new flow. Emails still say "escrow" — that's W12.

---

## Wave 12 — Emails + Events + Activity + Auto-Approve Job + Legacy Admin (3.0 hr)

### Goal
All notifications match new terminology. Auto-approve job replaces auto-release. Admin has a view for legacy escrow reconciliation.

### Files

**Blade renames + deletes:**
- **DELETE:** `resources/views/emails/business/53-milestone-funded.blade.php`
- **RENAME:** `52-escrow-funded.blade.php` → `52-contract-upfront-paid.blade.php` (1-line alias at old path)
- **RENAME:** `55-milestone-approved.blade.php` → `55-milestone-approved-and-paid.blade.php` (alias)
- **RENAME:** `57-milestone-auto-released.blade.php` → `57-milestone-auto-approved.blade.php` (alias)
- **RENUMBER:** old `58-milestone-review-reminder.blade.php` → `59-milestone-review-reminder.blade.php` (alias at old path)

**New blades:**
- `resources/views/emails/business/58-milestone-auto-approve-failed.blade.php` **(new)**
- `resources/views/emails/partials/direct-to-bp-disclosure.blade.php` **(new)**

**Templates to update:**
- `40-proposal-accepted.blade.php` — include committed terms recap
- `50-contract-pending-signature.blade.php` — include committed terms recap
- `51-contract-fully-executed.blade.php` — remove "please fund escrow" line
- `52-contract-upfront-paid.blade.php` — direct-to-BP receipt
- `55-milestone-approved-and-paid.blade.php` — direct-to-BP receipt
- `bp/32-support-request-received.blade.php` — include BP's proposed terms recap
- All money templates — `@include('emails.partials.direct-to-bp-disclosure')`

**Event classes (new):**
- `app/Events/Business/ContractUpfrontCharged.php`
- `app/Events/Business/ContractCompletionCharged.php`
- `app/Events/Business/ContractUpfrontRefunded.php`
- `app/Events/Business/MilestonePaid.php` (fires alongside `MilestoneReleased` for one cycle)
- `app/Events/Business/MilestoneAutoApproved.php`
- `app/Events/Business/MilestoneAutoApproveFailed.php`
- `app/Events/Business/MilestonePaymentFailed.php`

**Listener + provider:**
- `app/Providers/AppServiceProvider.php` **(edit — 7 new `Event::listen()`)**
- `app/Listeners/SendEmailNotificationListener.php` **(edit — 7 new match cases)**

**Activity log renames** (in `PayoutService`, `ContractService`, `ProposalService`):
| Old action | New action |
|---|---|
| `escrow_funded` (contract) | `contract_upfront_paid` |
| `escrow_funded` (milestone) | *(removed)* |
| `milestone_released` | `milestone_paid` |
| `milestone_auto_released` | `milestone_auto_approved` |
| `escrow_refunded` | `contract_upfront_refunded` |

**New actions:** `contract_terms_committed`, `milestone_payment_failed`

**Job + scheduler:**
- `app/Jobs/MilestoneAutoApproveJob.php` **(new)**
- `routes/console.php` **(edit — add hourly `MilestoneAutoApproveJob` schedule)**
- `app/Jobs/MilestoneAutoReleaseJob.php` **(edit — add legacy-only guard `whereNull('contract.payment_structure')`)**

**Legacy admin view:**
- `app/Http/Controllers/Admin/LegacyEscrowController.php` **(new)**
- `resources/js/pages/admin/LegacyEscrow.vue` **(new — lists contracts with `escrow_funded_cents > escrow_released_cents + escrow_refunded_cents`, allows admin manual `EscrowService::releaseMilestone` / `refundMilestone` / `splitResolution`)**
- Route in `routes/web.php` for admin

### `MilestoneAutoApproveJob`
```php
class MilestoneAutoApproveJob implements ShouldQueue
{
    public function handle(ContractService $contracts): void
    {
        $milestones = BpMilestone::where('status', MilestoneStatus::Submitted->value)
            ->whereNotNull('auto_approve_at')
            ->where('auto_approve_at', '<=', now())
            ->whereHas('contract', fn($q) => $q->whereNotNull('payment_structure'))
            ->with('contract.bp', 'contract.practitioner')
            ->limit(100)
            ->get();

        foreach ($milestones as $ms) {
            try {
                $contracts->approveMilestone($ms, null /* system */);
                event(new MilestoneAutoApproved($ms->fresh()));
            } catch (\Throwable $e) {
                event(new MilestoneAutoApproveFailed($ms->fresh(), $e->getMessage()));
            }
        }
    }
}
```

Scheduler:
```php
Schedule::job(new MilestoneAutoApproveJob)->hourly()->name('milestone-auto-approve')->withoutOverlapping();
Schedule::job(new MilestoneAutoReleaseJob)->hourly()->name('milestone-auto-release-legacy')->withoutOverlapping();
```

### `MilestoneAutoReleaseJob` guard
```php
BpMilestone::where('status', 'submitted')
    ->whereNotNull('auto_release_at')
    ->where('auto_release_at', '<=', now())
    ->whereHas('contract', fn($q) => $q->whereNull('payment_structure'))  // Rev 2 gate
    ->...
```

### Disclosure partial
```blade
{{-- resources/views/emails/partials/direct-to-bp-disclosure.blade.php --}}
<p style="font-size:11px;color:#888;line-height:1.5;margin-top:20px;padding:10px;background:#f5f5f5;border-radius:4px">
  Payment routes directly to the Business Partner's Stripe Connect account.
  Aegis does not hold, escrow, or process these funds. This transaction is
  between you and the Business Partner directly. If a dispute arises, Aegis
  provides mediation tools but is not a party to the transaction.
</p>
```

### Activity log — audit
Every `ActivityService::log()` call in Support Services must have position 11 = `'log'|'notification'`, position 12 = actor ID. Grep after edits:
```bash
grep -rn "ActivityService.*log(" app/Services/ContractService.php \
                                  app/Services/PayoutService.php \
                                  app/Services/ProposalService.php \
                                  app/Services/BpJobService.php \
                                  app/Jobs/MilestoneAutoApproveJob.php
```
Every hit reviewed manually.

### Verify
- Trigger contract signing (both parties) with `split @ 30%` — verify:
  - `52-contract-upfront-paid` email lands with disclosure
  - Activity row has action `contract_upfront_paid`
- Manually set a submitted milestone's `auto_approve_at` to yesterday
- Run `php artisan schedule:test` or run job directly
  - Milestone → paid (or `payment_failed` if card mock declined)
  - Both parties emailed
- Legacy admin: log in as `admin_root` → visit `/admin/legacy-escrow-reconciliation` → sees legacy contracts if any exist

### Ship gate
Rev 1 → Rev 2 migration complete. Ship after W11 verified in staging.

---

## Cleanup PR (one release cycle later, ~2 hr)

- Remove deprecated `EscrowService` methods entirely (retain the class only for reading legacy `bp_escrow_ledger` rows)
- Remove deprecated events (`EscrowFunded`, `ContractFullyFunded`, `MilestoneReleased`, `MilestoneAutoReleased`)
- Remove template aliases (52-escrow-funded, 55-milestone-approved, 57-milestone-auto-released, 58-milestone-review-reminder → 59)
- Remove `/fund` and `/milestones/{}/fund` routes
- Remove modal re-export shims
- Once all legacy escrow contracts reconciled: drop `bp_escrow_ledger` write path entirely (still read for admin views)
- Update tests

---

## Dependency Chart

```
W1 (migrations + enum extension)
  └─▶ W2 (models + backfill)
        ├─▶ W6 (proposal + job + escrow guards)
        │     └─▶ W7 (controllers + FormRequests + routes)
        │           └─▶ W8 (Vue deletions + renames)
        │                 ├─▶ W9 (provider modals)
        │                 └─▶ W10 (BP modals + pages)
        │                       └─▶ W11 (tables + pages + copy sweep)
        └─▶ W3 (PayoutService)
              └─▶ W4 (ContractService::sign rewrite)
                    └─▶ W5 (ContractService approve/complete/cancel)
                          └─▶ (already contributes to W7 above)

W7 also ─▶ W12 (emails + events + auto-approve job + legacy admin)
```

**Critical path (backend only):** W1 → W2 → W3 → W4 → W5 → W7 → W12 (14.5 hr sequential)
**Critical path (frontend only):** W1 → W2 → W6 → W7 → W8 → W9 → W11 (13.0 hr sequential)
**Fastest with 2 devs:** ~14 hr (backend + Vue in parallel from W3 onward)

---

## Open Questions (decide before W1)

1. **No new `bp_escrow_ledger` writes for Rev 2 contracts.** Default: yes, table read-only for legacy. If Chapman wants a parallel direct-charge ledger for audit even in Rev 2, easy addition to W3 (add a `writeDirectChargeLedger()` helper).

2. **`full_upfront` for milestone contracts — dispute refund granularity.** BP prepaid, provider disputes deliverable. Admin can refund via `refundBpCharge` against the original upfront PI. But that's contract-level, not per-milestone. Snapshot has numbers to compute partial refund. Confirm: partial via computation, or full-contract refund only?

3. **`allow_on_completion` — provider risk warning.** UI needs strong warning when provider toggles this. Draft: *"Enabling this lets BPs propose to be paid only after work is complete. Balances BP trust exposure — nothing paid until you approve, but if BP doesn't deliver you have no lever besides not paying."*

4. **Cancel-and-refund on `full_upfront`.** Provider cancels day 3, BP already did 30% of work. Recommendation: **full refund to provider, dispute is BP's remedy**. Needs Chapman sign-off.

5. **Two hourly jobs during transition.** After Rev 2 live, `MilestoneAutoReleaseJob` still runs hourly against legacy contracts. Confirm acceptable for one release cycle.

---

*Doc rev 2 — waves format. Any changes to the module doc during execution should be mirrored here.*
