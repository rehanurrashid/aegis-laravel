<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Models\ProviderCredential;
use App\Models\Service;
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

        $specialties  = $this->metaValue($user, 'specialties');
        $networkPrefs = $this->metaValue($user, 'network_prefs');
        $demographics = $this->metaValue($user, 'demographics');

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

        $hasServices = Service::where('practitioner_id', $user->id)
            ->where('status', 'active')
            ->exists();

        $sections = [
            'basic-info'   => ! empty($user->display_name)
                           && ! empty($user->phone)
                           && ! empty($user->location),
            'professional' => ! empty($user->title)
                           && ! empty($user->bio)
                           && $hasLicense,
            'specialties'  => ! empty($specialties)
                           && is_array($specialties)
                           && count($specialties) > 0,
            'insurance'    => $hasInsurance && $hasServices,
            'network'      => ! empty($networkPrefs)
                           && (is_array($networkPrefs) ? count($networkPrefs) > 0 : true),
            'demographics' => ! empty($demographics)
                           && (is_array($demographics) ? count($demographics) > 0 : true),
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
        if (empty($specialties) || ! is_array($specialties) || count($specialties) === 0) {
            return 'Add your specialties';
        }

        $hasInsurance = ProviderCredential::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereRaw("LOWER(cred_type) LIKE '%insurance%'")
                  ->orWhereRaw("LOWER(cred_type) LIKE '%liability%'");
            })
            ->exists();
        $hasServices = Service::where('practitioner_id', $user->id)
            ->where('status', 'active')
            ->exists();
        if (! $hasInsurance || ! $hasServices) {
            return 'Add insurance and services';
        }

        $networkPrefs = $this->metaValue($user, 'network_prefs');
        if (empty($networkPrefs)) {
            return 'Set network preferences';
        }

        $demographics = $this->metaValue($user, 'demographics');
        if (empty($demographics)) {
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
