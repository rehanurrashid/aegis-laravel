<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CriticalIncident;
use App\Services\IncidentService;
use App\Services\PayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Hourly. Finds incidents that have hit "ready for closure" state
 * (all CS tasks complete) but have not been explicitly verified by
 * Provider or Support Steward within CS_INCIDENT_AUTOCLOSE_DAYS (default 7).
 *
 * For each, calls IncidentService::autoClose() which:
 *   - verifies closure on behalf of the system
 *   - closes the incident (re-seals vault, fans out)
 *   - if plan_steward.fee_cents > 0, auto-generates a CsInvoice
 *   - if plan_steward.auto_charge=1, fires the destination charge
 *
 * The window is Provider-friendly by default (7 days is generous) — if
 * Dr. Chapman wants faster settlement she can shorten it via env.
 */
class IncidentAutoCloseCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(IncidentService $incidents, PayoutService $payouts): void
    {
        $windowDays = (int) env('CS_INCIDENT_AUTOCLOSE_DAYS', 7);
        $cutoff     = now()->subDays($windowDays);

        // Incidents that:
        //   - are still active
        //   - have the [READY_FOR_CLOSURE] marker in summary
        //   - have no explicit verified_by_id (verifyClosure not called by human)
        //   - haven't been touched in the last $windowDays
        $stale = CriticalIncident::where('status', 'active')
            ->where('summary', 'like', '%[READY_FOR_CLOSURE]%')
            ->whereNull('verified_by_id')
            ->where('updated_at', '<', $cutoff)
            ->limit(100)
            ->get();

        Log::info('[AUTO_CLOSE_JOB] scan', [
            'window_days' => $windowDays,
            'candidates'  => $stale->count(),
        ]);

        foreach ($stale as $incident) {
            try {
                $incidents->autoClose($incident, $windowDays, $payouts);
            } catch (\Throwable $e) {
                Log::error('[AUTO_CLOSE_JOB] failure', [
                    'incident_id' => $incident->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }
    }
}
