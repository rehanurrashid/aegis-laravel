# Aegis — Chapman Decisions Implementation Plan

**Date:** 2026-07-19 (revised)
**Source:** Chapman email reply + pricing doc `AegisPricingUpdated07172026correct.docx` + follow-up clarifications
**Scope:** Pricing overhaul + provider-as-CS/SS policy + Stripe reconfiguration + code changes across 12+ files

---

## 0. Key corrections from v1 of this plan

1. **`practice_business` is NOT a new tier.** Practice Business = Practice tier ($79) + CS Business Add-On ($25). No new `users.tier` enum value. No new portal. No new role. The add-on unlocks the higher CS cap, same pattern as MAAT addon.
2. **`TIER_PRACTICE_MAX_SS` stays at 4** until Chapman explicitly confirms otherwise.
3. **Annual display prices** show exact decimals everywhere (`$35.75/mo`, `$65.83/mo`) — no rounding.
4. **`available_as_cs`** meta key does NOT exist in repo yet. Must be built from scratch.
5. **Stripe structure for Practice + CS addon** = two SubscriptionItems on one subscription (mirrors `toggleMaatAddon()` exactly).

---

## 1. Finalized pricing

| Product | Monthly | Annual | Notes |
|---|---|---|---|
| **Continuity Access** | $39/mo | $429/yr | 1 mo free · serve as CS for 1 |
| **Continuity Practice** | $79/mo | $790/yr | 2 mo free · serve as CS for 3 |
| **Practice CS Add-On** | +$25/mo | +$250/yr | Practice tier only · unlocks CS cap of 43 |
| **Continuity Steward Business** | $49/mo | $490/yr | 2 mo free · up to 40 practitioners |
| **Business Partner** | $69/mo | $690/yr | 2 mo free · unchanged |

**Additional CS slot add-ons (annual only, post-launch):**
- Access: +$70/yr per additional CS (up to 2 more)
- Practice: +$30/yr per additional CS (unlimited)

**MAAT Services (external — not in-platform at launch):**
- Continuity Plan Setup: $1,500 one-time
- Continuity Assurance: $75/mo · $900/yr
- Critical Moment Response Reserve: $3,500 reserve

**Current system prices (WRONG — must be replaced):**

| Product | In system now | Correct |
|---|---|---|
| Access monthly | $29 | **$39** |
| Access annual | $276/yr | **$429/yr** |
| Practice monthly | $49 | **$79** |
| Practice annual | $468/yr | **$790/yr** |
| CS Business annual | $429/yr | **$490/yr** |
| BP monthly/annual | $69 / $690 | ✅ no change |

---

## 2. Chapman-confirmed policy decisions

| # | Decision | Confirmed value |
|---|---|---|
| 1 | Available as CS toggle in Settings | ✅ Keep — add tier badge in Find CS card |
| 2 | Available as SS toggle in Settings | ❌ Remove entirely |
| 3 | Find CS tab in Network | ✅ Keep — remove clinical license info from cards |
| 4 | Find SS tab in Network | ❌ Remove entirely |
| 5 | Circular CS blocking | ❌ Remove — mutual arrangements allowed |
| 6 | Provider-as-CS cap: Access | 1 practitioner |
| 7 | Provider-as-CS cap: Practice | 3 practitioners |
| 8 | Provider-as-CS cap: Practice + CS Add-On | 43 practitioners |
| 9 | Beyond cap — Access | Upgrade to Business CS ($49/mo) |
| 10 | Beyond cap — Practice | Add CS Add-On ($25/mo) |
| 11 | Provider-as-CS disclaimer at designation | ❌ Remove |
| 12 | Provider-as-SS | Anyone, free, no cap, invite-only |
| 13 | SS invite flow | name+email of existing Aegis user OR external email invite |
| 14 | SS-facing copy | Remove practitioner-specific language |
| 15 | TIER_PRACTICE_MAX_SS | Stays at 4 — no change until Chapman confirms |

---

## 3. Stripe changes

### 3.1 New products to create in Stripe Dashboard

| Product name | Env var | Amount |
|---|---|---|
| Practice CS Add-On Monthly | `STRIPE_PRICE_PRACTICE_CS_ADDON_MONTHLY` | $25/mo |
| Practice CS Add-On Annual | `STRIPE_PRICE_PRACTICE_CS_ADDON_ANNUAL` | $250/yr |

Post-launch (do not create at launch):
- Additional CS Slot — Access: `STRIPE_PRICE_ADDON_CS_ACCESS_ANNUAL` → $70/yr
- Additional CS Slot — Practice: `STRIPE_PRICE_ADDON_CS_PRACTICE_ANNUAL` → $30/yr

### 3.2 Existing products — create new price IDs at correct amounts

Stripe prices are immutable. Create new price IDs and swap env vars. Old price IDs stay valid for founding subscribers.

| Product | Old price ID (keep for founders) | New amount | Env var to update |
|---|---|---|---|
| Access Monthly | `price_1TqSldHnj73y5cBfCrKtyE5Z` | $39/mo | `STRIPE_PRICE_ACCESS_MONTHLY` |
| Access Annual | `price_1TqSpNHnj73y5cBfAReG6y0D` | $429/yr | `STRIPE_PRICE_ACCESS_ANNUAL` |
| Practice Monthly | `price_1TqSraHnj73y5cBfjxtPipio` | $79/mo | `STRIPE_PRICE_PRACTICE_MONTHLY` |
| Practice Annual | `price_1TqTCuHnj73y5cBfsCRcBDYX` | $790/yr | `STRIPE_PRICE_PRACTICE_ANNUAL` |
| CS Business Annual | `price_1TqSu2Hnj73y5cBf5NEsLOTi` | $490/yr | `STRIPE_PRICE_CS_BUSINESS_ANNUAL` |

### 3.3 Unchanged Stripe products

| Env var | Amount |
|---|---|
| `STRIPE_PRICE_CS_BUSINESS_MONTHLY` | $49/mo ✅ |
| `STRIPE_PRICE_BP_MONTHLY` | $69/mo ✅ |
| `STRIPE_PRICE_BP_ANNUAL` | $690/yr ✅ |
| `STRIPE_PRICE_MAAT_MONTHLY` | $29/mo ✅ |
| `STRIPE_PRICE_MAAT_ANNUAL` | $276/yr ✅ |

### 3.4 How Practice + CS Add-On billing works

Mirrors MAAT addon pattern exactly:
- User has Practice subscription (`$79/mo` base SubscriptionItem)
- User adds CS Add-On → `toggleCsAddon(true)` adds `$25/mo` as second SubscriptionItem on same `'default'` subscription
- Stripe invoice shows: Practice $79 + CS Add-On $25 = $104 total
- Webhook `subscription.updated` fires → checks all SubscriptionItem price IDs → detects CS addon → sets `users.cs_addon = 1`
- `users.tier` stays `'practice'` throughout — no enum change needed
- Removing add-on: `toggleCsAddon(false)` removes the SubscriptionItem → webhook sets `users.cs_addon = 0`

---

## 4. `.env` changes

### 4.1 Replace price IDs (create new ones in Stripe first)

```env
# ── Continuity Access (new prices) ───────────────────────────────────────
STRIPE_PRICE_ACCESS_MONTHLY=price_NEW_39mo
STRIPE_PRICE_ACCESS_ANNUAL=price_NEW_429yr

# ── Continuity Practice (new prices) ─────────────────────────────────────
STRIPE_PRICE_PRACTICE_MONTHLY=price_NEW_79mo
STRIPE_PRICE_PRACTICE_ANNUAL=price_NEW_790yr

# ── CS Business annual (new price) ───────────────────────────────────────
STRIPE_PRICE_CS_BUSINESS_ANNUAL=price_NEW_490yr

# ── Practice CS Add-On (NEW) ──────────────────────────────────────────────
STRIPE_PRICE_PRACTICE_CS_ADDON_MONTHLY=price_NEW_25mo
STRIPE_PRICE_PRACTICE_CS_ADDON_ANNUAL=price_NEW_250yr
```

### 4.2 Tier limit and cap vars

```env
# ── Steward slot caps (own plan's CS/SS invite slots) ────────────────────
TIER_ACCESS_MAX_CS=1          # was 2 → Chapman doc says 1
TIER_ACCESS_MAX_SS=2          # unchanged
TIER_PRACTICE_MAX_CS=2        # unchanged
TIER_PRACTICE_MAX_SS=4        # unchanged until Chapman confirms 2

# ── Provider-as-CS caps (how many other practices they can serve AS CS) ──
PROVIDER_AS_CS_MAX_ACCESS=1
PROVIDER_AS_CS_MAX_PRACTICE=3
PROVIDER_AS_CS_MAX_PRACTICE_CS_ADDON=43
```

---

## 5. `config/aegis.php` changes

### 5.1 Update pricing.practitioner.access

```php
'access' => [
    'name'               => 'Continuity Access',
    'tagline'            => 'Foundational continuity infrastructure.',
    'monthly_cents'      => 3900,     // was 2900
    'annual_cents'       => 3575,     // $429/yr ÷ 12 = $35.75
    'annual_total_cents' => 42900,    // was 27600
    'save_label'         => '1 month free',
    'is_popular'         => false,
    'features'           => [
        'Continuity Plan Builder',
        'All 7 critical incident types',
        'Document Vault (4 zones)',
        '1 Continuity Steward invitation',
        '2 Support Steward invitations',
        'Serve as CS for 1 practitioner',
        'Shadow Network (limited)',
        'Secure messaging · Activity log',
    ],
],
```

### 5.2 Update pricing.practitioner.practice

```php
'practice' => [
    'name'               => 'Continuity Practice',
    'tagline'            => 'Full toolkit. The standard for active practices.',
    'monthly_cents'      => 7900,     // was 4900
    'annual_cents'       => 6583,     // $790/yr ÷ 12 = $65.83
    'annual_total_cents' => 79000,    // was 46800
    'save_label'         => '2 months free',
    'is_popular'         => true,
    'features'           => [
        'Everything in Access, plus:',
        'Up to 2 Continuity Steward invitations',
        'Up to 2 Support Steward invitations',
        'Serve as CS for up to 3 practitioners',
        'Additional CS slots at $30/yr each',
        'Referrals — send & receive',
        'Full Integrative Network search',
        'Business Partner directory & Job Postings',
        'Priority support & onboarding call',
    ],
],
```

### 5.3 Add pricing.practice_cs_addon (new)

```php
'practice_cs_addon' => [
    'name'               => 'CS Business Add-On',
    'tagline'            => 'Serve as CS for up to 43 practitioners. Practice tier only.',
    'requires_tier'      => 'practice',
    'monthly_cents'      => 2500,     // +$25/mo
    'annual_cents'       => 2083,     // $250/yr ÷ 12 = $20.83
    'annual_total_cents' => 25000,    // $250/yr
    'save_label'         => '',
    'features'           => [
        'Serve as CS for up to 43 practitioners',
        'Unlocks full Business CS capacity',
        'No separate CS account needed',
        'Billed alongside Practice subscription',
    ],
],
```

### 5.4 Update pricing.continuity_steward_business annual

```php
'annual_cents'       => 4083,    // $490/yr ÷ 12 = $40.83
'annual_total_cents' => 49000,   // was 42900
'save_label'         => '2 months free',
```

### 5.5 Update stripe_price_to_tier

```php
'stripe_price_to_tier' => [
    env('STRIPE_PRICE_ACCESS_MONTHLY')              => 'access',
    env('STRIPE_PRICE_ACCESS_ANNUAL')               => 'access',
    env('STRIPE_PRICE_PRACTICE_MONTHLY')            => 'practice',
    env('STRIPE_PRICE_PRACTICE_ANNUAL')             => 'practice',
    env('STRIPE_PRICE_BP_MONTHLY')                  => 'business_partner',
    env('STRIPE_PRICE_BP_ANNUAL')                   => 'business_partner',
    env('STRIPE_PRICE_CS_BUSINESS_MONTHLY')         => 'cs_business',
    env('STRIPE_PRICE_CS_BUSINESS_ANNUAL')          => 'cs_business',
    env('STRIPE_PRICE_MAAT_MONTHLY')                => 'maat_addon',
    env('STRIPE_PRICE_MAAT_ANNUAL')                 => 'maat_addon',
    // CS addon intentionally NOT mapped here — does not change users.tier
    // Detected separately in StripeEventListener → sets users.cs_addon
],
```

### 5.6 Update tier_limits

```php
'tier_limits' => [
    'access' => [
        'max_continuity_stewards' => (int) env('TIER_ACCESS_MAX_CS', 1),   // was 2
        'max_support_stewards'    => (int) env('TIER_ACCESS_MAX_SS', 2),
        'referrals'               => false,
        'services_mode'           => false,
        'network_search'          => 'limited',
        'job_postings'            => false,
    ],
    'practice' => [
        'max_continuity_stewards' => (int) env('TIER_PRACTICE_MAX_CS', 2),
        'max_support_stewards'    => (int) env('TIER_PRACTICE_MAX_SS', 4), // unchanged
        'referrals'               => true,
        'services_mode'           => true,
        'network_search'          => 'full',
        'job_postings'            => true,
    ],
    // No practice_business tier — it stays 'practice' + cs_addon flag
],
```

### 5.7 Add provider_as_cs_caps (new section)

```php
/*
|--------------------------------------------------------------------------
| Provider-as-CS Caps
|--------------------------------------------------------------------------
| Caps how many OTHER practitioners a provider can serve as CS.
| Separate from tier_limits.max_continuity_stewards (their own plan's slots).
| When users.tier = 'practice' AND users.cs_addon = 1 → use practice_cs_addon cap.
|--------------------------------------------------------------------------
*/
'provider_as_cs_caps' => [
    'access'            => (int) env('PROVIDER_AS_CS_MAX_ACCESS', 1),
    'practice'          => (int) env('PROVIDER_AS_CS_MAX_PRACTICE', 3),
    'practice_cs_addon' => (int) env('PROVIDER_AS_CS_MAX_PRACTICE_CS_ADDON', 43),
],

'provider_as_cs_upgrade_paths' => [
    'access' => [
        'message'       => 'Your Access plan supports serving as CS for 1 practitioner. Upgrade to Business CS ($49/mo) to serve more.',
        'upgrade_to'    => 'cs_business',
        'monthly_cents' => 4900,
    ],
    'practice' => [
        'message'       => 'Your Practice plan supports serving as CS for 3 practitioners. Add the CS Business Add-On (+$25/mo) to serve up to 43.',
        'upgrade_to'    => 'practice_cs_addon',
        'monthly_cents' => 2500,
    ],
],
```

---

## 6. Backend code changes

### 6.1 New migration — `users.cs_addon`

```php
// [timestamp]_add_cs_addon_to_users_table.php
$table->tinyInteger('cs_addon')->default(0)->after('maat_addon');
```

Add to `User::$fillable`: `'cs_addon'`
Add to `User::$casts`: `'cs_addon' => 'boolean'`

### 6.2 `app/Listeners/StripeEventListener.php`

In `handleSubscriptionUpdated()`, after the MAAT addon detection block, add (same pattern):

```php
// Sync CS addon state
$csAddonMonthly = env('STRIPE_PRICE_PRACTICE_CS_ADDON_MONTHLY');
$csAddonAnnual  = env('STRIPE_PRICE_PRACTICE_CS_ADDON_ANNUAL');
$hasCsAddon = ($csAddonMonthly && in_array($csAddonMonthly, $itemPriceIds, true))
           || ($csAddonAnnual  && in_array($csAddonAnnual,  $itemPriceIds, true));

if ((bool) $user->cs_addon !== $hasCsAddon) {
    $user->update(['cs_addon' => $hasCsAddon ? 1 : 0]);
    Log::info('[STRIPE_WEBHOOK] CS addon state synced', [
        'user_id'      => $user->id,
        'has_cs_addon' => $hasCsAddon,
    ]);
}
```

### 6.3 `app/Services/SubscriptionService.php`

Add `toggleCsAddon()` mirroring `toggleMaatAddon()`:

```php
public function toggleCsAddon(User $user, bool $enable, string $billing = 'monthly'): User
{
    $priceId = $billing === 'annual'
        ? env('STRIPE_PRICE_PRACTICE_CS_ADDON_ANNUAL')
        : env('STRIPE_PRICE_PRACTICE_CS_ADDON_MONTHLY');

    if (!$priceId) {
        throw new \RuntimeException(
            'CS addon price not configured. Set STRIPE_PRICE_PRACTICE_CS_ADDON_MONTHLY/ANNUAL in .env.'
        );
    }

    if ($user->tier !== 'practice') {
        throw new \RuntimeException('CS Business Add-On requires the Continuity Practice plan.');
    }

    $sub = $user->subscription('default');
    if (!$sub) {
        throw new \RuntimeException('Cannot toggle CS addon — no active base subscription.');
    }

    if ($enable) {
        if (!$sub->hasPrice($priceId)) {
            $sub->addPrice($priceId);
        }
    } else {
        foreach ([
            env('STRIPE_PRICE_PRACTICE_CS_ADDON_MONTHLY'),
            env('STRIPE_PRICE_PRACTICE_CS_ADDON_ANNUAL'),
        ] as $pid) {
            if ($pid && $sub->hasPrice($pid)) {
                $sub->removePrice($pid);
            }
        }
    }

    $user->update(['cs_addon' => $enable ? 1 : 0]);
    return $user->fresh();
}
```

### 6.4 `app/Services/StewardService.php`

**Add `enforceProviderAsCsCap()`:**

```php
private function enforceProviderAsCsCap(User $steward): void
{
    if ($steward->role !== 'practitioner') return;

    $tier   = $steward->tier ?? 'access';
    $capKey = ($tier === 'practice' && $steward->cs_addon) ? 'practice_cs_addon' : $tier;
    $cap    = config('aegis.provider_as_cs_caps.' . $capKey, 1);

    $count = \App\Models\PlanSteward::where('steward_id', $steward->id)
        ->where('steward_category', 'continuity_steward')
        ->whereIn('status', ['active', 'pending'])
        ->count();

    if ($count >= $cap) {
        $path = config('aegis.provider_as_cs_upgrade_paths.' . $tier);
        throw new \RuntimeException(
            $path['message'] ?? 'Provider-as-CS cap reached. Please upgrade.'
        );
    }
}
```

**Call it in `designate()` and `inviteExternal()` after `enforceTierLimits()`:**

```php
if ($stewardType === 'continuity_steward' && isset($stewardUser)) {
    $this->enforceProviderAsCsCap($stewardUser);
}
```

**Remove circular CS block** — search for any check comparing mutual CS relationships between plan.practitioner_id and steward_id. Delete.

### 6.5 `app/Http/Controllers/Provider/SettingsController.php`

```php
// Add
public function toggleCsAddon(Request $request): RedirectResponse
{
    $enable  = $request->boolean('enable');
    $billing = $request->input('billing', 'monthly');
    app(\App\Services\SubscriptionService::class)->toggleCsAddon(
        $request->user(), $enable, $billing
    );
    return back()->with('success', $enable ? 'CS Add-On activated.' : 'CS Add-On removed.');
}

// Add
public function updateCsAvailability(Request $request): RedirectResponse
{
    app(\App\Services\ProfileService::class)->saveMeta(
        $request->user(), 'available_as_cs', $request->boolean('available_as_cs'), 'boolean'
    );
    return back()->with('success', 'CS availability updated.');
}

// Remove: available_as_ss from validation + save
```

### 6.6 `routes/web.php`

```php
// Add
Route::post('/settings/cs-availability', [SettingsController::class, 'updateCsAvailability'])->name('settings.cs-availability');
Route::post('/settings/cs-addon', [SettingsController::class, 'toggleCsAddon'])->name('settings.cs-addon');

// Remove: SS directory search route if added
```

### 6.7 `app/Http/Controllers/Provider/NetworkController.php`

Remove `ssDirectory` prop. Build `csDirectory` with tier badge, no license info:

```php
private function resolveCsTierBadge(\App\Models\User $u): string
{
    if ($u->role === 'continuity_steward') return 'Business CS';
    if ($u->tier === 'practice' && $u->cs_addon) return 'Practice CS · Extended';
    if ($u->tier === 'practice') return 'Practice CS';
    if ($u->tier === 'access') return 'Access CS';
    return 'Continuity Steward';
}

'csDirectory' => \App\Models\User::where(function ($q) {
    $q->where(function ($q2) {
        $q2->where('role', 'continuity_steward')
           ->where('cs_account_type', 'business');
    })->orWhereHas('metaItems', fn ($mq) =>
        $mq->where('meta_key', 'available_as_cs')->where('meta_value', '1')
    );
})->limit(50)->get()->map(fn ($u) => [
    'id'             => $u->id,
    'display_name'   => $u->display_name,
    'title'          => $u->title,          // NO license_number / license_state
    'location'       => $u->location,
    'avatar_initials'=> $u->avatar_initials,
    'slug'           => $u->slug,
    'cs_tier_badge'  => $this->resolveCsTierBadge($u),
]),
```

### 6.8 `app/Http/Controllers/Provider/SupportStewardController.php`

Update invite to accept two flows:

```php
// Flow A — existing Aegis user: validate name + email match
if ($request->filled('existing_user_email')) {
    $match = \App\Models\User::where('email', $request->existing_user_email)
        ->where('display_name', 'like', '%' . $request->existing_user_name . '%')
        ->first();
    if (!$match) {
        return back()->withErrors([
            'existing_user_email' => 'No Aegis user found matching that name and email.'
        ]);
    }
    // proceed with $match
}
// Flow B — external: name + email only, send onboarding invite
```

### 6.9 `app/Http/Controllers/Provider/ContinuityStewardController.php`

Pass provider-as-CS cap data to Vue:

```php
$providerAsCsCount = \App\Models\PlanSteward::where('steward_id', $user->id)
    ->where('steward_category', 'continuity_steward')
    ->whereIn('status', ['active', 'pending'])
    ->count();

$tier   = $user->tier ?? 'access';
$capKey = ($tier === 'practice' && $user->cs_addon) ? 'practice_cs_addon' : $tier;
$providerAsCsCap = config('aegis.provider_as_cs_caps.' . $capKey, 1);

// Add to Inertia props:
'providerAsCsCount' => $providerAsCsCount,
'providerAsCsCap'   => $providerAsCsCap,
'hasCsAddon'        => (bool) $user->cs_addon,
```

---

## 7. Frontend changes

### 7.1 `resources/js/pages/provider/Settings.vue`

**Pricing displays — exact decimals:**
```vue
<!-- Access -->
${{ billingAnnualView ? '35.75' : 39 }}/mo
{{ billingAnnualView ? 'billed $429/yr · 1 month free' : 'or $429/yr · 1 month free' }}

<!-- Practice -->
${{ billingAnnualView ? '65.83' : 79 }}/mo
{{ billingAnnualView ? 'billed $790/yr · 2 months free' : 'or $790/yr · 2 months free' }}
```

**CS Add-On card** (in Add-ons section, after MAAT card, only shown when `currentTier === 'practice'`):
```vue
<div class="st-addon-card" v-if="currentTier === 'practice'">
  <span class="st-card-ico"><AegisIcon name="users" :size="17" /></span>
  <div class="st-addon-body">
    <div class="st-addon-head">
      <div class="st-addon-name">CS Business Add-On <span class="st-addon-tag">Practice Only</span></div>
      <div class="st-addon-price">
        +<strong>${{ csAddonAnnual ? '20.83' : 25 }}</strong>/mo
        <div class="st-addon-billed">{{ csAddonAnnual ? 'billed $250/yr' : 'or $250/yr' }}</div>
      </div>
    </div>
    <div class="st-addon-desc">Serve as Continuity Steward for up to 43 practitioners. Billed alongside your Practice subscription.</div>
    <div class="st-addon-foot">
      <button v-if="hasCsAddon" class="btn btn-outline" @click="doToggleCsAddon(false)" :disabled="csAddonBusy">
        {{ csAddonBusy ? 'Removing…' : 'Remove CS Add-On' }}
      </button>
      <button v-else class="btn btn-gold" @click="doToggleCsAddon(true)" :disabled="csAddonBusy">
        <AegisIcon name="users" :size="13" /> Add CS Business Add-On
      </button>
    </div>
  </div>
</div>
```

```js
const hasCsAddon   = computed(() => props.subscription?.has_cs_addon ?? false)
const csAddonBusy  = ref(false)
const csAddonAnnual = ref(false)

function doToggleCsAddon(enable) {
  csAddonBusy.value = true
  router.post(route('settings.cs-addon'), {
    enable, billing: csAddonAnnual.value ? 'annual' : 'monthly',
  }, {
    preserveScroll: true,
    onSuccess: () => toast.success(enable ? 'CS Add-On activated.' : 'CS Add-On removed.'),
    onError:   () => toast.error('Could not update CS Add-On.'),
    onFinish:  () => { csAddonBusy.value = false },
  })
}
```

**Privacy & Visibility section:**
- Remove "Available as SS" toggle entirely
- Add "Available as CS" toggle:
```vue
<div class="toggle-row">
  <div class="toggle-info">
    <div class="toggle-label">Available as Continuity Steward</div>
    <div class="toggle-desc">Allow other providers to find and designate you as their CS in the Network directory.</div>
  </div>
  <AegisToggle v-model="privacyForm.available_as_cs" @update:model-value="saveAvailableAsCs" />
</div>
```

### 7.2 `resources/js/pages/provider/Network.vue`

- Remove Find SS tab + content entirely
- Remove `ssDirectory` from defineProps
- Remove SS filter toggles
- In Find CS cards: show `cs.cs_tier_badge` badge, remove any license info fields
- Handle `?scope=cs` URL param on mount

### 7.3 `resources/js/pages/provider/SupportStewards.vue`

- Remove "Browse SS Directory" hero button
- Rewrite Add SS Step 1 modal with two tabs:
  - **Existing Aegis user:** full name + email (both required, must match DB)
  - **External invite:** full name + email (sends onboarding invite)
- Remove any SS = practitioner-only language

### 7.4 `resources/js/pages/provider/ContinuityStewards.vue`

Add to defineProps: `providerAsCsCount`, `providerAsCsCap`, `hasCsAddon`.

Show upgrade alert inside Add CS modal when at cap:
```vue
<div v-if="providerAsCsCount >= providerAsCsCap" class="alert alert-warning" style="margin-bottom:14px">
  <div class="alert-icon"><AegisIcon name="lock" :size="16" /></div>
  <div class="alert-content">
    <div class="alert-title">You've reached your CS capacity</div>
    <div v-if="userTier === 'access'">
      Your Access plan supports serving as CS for 1 practitioner.
      <a :href="route('provider.settings')" style="color:var(--gold-dark);font-weight:700;">
        Upgrade to Business CS — $49/mo →
      </a>
    </div>
    <div v-else-if="userTier === 'practice' && !hasCsAddon">
      Your Practice plan supports serving as CS for 3 practitioners.
      <a :href="route('provider.settings')" style="color:var(--gold-dark);font-weight:700;">
        Add CS Business Add-On — +$25/mo →
      </a>
    </div>
  </div>
</div>
```

### 7.5 `resources/js/pages/auth/OnboardingPlan.vue`

- Access: $39/mo · $35.75/mo annual · $429/yr · 1 month free
- Practice: $79/mo · $65.83/mo annual · $790/yr · 2 months free
- No new plan card — CS addon is post-signup, not onboarding

### 7.6 `resources/js/pages/auth/OnboardingPayment.vue`

- Handle new price IDs from env — no structural change needed

---

## 8. Remaining open questions (non-blocking)

| # | Question | Status |
|---|---|---|
| Q1 | TIER_PRACTICE_MAX_SS: stay at 4? | Keep 4 until Chapman confirms |
| Q2 | MAAT services in-platform or external? | External at launch — v2 |
| Q3 | Additional CS slot add-ons at launch? | Post-launch |
| Q4 | Founding member cancel/resubscribe pricing lock? | Awaiting Chapman |

---

## 9. Implementation order

**Phase 1 — Stripe + config (2-3 hrs)**
1. Create 7 new Stripe price IDs (5 replacements + 2 CS addon)
2. Update `.env` with new price IDs
3. Update `config/aegis.php` — pricing + tier_limits + provider_as_cs_caps
4. Run migration: `cs_addon` column on users
5. Update `User` model `$fillable` + `$casts`

**Phase 2 — Backend logic (3-4 hrs)**
1. `StripeEventListener` — CS addon detection block
2. `SubscriptionService::toggleCsAddon()`
3. `StewardService::enforceProviderAsCsCap()` + remove circular CS block
4. `SettingsController` — `toggleCsAddon()` + `updateCsAvailability()` + remove `available_as_ss`
5. `NetworkController` — remove SS directory, build CS directory with tier badge
6. `SupportStewardController` — two-flow invite
7. `ContinuityStewardController` — pass `providerAsCsCount` + `providerAsCsCap` + `hasCsAddon`
8. `routes/web.php` — add 2 routes, remove SS directory route

**Phase 3 — Frontend (4-5 hrs)**
1. `Settings.vue` — prices, CS addon card, available-as-CS toggle, remove available-as-SS
2. `Network.vue` — remove SS tab, CS cards with tier badge
3. `SupportStewards.vue` — two-flow invite modal, remove Browse SS
4. `ContinuityStewards.vue` — cap warning + upgrade CTA
5. `OnboardingPlan.vue` + `OnboardingPayment.vue` — new prices

**Phase 4 — Testing (2 hrs)**
1. Onboarding prices display correctly
2. Access: blocked on 2nd CS designation → upgrade to Business CS prompt
3. Practice: blocked on 4th CS designation → CS addon prompt
4. Practice + CS addon: can serve 43 practitioners
5. Circular CS: mutual designations allowed (no error)
6. SS invite: existing user lookup + external email
7. Settings: Available as CS toggle visible, Available as SS gone
8. Network: Find CS shows tier badges, no license info; Find SS gone
9. Webhook: CS addon subscription item → `users.cs_addon = 1`

**Total estimate: 11-14 hours**

---

## 10. Success criteria

- [ ] All Stripe price IDs updated — founding subscribers unaffected
- [ ] `config/aegis.php` reflects new prices + caps
- [ ] `users.cs_addon` column syncs from Stripe webhook
- [ ] Access: blocked at 2nd CS designation, sees Business CS upgrade prompt
- [ ] Practice: blocked at 4th CS designation, sees CS addon prompt ($25/mo)
- [ ] Practice + CS addon: serves up to 43 as CS
- [ ] Circular CS allowed
- [ ] Available as SS toggle gone from Settings Privacy section
- [ ] Find SS tab gone from Network
- [ ] Find CS cards show tier badge, no license number
- [ ] SS Add modal: two tabs (existing Aegis user / external)
- [ ] No SS-facing copy implies practitioner-only
- [ ] Onboarding shows $39/$79 with exact annual equivalents

---

## 11. Files touched

**Backend:**
- [ ] `.env`
- [ ] `config/aegis.php`
- [ ] `database/migrations/[ts]_add_cs_addon_to_users_table.php` ← NEW
- [ ] `app/Models/User.php`
- [ ] `app/Listeners/StripeEventListener.php`
- [ ] `app/Services/SubscriptionService.php`
- [ ] `app/Services/StewardService.php`
- [ ] `app/Http/Controllers/Provider/SettingsController.php`
- [ ] `app/Http/Controllers/Provider/NetworkController.php`
- [ ] `app/Http/Controllers/Provider/SupportStewardController.php`
- [ ] `app/Http/Controllers/Provider/ContinuityStewardController.php`
- [ ] `routes/web.php`

**Frontend:**
- [ ] `resources/js/pages/provider/Settings.vue`
- [ ] `resources/js/pages/provider/Network.vue`
- [ ] `resources/js/pages/provider/SupportStewards.vue`
- [ ] `resources/js/pages/provider/ContinuityStewards.vue`
- [ ] `resources/js/pages/auth/OnboardingPlan.vue`
- [ ] `resources/js/pages/auth/OnboardingPayment.vue`

**Stripe Dashboard:**
- [ ] Create Access Monthly $39 → `STRIPE_PRICE_ACCESS_MONTHLY`
- [ ] Create Access Annual $429/yr → `STRIPE_PRICE_ACCESS_ANNUAL`
- [ ] Create Practice Monthly $79 → `STRIPE_PRICE_PRACTICE_MONTHLY`
- [ ] Create Practice Annual $790/yr → `STRIPE_PRICE_PRACTICE_ANNUAL`
- [ ] Create CS Business Annual $490/yr → `STRIPE_PRICE_CS_BUSINESS_ANNUAL`
- [ ] Create Practice CS Add-On Monthly $25 → `STRIPE_PRICE_PRACTICE_CS_ADDON_MONTHLY`
- [ ] Create Practice CS Add-On Annual $250/yr → `STRIPE_PRICE_PRACTICE_CS_ADDON_ANNUAL`
- [ ] (post-launch) Additional CS Slot Access $70/yr · Practice $30/yr

---

## 12. Rollback plan

1. Revert `.env` STRIPE_PRICE_* to old price IDs (old Stripe products remain live)
2. Revert `config/aegis.php`
3. Revert Vue components
4. Drop `cs_addon` migration (if not yet in production)
5. Existing subscribers unaffected — Stripe binds to price IDs, not env values

---

*Plan revised 2026-07-19. All v1 blockers resolved. Ready for implementation.*
