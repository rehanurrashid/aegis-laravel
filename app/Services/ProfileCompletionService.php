<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Models\ProviderCredential;
use App\Models\User;

/**
 * Platform readiness score for Practitioner accounts.
 *
 * 12-check checklist spanning profile, credentials, billing, and continuity setup.
 * Each check is one point; percentage = filled / 12 × 100.
 *
 * Checks:
 *  1.  Display name set
 *  2.  Professional title set
 *  3.  Bio set
 *  4.  Location + phone set (both required)
 *  5.  Specialties added
 *  6.  License credential on file (cred_type not insurance/liability)
 *  7.  Liability insurance on file (cred_type contains insurance or liability)
 *  8.  Subscription active (stripe_id exists and not demo prefix)
 *  9.  Payment method saved (stripe_payment_method_id not null/demo)
 * 10.  MFA enabled
 * 11.  Continuity Plan created
 * 12.  CS + SS both assigned (both needed = 1 check; partial = 0)
 */
class ProfileCompletionService
{
    private const TOTAL = 12;

    public function compute(User $user): int
    {
        if (! $user->relationLoaded('meta')) {
            $user->load('meta');
        }

        $specialties = $this->metaValue($user, 'specialties');

        $hasLicense = ProviderCredential::where('user_id', $user->id)
            ->whereRaw("LOWER(cred_type) NOT LIKE '%insurance%'")
            ->whereRaw("LOWER(cred_type) NOT LIKE '%liability%'")
            ->exists();

        $hasInsurance = ProviderCredential::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereRaw("LOWER(cred_type) LIKE '%insurance%'")
                  ->orWhereRaw("LOWER(cred_type) LIKE '%liability%'");
            })
            ->exists();

        $stripeId = $user->stripe_id ?? '';
        $pmId     = $user->stripe_payment_method_id ?? '';
        $hasSubscription  = ! empty($stripeId)  && ! str_starts_with($stripeId, 'cus_demo_');
        $hasPaymentMethod = ! empty($pmId)       && ! str_starts_with($pmId, 'pm_demo_');

        $plan = ContinuityPlan::where('practitioner_id', $user->id)->first();

        $hasCs = false;
        $hasSs = false;
        if ($plan) {
            $hasCs = PlanSteward::where('plan_id', $plan->id)
                ->where('steward_category', 'cs')
                ->where('status', 'active')
                ->exists();
            $hasSs = PlanSteward::where('plan_id', $plan->id)
                ->where('steward_category', 'ss')
                ->where('status', 'active')
                ->exists();
        }

        $checks = [
            ! empty($user->display_name),                                             // 1
            ! empty($user->title),                                                    // 2
            ! empty($user->bio),                                                      // 3
            ! empty($user->location) && ! empty($user->phone),                       // 4
            ! empty($specialties) && is_array($specialties) && count($specialties) > 0, // 5
            $hasLicense,                                                              // 6
            $hasInsurance,                                                            // 7
            $hasSubscription,                                                         // 8
            $hasPaymentMethod,                                                        // 9
            (bool) $user->two_factor_enabled,                                        // 10
            $plan !== null,                                                           // 11
            $hasCs && $hasSs,                                                         // 12
        ];

        $filled = count(array_filter($checks));

        return (int) round(($filled / self::TOTAL) * 100);
    }

    public function recompute(User $user): int
    {
        $pct = $this->compute($user);
        $user->update(['profile_completion' => $pct]);
        return $pct;
    }

    public function itemsRemaining(User $user): int
    {
        return max(0, self::TOTAL - (int) round(($this->compute($user) / 100) * self::TOTAL));
    }

    /**
     * Returns a human-readable label for the first incomplete check.
     * Used in subtitle hints.
     */
    public function nextStepLabel(User $user): string
    {
        if (! $user->relationLoaded('meta')) {
            $user->load('meta');
        }

        if (empty($user->display_name))  return 'Add your display name';
        if (empty($user->title))         return 'Add your professional title';
        if (empty($user->bio))           return 'Write your bio';

        if (empty($user->location) || empty($user->phone)) return 'Add your location and phone';

        $specialties = $this->metaValue($user, 'specialties');
        if (empty($specialties) || ! is_array($specialties) || count($specialties) === 0) {
            return 'Add your specialties';
        }

        $hasLicense = ProviderCredential::where('user_id', $user->id)
            ->whereRaw("LOWER(cred_type) NOT LIKE '%insurance%'")
            ->whereRaw("LOWER(cred_type) NOT LIKE '%liability%'")
            ->exists();
        if (! $hasLicense) return 'Add a license credential';

        $hasInsurance = ProviderCredential::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereRaw("LOWER(cred_type) LIKE '%insurance%'")
                  ->orWhereRaw("LOWER(cred_type) LIKE '%liability%'");
            })
            ->exists();
        if (! $hasInsurance) return 'Add liability insurance';

        $stripeId = $user->stripe_id ?? '';
        if (empty($stripeId) || str_starts_with($stripeId, 'cus_demo_')) return 'Activate your subscription';

        $pmId = $user->stripe_payment_method_id ?? '';
        if (empty($pmId) || str_starts_with($pmId, 'pm_demo_')) return 'Save a payment method';

        if (! $user->two_factor_enabled) return 'Enable two-factor authentication';

        $plan = ContinuityPlan::where('practitioner_id', $user->id)->first();
        if (! $plan) return 'Create your Continuity Plan';

        $hasCs = PlanSteward::where('plan_id', $plan->id)->where('steward_category', 'cs')->where('status', 'active')->exists();
        if (! $hasCs) return 'Assign a Continuity Steward';

        $hasSs = PlanSteward::where('plan_id', $plan->id)->where('steward_category', 'ss')->where('status', 'active')->exists();
        if (! $hasSs) return 'Assign a Support Steward';

        return '';
    }

    private function metaValue(User $user, string $key): mixed
    {
        $row = $user->meta->firstWhere('meta_key', $key);
        return $row ? $row->typed_value : null;
    }
}
