<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class SecurityCompletionService
{
    public function compute(User $user): array
    {
        if (! $user->relationLoaded('meta')) {
            $user->load('meta');
        }

        $backupCodes = $this->metaValue($user, 'backup_codes');
        $hasBackupCodes = ! empty($backupCodes)
            && is_array($backupCodes)
            && count($backupCodes) > 0;

        // password_updated_at may not exist on all installations — handle gracefully
        $passwordUpdatedAt = $user->password_updated_at ?? null;
        $strongPassword = $passwordUpdatedAt
            ? \Carbon\Carbon::parse($passwordUpdatedAt)->gt(now()->subYear())
            : false;

        $checks = [
            'two_factor'      => (bool) $user->two_factor_enabled,
            'strong_password' => $strongPassword,
            'backup_codes'    => $hasBackupCodes,
            'email_verified'  => ! empty($user->email_verified_at),
        ];

        $labels = [
            'two_factor'      => 'Enable two-factor authentication',
            'strong_password' => 'Update password (older than 1 year)',
            'backup_codes'    => 'Generate backup codes',
            'email_verified'  => 'Verify email address',
        ];

        $filled = count(array_filter($checks));
        $total  = count($checks);
        $pct    = (int) round(($filled / $total) * 100);

        // First incomplete item label
        $nextItem = '';
        foreach ($checks as $key => $done) {
            if (! $done) {
                $nextItem = $labels[$key];
                break;
            }
        }

        return [
            'checks'          => $checks,
            'pct'             => $pct,
            'items_remaining' => $total - $filled,
            'next_item'       => $nextItem,
            'items'           => $labels,
        ];
    }

    public function recompute(User $user): int
    {
        $result = $this->compute($user);
        $user->update(['security_completion' => $result['pct']]);
        return $result['pct'];
    }

    private function metaValue(User $user, string $key): mixed
    {
        $row = $user->meta->firstWhere('meta_key', $key);
        return $row ? $row->typed_value : null;
    }
}
