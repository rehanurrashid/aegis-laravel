<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Jobs\ActivityFanoutJob;
use App\Models\ActivityEvent;
use App\Models\PlanSteward;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Single entry point for cross-portal notification writes.
 * Replaces aegis_log_activity() from _shared/models_write.php.
 */
class ActivityService
{
    /**
     * Write one activity_events row for a single recipient.
     *
     * entry_type:  'log'          = actor's own history (My Activity tab)
     *              'notification' = another party's feed (Notifications tab)
     * actor_id:    ID of the user who triggered the action. Set on notifications
     *              so the recipient knows who acted ("Dr Johnson accepted your proposal").
     *              Defaults to $userId when entry_type='log' (actor is recipient).
     */
    public function log(
        string $userId,
        string $portal,
        string $module,
        ActivitySeverity $severity,
        string $action,
        string $title,
        string $description,
        ?string $linkableType = null,
        ?string $linkableId = null,
        ?string $relatedUserId = null,
        string $entryType = 'log',
        ?string $actorId = null
    ): ActivityEvent {
        return ActivityEvent::create([
            'id'                  => 'ae_' . Str::lower(Str::random(12)),
            'user_id'             => $userId,
            'portal'              => $portal,
            'event_type'          => $this->mapModuleToEventType($module),
            'severity'            => $severity->value,
            'module'              => $module,
            'entry_type'          => $entryType,
            'actor_id'            => $actorId ?? ($entryType === 'log' ? $userId : $relatedUserId),
            'action'              => $action,
            'title'               => $title,
            'description'         => $description,
            'linkable_type'       => $linkableType,
            'linkable_id'         => $linkableId,
            'scoped_provider_id'  => $relatedUserId,
            'read_at'             => null,
            'created_at'          => now(),
        ]);
    }

    /**
     * Map service-layer module (plan/steward/etc.) → activity_events.event_type enum.
     */
    private function mapModuleToEventType(string $module): string
    {
        return match ($module) {
            'message'                => 'message',
            'task'                   => 'task',
            'job_postings'           => 'job_postings',
            'document'               => 'document',
            'incident'               => 'incident',
            'vault'                  => 'vault',
            'attestation'            => 'attestation',
            'payment', 'billing'     => 'payment',
            'account', 'steward'     => 'account',
            'system'                 => 'system',
            'support'                => 'support',
            'referral'               => 'referral',
            'news'                   => 'news',
            'event'                  => 'event',
            'plan', 'compliance'     => 'compliance',
            default                  => 'system',
        };
    }

    /**
     * Fan out to multiple recipients. For >3 recipients, queues via Job.
     *
     * @param array<int, array{user_id:string,portal:string}> $recipients
     * @param array{module:string,severity:ActivitySeverity,event_type:string,title:string,description:string,linkable_type?:?string,linkable_id?:?string,related_user_id?:?string} $payload
     */
    public function fanOut(array $recipients, array $payload): void
    {
        if (count($recipients) > 3) {
            ActivityFanoutJob::dispatch($recipients, $this->normalizePayload($payload));
            return;
        }

        foreach ($recipients as $r) {
            $this->log(
                $r['user_id'],
                $r['portal'],
                $payload['module'],
                $payload['severity'],
                $payload['event_type'],
                $payload['title'],
                $payload['description'],
                $payload['linkable_type'] ?? null,
                $payload['linkable_id']   ?? null,
                $payload['related_user_id'] ?? null,
            );
        }
    }

    /**
     * Get all active steward recipients (CS + SS) for a plan.
     *
     * @return array<int, array{user_id:string, portal:string}>
     */
    public function getPlanStewardRecipients(string $planId): array
    {
        return PlanSteward::where('plan_id', $planId)
            ->where('status', 'active')
            ->get()
            ->map(fn (PlanSteward $ps) => [
                'user_id' => $ps->steward_id,
                'portal'  => $ps->steward_type === 'continuity_steward'
                    ? 'continuity_steward'
                    : 'support_steward',
            ])
            ->toArray();
    }

    public function markRead(string $eventId, string $userId): bool
    {
        return ActivityEvent::where('id', $eventId)
                ->where('user_id', $userId)
                ->update(['read_at' => now()]) > 0;
    }

    public function markAllRead(string $userId): int
    {
        return ActivityEvent::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * @param array{module?:string,severity?:string,unread?:bool,portal?:string} $filters
     */
    public function getForUser(string $userId, array $filters = [], int $limit = 50): Collection
    {
        $query = ActivityEvent::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if (!empty($filters['module']))   $query->where('module', $filters['module']);
        if (!empty($filters['severity'])) $query->where('severity', $filters['severity']);
        if (!empty($filters['portal']))   $query->where('portal', $filters['portal']);
        if (!empty($filters['unread']))   $query->whereNull('read_at');

        return $query->limit($limit)->get();
    }

    public function getUnreadCount(string $userId): int
    {
        return ActivityEvent::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Serialize ActivitySeverity enum for Job queue payload.
     */
    private function normalizePayload(array $payload): array
    {
        if (isset($payload['severity']) && $payload['severity'] instanceof ActivitySeverity) {
            $payload['severity'] = $payload['severity']->value;
        }
        return $payload;
    }
}
