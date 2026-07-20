<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OnboardingIntentRequest;
use App\Http\Requests\Auth\OnboardingSubscribeRequest;
use App\Services\AuthService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        private AuthService         $auth,
        private SubscriptionService $subscriptionService,
    ) {}

    // ── Pre-registration intent pages ────────────────────────────────────

    public function showIntent(): Response
    {
        return Inertia::render('auth/OnboardingIntent');
    }

    public function showRole(): Response
    {
        return Inertia::render('auth/OnboardingRole');
    }

    public function submitIntent(OnboardingIntentRequest $request): RedirectResponse
    {
        $request->session()->put('onboarding_intent', $request->validated());
        return redirect()->route('onboarding.role');
    }

    public function complete(OnboardingIntentRequest $request): RedirectResponse
    {
        $this->auth->completeOnboarding($request->user(), $request->validated());
        return redirect()->route('home');
    }

    // ── Plan Selection (post email-verify, paid roles only) ───────────────

    /**
     * GET /onboarding/plan
     *
     * Routing logic:
     *   - Practitioner → Access vs Practice picker (monthly/annual)
     *   - Business Partner → Monthly vs Annual picker
     *   - Business CS → single-tier CS Business picker
     *   - Invited CS / SS → no plan needed, bounce to dashboard
     */
    public function showPlan(Request $request): Response|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($redirect = $this->redirectIfComplete($user)) {
            return $redirect;
        }

        if (!$user->verified) {
            return redirect()->route('verification.notice');
        }

        if (!$this->requiresSubscription($user)) {
            return redirect()->route($this->dashboardRoute($user));
        }

        return Inertia::render('auth/OnboardingPlan', [
            'role'    => $this->roleValue($user),
            'cs_path' => $user->cs_path,
            'pricing' => config('aegis.pricing'),
            'flash'   => session()->only(['success', 'error']),
        ]);
    }

    /**
     * POST /onboarding/plan — store plan selection in session, redirect to payment.
     */
    public function storePlan(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'tier'    => ['required', 'string'],
            'billing' => ['required', 'string', 'in:monthly,annual'],
            'addons'  => ['nullable', 'array'],
            'addons.*'=> ['string', 'in:maat,cs_addon'],
        ]);

        $request->session()->put('onboarding_plan', [
            'tier'    => $request->input('tier'),
            'billing' => $request->input('billing'),
            'addons'  => $request->input('addons', []),
            'role'    => $this->roleValue($user),
        ]);

        return redirect()->route('onboarding.payment');
    }

    // ── Payment (Stripe Payment Element) ─────────────────────────────────

    /**
     * GET /onboarding/payment
     */
    public function showPayment(Request $request): Response|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        \Log::info('[ONBOARDING_PAYMENT] page load', [
            'user_id'    => $user->id,
            'stripe_id'  => $user->stripe_id,
            'has_stripe' => $user->hasStripeId(),
            'verified'   => $user->verified,
            'role'       => $user->role,
        ]);

        if ($redirect = $this->redirectIfComplete($user)) {
            return $redirect;
        }

        if (!$user->verified) {
            return redirect()->route('verification.notice');
        }

        if (!$this->requiresSubscription($user)) {
            return redirect()->route($this->dashboardRoute($user));
        }

        $plan = $request->session()->get('onboarding_plan');
        if (!$plan) {
            return redirect()->route('onboarding.plan');
        }

        // Ensure a valid Stripe customer exists before creating a SetupIntent.
        // Demo users may have a fake stripe_id (e.g. 'cus_demo_sarah') — clear it
        // so Cashier creates a real customer in Stripe sandbox.
        if ($user->hasStripeId()) {
            try {
                // Use Cashier's stripe() client — has API key set automatically
                \Log::info('[ONBOARDING_PAYMENT] retrieving Stripe customer', ['stripe_id' => $user->stripe_id]);
                $user->stripe()->customers->retrieve($user->stripe_id);
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // stripe_id is fake/invalid — clear it so createAsStripeCustomer runs fresh
                $user->forceFill(['stripe_id' => null])->saveQuietly();
            } catch (\Throwable $e) {
                // Network/DNS failure — clear stripe_id so we retry fresh on submit
                \Log::warning('[Onboarding] Stripe customer retrieve failed: ' . $e->getMessage());
                $user->forceFill(['stripe_id' => null])->saveQuietly();
            }
        }

        if (!$user->hasStripeId()) {
            try {
                \Log::info('[ONBOARDING_PAYMENT] creating Stripe customer', ['user_id' => $user->id]);
                $user->createAsStripeCustomer([
                    'name'  => $user->display_name,
                    'email' => $user->email,
                ]);
                \Log::info('[ONBOARDING_PAYMENT] Stripe customer created', ['stripe_id' => $user->stripe_id]);
            } catch (\Throwable $e) {
                \Log::warning('[ONBOARDING_PAYMENT] createAsStripeCustomer failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);
            }
        }

        // CreateSetupIntent so Stripe can collect + tokenize the card
        $clientSecret = null;
        try {
            \Log::info('[ONBOARDING_PAYMENT] creating SetupIntent', ['stripe_id' => $user->stripe_id]);
            $intent       = $user->createSetupIntent();
            $clientSecret = $intent->client_secret;
            \Log::info('[ONBOARDING_PAYMENT] SetupIntent created', ['intent_id' => $intent->id]);
        } catch (\Throwable $e) {
            \Log::error('[ONBOARDING_PAYMENT] createSetupIntent failed', [
                'error'     => $e->getMessage(),
                'user_id'   => $user->id,
                'stripe_id' => $user->stripe_id,
            ]);
            // Don't render with null — redirect back with actionable message
            return redirect()->route('onboarding.payment')
                ->withErrors(['stripe' => 'Could not connect to payment processor. Check STRIPE_SECRET in .env and that Stripe API is reachable. Error: ' . $e->getMessage()]);
        }

        \Log::info('[ONBOARDING_PAYMENT] rendering page', [
            'has_client_secret' => !is_null($clientSecret),
            'plan'              => $plan,
        ]);

        return Inertia::render('auth/OnboardingPayment', [
            'role'         => $this->roleValue($user),
            'cs_path'      => $user->cs_path,
            'plan'         => $plan,
            'clientSecret' => $clientSecret,
            'stripeKey'    => config('services.stripe.key'),
            'pricing'      => config('aegis.pricing'),
        ]);
    }

    /**
     * POST /onboarding/subscribe
     *
     * Attaches the PaymentMethod, creates the Stripe subscription,
     * handles add-ons, then redirects to the portal dashboard.
     */
    public function subscribe(OnboardingSubscribeRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (!$user->verified) {
            return redirect()->route('verification.notice');
        }

        $data    = $request->validated();
        $pmId    = $data['payment_method_id'];
        $priceId = $data['price_id'];

        // Ensure user exists as a Stripe customer first (Cashier requirement).
        // createOrGetStripeCustomer() is idempotent — safe to call every time.
        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer([
                'name'  => $user->display_name,
                'email' => $user->email,
            ]);
        }

        // Attach card as default payment method (Cashier)
        $user->updateDefaultPaymentMethod($pmId);

        // Mirror to users.stripe_payment_method_id so peer-payment charges
        // (Provider → BP, Provider → CS, Client → Provider service sessions)
        // can find the card without a second Stripe roundtrip.
        $user->forceFill(['stripe_payment_method_id' => $pmId])->save();

        // Handle add-ons — pick MAAT price matching the base plan's billing period
        $plan    = $request->session()->get('onboarding_plan', []);
        $billing = $plan['billing'] ?? 'monthly';

        // practice_business: subscribe with two SubscriptionItems (practice base + CS add-on)
        $planTier = $plan['tier'] ?? null;
        if ($planTier === 'practice_business') {
            $practiceBasePrice = $billing === 'annual'
                ? env('STRIPE_PRICE_PRACTICE_ANNUAL')
                : env('STRIPE_PRICE_PRACTICE_MONTHLY');
            $csAddonPrice = $billing === 'annual'
                ? env('STRIPE_PRICE_PRACTICE_CS_ADDON_ANNUAL')
                : env('STRIPE_PRICE_PRACTICE_CS_ADDON_MONTHLY');

            $this->subscriptionService->subscribe($user, $practiceBasePrice, $pmId, $csAddonPrice);
            $user->forceFill(['tier' => 'practice_business', 'cs_addon' => 1])->save();
        } else {
            // Standard single-price subscription
            $this->subscriptionService->subscribe($user, $priceId, $pmId);

            foreach ($data['addons'] ?? [] as $addon) {
                match ($addon) {
                    'maat'     => $this->subscriptionService->toggleMaatAddon($user, true, $billing),
                    'cs_addon' => $this->subscriptionService->toggleCsAddon($user, true, $billing),
                    default    => null,
                };
            }

            // Update tier on user record from the price ID mapping
            $tier = config("aegis.stripe_price_to_tier.{$priceId}");
            if ($tier && in_array($tier, ['access', 'practice'], true)) {
                // If cs_addon was added, promote tier to practice_business
                $hasCs = in_array('cs_addon', $data['addons'] ?? [], true);
                $user->forceFill([
                    'tier'     => ($tier === 'practice' && $hasCs) ? 'practice_business' : $tier,
                    'cs_addon' => $hasCs ? 1 : 0,
                ])->save();
            }
        }

        // Clear onboarding session
        $request->session()->forget(['onboarding_plan', 'onboarding_intent']);

        // Fire welcome email (post-payment — not on register)
        // and subscription invoice notification via Stripe webhook
        // (invoice.payment_succeeded fires automatically via Stripe → StripeEventListener)
        // Welcome email fires here since we know subscription is now active
        \App\Jobs\SendEmailJob::dispatch(
            'emails.account.01-welcome',
            [
                'user_id'    => $user->id,
                'portal_url' => route($this->dashboardRoute($user)),
                'role_label' => $this->roleLabelFor($user),
            ],
            $user->id
        )->onQueue('email');

        // Fire subscription invoice email alongside the welcome email
        \App\Jobs\SendEmailJob::dispatch(
            'emails.auth.10-subscription-invoice',
            $this->buildInvoiceData($user, $plan, $billing),
            $user->id
        )->onQueue('email');

        return redirect()->route($this->dashboardRoute($user))
            ->with('success', 'Welcome to Aegis! Your subscription is active.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Returns true if this user/role combination requires Stripe payment at onboarding.
     * - Practitioner → always pays (Access or Practice)
     * - Business Partner → always pays
     * - CS Business → pays ($49/mo)
     * - CS Invited → free (covered by practitioner)
     * - Support Steward → free (invitation-only)
     */
    /**
     * If the user is already fully onboarded (verified + active subscription),
     * redirect them to their portal dashboard — they have no business here.
     */
    private function redirectIfComplete(\App\Models\User $user): ?\Illuminate\Http\RedirectResponse
    {
        if (!$user->verified) return null;
        if (!$this->requiresSubscription($user)) return null; // free role — already handled

        try {
            $sub = $user->subscriptions()->where('type', 'default')->latest()->first();
            $active = $sub && in_array($sub->stripe_status, ['active', 'trialing', 'past_due'], true);
        } catch (\Throwable) {
            return null; // fail open
        }

        if ($active) {
            return redirect()->route($this->dashboardRoute($user))
                ->with('success', 'Your plan is already active. Welcome to your dashboard!');
        }

        return null;
    }

    private function roleLabelFor(\App\Models\User $user): string
    {
        $role = $user->role instanceof \App\Enums\UserRole ? $user->role->value : (string) $user->role;
        return match ($role) {
            'practitioner'      => 'Practitioner Partner',
            'business_partner'  => 'Business Partner',
            'continuity_steward'=> 'Continuity Steward',
            'support_steward'   => 'Support Steward',
            default             => 'Aegis',
        };
    }

    private function requiresSubscription(\App\Models\User $user): bool
    {
        $role   = $this->roleValue($user);
        $csType = $user->cs_account_type instanceof \App\Enums\CsAccountType
            ? $user->cs_account_type->value
            : (string) ($user->cs_account_type ?? '');

        if ($role === 'practitioner')      return true;
        if ($role === 'business_partner')  return true;
        if ($role === 'continuity_steward' && $csType === 'business') return true;

        return false;
    }

    private function dashboardRoute(\App\Models\User $user): string
    {
        $role = $user->role instanceof UserRole ? $user->role : UserRole::tryFrom((string) $user->role);

        return match ($role) {
            UserRole::Practitioner      => 'provider.dashboard',
            UserRole::ContinuitySteward => 'cs.dashboard',
            UserRole::SupportSteward    => 'ss.dashboard',
            UserRole::BusinessPartner   => 'bp.dashboard',
            UserRole::Admin             => 'admin.dashboard',
            default                     => 'home',
        };
    }

    private function roleValue(\App\Models\User $user): string
    {
        return $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
    }

    /**
     * Build the data array for the subscription invoice email.
     * Pulls prices from config('aegis.pricing') — same source OnboardingPlan uses.
     */
    private function buildInvoiceData(\App\Models\User $user, array $plan, string $billing): array
    {
        $pricing   = config('aegis.pricing');
        $tier      = $plan['tier']    ?? 'practice';
        $addons    = $plan['addons']  ?? [];
        $isAnnual  = $billing === 'annual';
        $toD       = fn(int $cents): string => '$' . number_format($cents / 100, 2);

        // ── Base plan ─────────────────────────────────────────────────────
        $planCents = match (true) {
            $tier === 'access'            => $isAnnual
                ? ($pricing['practitioner']['access']['annual_cents']            ?? 3575)
                : ($pricing['practitioner']['access']['monthly_cents']           ?? 3900),
            $tier === 'practice'          => $isAnnual
                ? ($pricing['practitioner']['practice']['annual_cents']          ?? 6583)
                : ($pricing['practitioner']['practice']['monthly_cents']         ?? 7900),
            $tier === 'practice_business' => $isAnnual
                ? (($pricing['practitioner']['practice']['annual_cents']          ?? 6583)
                 + ($pricing['practitioner']['practice_business']['annual_cents'] ?? 2083))
                : (($pricing['practitioner']['practice']['monthly_cents']          ?? 7900)
                 + ($pricing['practitioner']['practice_business']['monthly_cents'] ?? 2500)),
            $tier === 'monthly'           => $pricing['business_partner']['monthly_cents']      ?? 6900,
            $tier === 'annual'            => $pricing['business_partner']['annual_total_cents'] ?? 69000,
            $tier === 'cs_business'       => $isAnnual
                ? ($pricing['continuity_steward_business']['annual_cents']       ?? 4083)
                : ($pricing['continuity_steward_business']['monthly_cents']      ?? 4900),
            default => 0,
        };

        $planLabels = [
            'access'            => 'Continuity Access',
            'practice'          => 'Continuity Practice',
            'practice_business' => 'Continuity Practice Business',
            'monthly'           => 'Business Partner — Monthly',
            'annual'            => 'Business Partner — Annual',
            'cs_business'       => 'Business CS',
        ];

        $planName = $planLabels[$tier] ?? 'Aegis Subscription';
        $planDesc = $isAnnual && !in_array($tier, ['monthly', 'annual'], true)
            ? 'Annual subscription (billed today)'
            : 'Monthly subscription';

        // ── Add-ons ───────────────────────────────────────────────────────
        $addonLines = [];
        $addonTotal = 0;

        if (in_array('maat', $addons, true)) {
            $maatCents   = $isAnnual
                ? ($pricing['maat_addon']['annual_cents']      ?? 2300)
                : ($pricing['maat_addon']['monthly_cents']     ?? 2900);
            $addonTotal += $maatCents;
            $addonLines[] = [
                'name'        => 'MAAT Professional Continuity Steward Service',
                'description' => 'Designated licensed & insured CS · emergency response within 4 hrs',
                'price'       => $toD($maatCents) . ($tier === 'annual' ? '/yr' : '/mo'),
            ];
        }

        // ── Totals & renewal ─────────────────────────────────────────────
        $totalCents   = $planCents + $addonTotal;
        $suffix       = in_array($tier, ['annual', 'cs_business'], true) && $isAnnual ? '/yr' : '/mo';
        $billingLabel = $isAnnual ? 'Annual (billed today)' : 'Monthly';

        $renewalLabel = $isAnnual
            ? 'Annually on ' . now()->addYear()->format('F j, Y')
            : 'Monthly on the ' . now()->addMonth()->format('jS') . ' of each month';

        return [
            'recipient_name'  => $user->display_name ?? $user->name,
            'invoice_number'  => 'INV-' . strtoupper(substr(str_replace('-', '', $user->id), 0, 8)),
            'invoice_date'    => now()->format('F j, Y'),
            'billing_label'   => $billingLabel,
            'plan_name'       => $planName,
            'plan_description'=> $planDesc,
            'plan_price'      => $toD($planCents) . $suffix,
            'addons'          => $addonLines,
            'total_price'     => $toD($totalCents) . $suffix,
            'renewal_label'   => $renewalLabel,
            'portal_url'      => route($this->dashboardRoute($user)),
            'role_label'      => $this->roleLabelFor($user),
            'user_id'         => $user->id,
        ];
    }
}
