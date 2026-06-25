<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Admin\PayoutReleasedManually;
use App\Models\AdminAuditLog;
use App\Models\BpPayout;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Admin payout oversight — manual release, retry, cancel.
 * Aegis never holds funds; payouts execute via Stripe Connect Express.
 * This service brackets the Stripe call with audit log + activity write.
 */
class AdminPayoutService
{
    public function __construct(private ActivityService $activity) {}

    public function listPending(): Collection
    {
        return BpPayout::whereIn('status', ['pending', 'failed'])
            ->orderBy('created_at')
            ->get();
    }

    public function listAll(?string $status = null): Collection
    {
        $q = BpPayout::query();
        if ($status !== null) $q->where('status', $status);
        return $q->orderByDesc('created_at')->limit(500)->get();
    }

    /**
     * Manually release a pending payout. The actual Stripe transfer is dispatched
     * by the StripeEventListener / payout job — this method flips the status and
     * audit-logs the admin action.
     */
    public function releaseManually(User $admin, BpPayout $payout, ?string $note = null): BpPayout
    {
        if (!in_array($payout->status, ['pending', 'failed'], true)) {
            throw new RuntimeException("Cannot manually release a payout in status '{$payout->status}'.");
        }

        $before = $payout->status;
        $payout->update([
            'status'       => 'in_transit',
            'scheduled_at' => now(),
        ]);

        $this->audit($admin, 'release_payout', $payout->id, [
            'before' => $before,
            'after'  => 'in_transit',
            'note'   => $note,
        ]);

        event(new PayoutReleasedManually($payout, $admin));

        return $payout->fresh();
    }

    public function cancel(User $admin, BpPayout $payout, ?string $reason = null): BpPayout
    {
        if ($payout->status === 'paid') {
            throw new RuntimeException('Cannot cancel a paid payout.');
        }

        $payout->update(['status' => 'cancelled']);
        $this->audit($admin, 'cancel_payout', $payout->id, ['reason' => $reason]);

        return $payout->fresh();
    }

    public function retry(User $admin, BpPayout $payout): BpPayout
    {
        if ($payout->status !== 'failed') {
            throw new RuntimeException('Only failed payouts can be retried.');
        }

        $payout->update(['status' => 'pending']);
        $this->audit($admin, 'retry_payout', $payout->id, []);

        return $payout->fresh();
    }

    private function audit(User $admin, string $action, string $targetId, array $meta): void
    {
        AdminAuditLog::create([
            'id'          => 'aal_' . Str::lower(Str::random(12)),
            'admin_id'    => $admin->id,
            'action'      => $action,
            'target_type' => 'bp_payout',
            'target_id'   => $targetId,
            'meta_json'   => json_encode($meta),
            'created_at'  => now(),
        ]);
    }
}
