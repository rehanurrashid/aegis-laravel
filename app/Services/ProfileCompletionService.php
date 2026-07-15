<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProviderCredential;
use App\Models\User;

class ProfileCompletionService
{
    /**
     * Compute profile completion percentage (0–100).
     * Mirrors the 9-check logic in EditProfile.vue completionPct computed.
     */
    public function compute(User $user): int
    {
        // Ensure meta relation is loaded
        if (! $user->relationLoaded('meta')) {
            $user->load('meta');
        }

        $specialties = $this->metaValue($user, 'specialties');
        $services    = $this->metaValue($user, 'services');

        // Mirror Vue: licenseCredentials = credentials where !is_insurance
        // is_insurance accessor: cred_type contains 'insurance' or 'liability'
        $hasLicense = ProviderCredential::where('user_id', $user->id)
            ->whereRaw("LOWER(cred_type) NOT LIKE '%insurance%'")
            ->whereRaw("LOWER(cred_type) NOT LIKE '%liability%'")
            ->exists();

        // Mirror Vue: insuranceCredential = credentials where is_insurance
        $hasInsurance = ProviderCredential::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereRaw("LOWER(cred_type) LIKE '%insurance%'")
                  ->orWhereRaw("LOWER(cred_type) LIKE '%liability%'");
            })
            ->exists();

        $checks = [
            ! empty($user->display_name),
            ! empty($user->title),
            ! empty($user->bio),
            ! empty($user->location),
            ! empty($user->phone),
            ! empty($specialties) && is_array($specialties) && count($specialties) > 0,
            ! empty($services)    && is_array($services)    && count($services) > 0,
            $hasLicense,
            $hasInsurance,
        ];

        $filled = count(array_filter($checks));

        return (int) round(($filled / count($checks)) * 100);
    }

    public function recompute(User $user): int
    {
        $pct = $this->compute($user);
        $user->update(['profile_completion' => $pct]);
        return $pct;
    }

    public function itemsRemaining(User $user): int
    {
        return max(0, 9 - (int) round(($this->compute($user) / 100) * 9));
    }

    private function metaValue(User $user, string $key): mixed
    {
        $row = $user->meta->firstWhere('meta_key', $key);
        if (! $row) {
            return null;
        }
        return $row->typed_value;
    }
}
