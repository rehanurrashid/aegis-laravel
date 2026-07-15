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

        $hasLicense  = ProviderCredential::where('user_id', $user->id)
            ->where('cred_type', 'license')
            ->exists();

        $hasInsurance = ProviderCredential::where('user_id', $user->id)
            ->get()
            ->contains(fn ($c) => $c->is_insurance);

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
        $pct = $this->compute($user);
        return max(0, 9 - (int) round(($pct / 100) * 9));
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
