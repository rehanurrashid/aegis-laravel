<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ActivitySeverity;
use App\Models\ContinuityPlan;
use App\Services\ActivityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Daily check. Finds plans whose vault attestation is older than 1 year
 * (or never attested on an active plan) and warns the practitioner.
 */
class VaultSealCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(ActivityService $activity): void
    {
        $oneYearAgo = now()->subYear();

        $stale = ContinuityPlan::where('status', 'active')
            ->where(function ($q) use ($oneYearAgo) {
                $q->whereNull('vault_attested_at')
                  ->orWhere('vault_attested_at', '<=', $oneYearAgo);
            })
            ->get();

        foreach ($stale as $plan) {
            $activity->log(
                $plan->practitioner_id,
                'provider',
                'vault',
                ActivitySeverity::Warning,
                'vault_attestation_stale',
                'Your vault attestation needs refreshing',
                'Confirm that all essential supplemental information is still up to date in your Vault.',
                'continuity_plan',
                $plan->id,
                null
            );
        }
    }
}
