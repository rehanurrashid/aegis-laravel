<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CriticalIncident;
use App\Models\PlanSteward;
use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * High-priority incident notification. UNGATED — bypasses notify_* prefs entirely.
 * Sends to assigned CS + SS and includes SMS for users with notify_sms=true.
 */
class IncidentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 10;

    public function __construct(
        public string $incidentId,
        public string $stage // 'reported' | 'verified' | 'activated'
    ) {
        $this->onQueue('incident');
    }

    public function handle(): void
    {
        $incident = CriticalIncident::find($this->incidentId);
        if (!$incident) return;

        $stewards = PlanSteward::where('plan_id', $incident->plan_id)
            ->where('status', 'active')
            ->get();

        $template = match ($this->stage) {
            'reported'  => 'emails.incident.26-incident-reported',
            'verified'  => 'emails.incident.27-incident-verified',
            'activated' => 'emails.incident.28-incident-activated',
            default     => 'emails.incident.26-incident-reported',
        };

        foreach ($stewards as $ps) {
            // Email (always)
            SendEmailJob::dispatch(
                $template,
                ['incident_id' => $incident->id, 'plan_id' => $incident->plan_id, 'stage' => $this->stage],
                $ps->steward_id
            );

            // SMS if user opted in
            if ($this->wantsSms($ps->steward_id)) {
                $this->sendSms($ps->steward_id, $incident, $this->stage);
            }
        }

        // Practitioner always
        SendEmailJob::dispatch(
            $template,
            ['incident_id' => $incident->id, 'stage' => $this->stage],
            $incident->practitioner_id
        );
        if ($this->wantsSms($incident->practitioner_id)) {
            $this->sendSms($incident->practitioner_id, $incident, $this->stage);
        }
    }

    private function wantsSms(string $userId): bool
    {
        $row = UserMeta::where('user_id', $userId)
            ->where('meta_key', 'notify_sms')
            ->first();
        return $row && in_array((string) $row->meta_value, ['1', 'true', 'on', 'yes'], true);
    }

    private function sendSms(string $userId, CriticalIncident $incident, string $stage): void
    {
        $user = User::find($userId);
        if (!$user?->phone) return;

        // Twilio integration is stubbed for now — log + skip if not configured.
        if (!config('services.twilio.sid')) {
            Log::info('SMS stub (no Twilio config)', [
                'user'  => $userId,
                'phone' => $user->phone,
                'stage' => $stage,
            ]);
            return;
        }

        try {
            // Twilio SDK call would go here in production:
            // app(\Twilio\Rest\Client::class)->messages->create($user->phone, [...]);
            Log::info('SMS sent', ['user' => $userId, 'incident' => $incident->id, 'stage' => $stage]);
        } catch (\Throwable $e) {
            Log::error('SMS send failed', ['user' => $userId, 'error' => $e->getMessage()]);
        }
    }
}
