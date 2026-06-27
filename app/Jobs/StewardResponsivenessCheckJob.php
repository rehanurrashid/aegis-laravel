<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ActivitySeverity;
use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Services\ActivityService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Daily job — scans all active Continuity Steward assignments and flags any
 * CS that has not interacted (accepted tasks, added updates, etc.) within a
 * configurable window. Dispatches SendEmailJob to the practitioner and the
 * SS (if present) using the gaps.65-cs-flagged-unresponsive template.
 */
class StewardResponsivenessCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Inactivity threshold in days before flagging. */
    public int $inactivityDays;

    public int $tries = 3;

    public function __construct(int $inactivityDays = 14)
    {
        $this->inactivityDays = $inactivityDays;
        $this->onQueue('default');
    }

    public function handle(ActivityService $activity): void
    {
        $cutoff = Carbon::now()->subDays($this->inactivityDays);

        $unresponsive = PlanSteward::where('status', 'active')
            ->where('steward_type', 'continuity_steward')
            ->where(function ($q) use ($cutoff) {
                $q->where('last_activity_at', '<', $cutoff)
                  ->orWhereNull('last_activity_at');
            })
            ->with(['plan', 'steward'])
            ->get();

        foreach ($unresponsive as $ps) {
            $plan = $ps->plan;
            if (! $plan) {
                continue;
            }

            Log::info('StewardResponsivenessCheckJob: flagging CS', [
                'plan_steward_id' => $ps->id,
                'steward_id'      => $ps->steward_id,
                'plan_id'         => $plan->id,
            ]);

            $flaggedAt = now()->toDateTimeString();

            // Notify the practitioner.
            SendEmailJob::dispatch(
                'emails.gaps.65-cs-flagged-unresponsive',
                [
                    'cs_name'       => $ps->steward?->display_name ?? 'Your Continuity Steward',
                    'flagged_at'    => $flaggedAt,
                    'stewards_url'  => rtrim(config('app.url'), '/') . '/provider/stewards',
                ],
                $plan->practitioner_id
            );

            // Also notify any active SS on the plan.
            $ss = PlanSteward::where('plan_id', $plan->id)
                ->where('status', 'active')
                ->where('steward_type', 'support_steward')
                ->with('steward')
                ->first();

            if ($ss) {
                SendEmailJob::dispatch(
                    'emails.gaps.65-cs-flagged-unresponsive',
                    [
                        'cs_name'       => $ps->steward?->display_name ?? 'Continuity Steward',
                        'flagged_at'    => $flaggedAt,
                        'stewards_url'  => rtrim(config('app.url'), '/') . '/cs/providers',
                    ],
                    $ss->steward_id
                );
            }

            // Log to activity feed.
            $activity->log(
                $plan->practitioner_id,
                'provider',
                'steward',
                ActivitySeverity::Warning,
                'cs_flagged_unresponsive',
                'Continuity Steward flagged as unresponsive',
                ($ps->steward?->display_name ?? 'CS') . " has not interacted in {$this->inactivityDays} days.",
                'plan_steward',
                $ps->id,
                null
            );
        }
    }
}
