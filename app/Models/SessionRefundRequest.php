<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SessionRefundRequestStatus;
use App\Enums\SessionRefundType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionRefundRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'session_refund_requests';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id',
        'session_id',
        'requested_by_id',
        'provider_id',
        'reason',
        'reason_detail',
        'refund_type',
        'amount_requested_cents',
        'status',
        'provider_response',
        'responded_at',
        'provider_deadline_at',
        'stripe_refund_id',
        'stripe_refund_id_balance',
        'refunded_cents',
        'escalated_dispute_id',
    ];

    protected $casts = [
        'status'                  => SessionRefundRequestStatus::class,
        'refund_type'             => SessionRefundType::class,
        'amount_requested_cents'  => 'integer',
        'refunded_cents'          => 'integer',
        'responded_at'            => 'datetime',
        'provider_deadline_at'    => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function session(): BelongsTo
    {
        return $this->belongsTo(ServiceSession::class, 'session_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class, 'escalated_dispute_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePendingReview($q)
    {
        return $q->where('status', SessionRefundRequestStatus::PendingReview->value);
    }

    public function scopeForProvider(mixed $q, string $providerId)
    {
        return $q->where('provider_id', $providerId);
    }

    public function scopeForClient(mixed $q, string $clientId)
    {
        return $q->where('requested_by_id', $clientId);
    }

    public function scopeOverdue(mixed $q)
    {
        return $q->where('status', SessionRefundRequestStatus::PendingReview->value)
            ->whereNotNull('provider_deadline_at')
            ->where('provider_deadline_at', '<', now());
    }

    // ── Computed helpers ──────────────────────────────────────────────────────

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === SessionRefundRequestStatus::PendingReview
            && $this->provider_deadline_at !== null
            && $this->provider_deadline_at->isPast();
    }

    public function getCanEscalateAttribute(): bool
    {
        return $this->status->canEscalate();
    }

    public function getIsActionableAttribute(): bool
    {
        return $this->status->isActionable();
    }
}
