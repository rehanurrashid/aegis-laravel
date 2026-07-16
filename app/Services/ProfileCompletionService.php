<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Models\ProviderCredential;
use App\Models\User;

/**
 * Profile completion service — per-section + overall pct.
 *
 * compute(user)       → array{ sections, pct, items_remaining, total }
 *                       sections keyed by EditProfile nav key → bool
 * recompute(user)     → persists pct to users.profile_completion, returns int
 * nextStepLabel(user) → first incomplete section label string
 */
class ProfileCompletionService
{
    public function compute(User $user): array
    {
        if (! $user->relationLoaded('meta')) {
            $user->load('meta');
        }

        $specialties      = $this->metaValue($user, 'specialties');
        $services         = $this->metaValue($user, 'services');
        $networkPrefs     = $this->metaValue($user, 'network_prefs');
        $licensedStates   = $this->metaValue($user, 'licensed_states');
        $aiShadowSettings = $this->metaValue($user, 'ai_shadow_settings');
        $demographics     = $this->metaValue($user, 'demographics');
        $fees             = $this->metaValue($user, 'fees');

        $hasLicense = ProviderCredential::where('user_id', $user->id)
            ->whereRaw("LOWER(cred_type) NOT LIKE '%insurance%'")
            ->whereRaw("LOWER(cred_type) NOT LIKE '%liability%'")
            ->exists();

        $hasInsuranceCred = ProviderCredential::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereRaw("LOWER(cred_type) LIKE '%insurance%'")
                  ->orWhereRaw("LOWER(cred_type) LIKE '%liability%'");
            })
            ->exists();

        $sections = [
            'basic-info'   => ! empty($user->display_name)
                           && ! empty($user->phone)
                           && ! empty($user->location),
            'professional' => ! empty($user->title)
                           && ! empty($user->bio)
                           && $hasLicense,
            'specialties'  => (! empty($specialties) && is_array($specialties) && count($specialties) > 0)
                           || (! empty($services) && is_array($services) && count($services) > 0),
            'insurance'    => $hasInsuranceCred
                           || (! empty($fees) && is_array($fees) && count($fees) > 0),
            'network'      => (! empty($networkPrefs) && (is_array($networkPrefs) ? count($networkPrefs) > 0 : true))
                           || (! empty($licensedStates) && is_array($licensedStates) && count($licensedStates) > 0)
                           || (! empty($aiShadowSettings) && (is_array($aiShadowSettings) ? count($aiShadowSettings) > 0 : true)),
            'demographics' => is_array($demographics)
                           && count(array_filter($demographics, fn($v) => ! empty($v))) > 0,
        ];

        $filled = count(array_filter($sections));
        $total  = count($sections);
        $pct    = (int) round(($filled / $total) * 100);

        return [
            'sections'        => $sections,
            'pct'             => $pct,
            'items_remaining' => $total - $filled,
            'total'           => $total,
        ];
    }

    public function recompute(User $user): int
    {
        $result = $this->compute($user);
        $user->update(['profile_completion' => $result['pct']]);
        return $result['pct'];
    }

    public function itemsRemaining(User $user): int
    {
        return $this->compute($user)['items_remaining'];
    }

    public function nextStepLabel(User $user): string
    {
        if (! $user->relationLoaded('meta')) {
            $user->load('meta');
        }

        if (empty($user->display_name) || empty($user->phone) || empty($user->location)) {
            return 'Complete your basic info';
        }

        if (empty($user->title) || empty($user->bio)) {
            return 'Add your professional details';
        }

        $hasLicense = ProviderCredential::where('user_id', $user->id)
            ->whereRaw("LOWER(cred_type) NOT LIKE '%insurance%'")
            ->whereRaw("LOWER(cred_type) NOT LIKE '%liability%'")
            ->exists();
        if (! $hasLicense) {
            return 'Add a license credential';
        }

        $specialties = $this->metaValue($user, 'specialties');
        $services    = $this->metaValue($user, 'services');
        if ((empty($specialties) || ! is_array($specialties) || count($specialties) === 0)
            && (empty($services) || ! is_array($services) || count($services) === 0)) {
            return 'Add your specialties';
        }

        $hasInsuranceCred = ProviderCredential::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereRaw("LOWER(cred_type) LIKE '%insurance%'")
                  ->orWhereRaw("LOWER(cred_type) LIKE '%liability%'");
            })
            ->exists();
        $fees = $this->metaValue($user, 'fees');
        $feesComplete = $hasInsuranceCred || (! empty($fees) && is_array($fees) && count($fees) > 0);
        if (! $feesComplete) {
            return 'Add insurance and services';
        }

        $networkPrefs     = $this->metaValue($user, 'network_prefs');
        $licensedStates   = $this->metaValue($user, 'licensed_states');
        $aiShadowSettings = $this->metaValue($user, 'ai_shadow_settings');
        $networkComplete  = (! empty($networkPrefs) && (is_array($networkPrefs) ? count($networkPrefs) > 0 : true))
                         || (! empty($licensedStates) && is_array($licensedStates) && count($licensedStates) > 0)
                         || (! empty($aiShadowSettings) && (is_array($aiShadowSettings) ? count($aiShadowSettings) > 0 : true));
        if (! $networkComplete) {
            return 'Set network preferences';
        }

        $demographics = $this->metaValue($user, 'demographics');
        $demographicsComplete = is_array($demographics)
            && count(array_filter($demographics, fn($v) => ! empty($v))) > 0;
        if (! $demographicsComplete) {
            return 'Add demographics';
        }

        return '';
    }

    private function metaValue(User $user, string $key): mixed
    {
        $row = $user->meta->firstWhere('meta_key', $key);
        return $row ? $row->typed_value : null;
    }
}
