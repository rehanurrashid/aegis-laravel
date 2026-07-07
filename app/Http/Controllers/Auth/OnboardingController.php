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
            'addons.*'=> ['string', 'in:maat'],
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

        // CreateSetupIntent so Stripe can collect + tokenize the card
        $intent = $user->createSetupIntent();

        return Inertia::render('auth/OnboardingPayment', [
            'role'         => $this->roleValue($user),
            'cs_path'      => $user->cs_path,
            'plan'         => $plan,
            'clientSecret' => $intent->client_secret,
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

        // Create the Stripe subscription
        $this->subscriptionService->subscribe($user, $priceId, $pmId);

        // Handle add-ons
        foreach ($data['addons'] ?? [] as $addon) {
            match ($addon) {
                'maat' => $this->subscriptionService->toggleMaatAddon($user, true),
                default => null,
            };
        }

        // Update tier on user record from the price ID mapping
        $tier = config("aegis.stripe_price_to_tier.{$priceId}");
        if ($tier && in_array($tier, ['access', 'practice'], true)) {
            $user->forceFill(['tier' => $tier])->save();
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
            return redirect()->route($this->dashboardRoute($user));
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
}
