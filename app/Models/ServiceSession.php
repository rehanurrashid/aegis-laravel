<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceSessionPaymentStatus;
use App\Enums\ServiceSessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'service_sessions';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id',
        'service_request_id',
        'service_id',
        'practitioner_id',
        'client_id',
        'status',
        'scheduled_at',
        'timezone',
        'completed_at',

        // ── Legacy single-charge amount (kept for backwards compat) ───────
        'amount_cents',

        // ── Wave 1: pricing ───────────────────────────────────────────────
        'original_amount_cents',        // listing price at request time
        'negotiated_amount_cents',      // provider override at accept time

        // ── Wave 1: deposit (30%) ─────────────────────────────────────────
        'deposit_cents',
        'deposit_charge_id',
        'deposit_paid_at',

        // ── Wave 1: balance (70%) ─────────────────────────────────────────
        'balance_cents',
        'balance_charge_id',
        'balance_paid_at',

        // ── Wave 1: refund tracking ───────────────────────────────────────
        'total_refunded_cents',
        'payment_status',

        // ── Existing: stripe + notes ──────────────────────────────────────
        'stripe_payment_intent_id',
        'paid_at',
        'session_summary',
        'session_action_items',
        'share_notes_with_client',
        'cancel_reason',
    ];

    protected $casts = [
        'status'                  => ServiceSessionStatus::class,
        'payment_status'          => ServiceSessionPaymentStatus::class,
        'scheduled_at'            => 'datetime',
        'completed_at'            => 'datetime',
        'deposit_paid_at'         => 'datetime',
        'balance_paid_at'         => 'datetime',
        'paid_at'                 => 'datetime',
        'amount_cents'            => 'integer',
        'original_amount_cents'   => 'integer',
        'negotiated_amount_cents' => 'integer',
        'deposit_cents'           => 'integer',
        'balance_cents'           => 'integer',
        'total_refunded_cents'    => 'integer',
        'share_notes_with_client' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(SessionRefundRequest::class, 'session_id');
    }

    public function activeRefundRequest(): HasOne
    {
        return $this->hasOne(SessionRefundRequest::class, 'session_id')
            ->whereIn('status', ['pending_review']);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeScheduled($q)
    {
        return $q->where('status', ServiceSessionStatus::Scheduled->value);
    }

    public function scopeCompleted($q)
    {
        return $q->where('status', ServiceSessionStatus::Completed->value);
    }

    public function scopeUnpaid($q)
    {
        return $q->where('payment_status', ServiceSessionPaymentStatus::Unpaid->value);
    }

    public function scopeDepositPaid($q)
    {
        return $q->where('payment_status', ServiceSessionPaymentStatus::DepositPaid->value);
    }

    public function scopeFullyPaid($q)
    {
        return $q->where('payment_status', ServiceSessionPaymentStatus::Paid->value);
    }

    public function scopePendingBalance($q)
    {
        return $q->whereIn('payment_status', [
            ServiceSessionPaymentStatus::Unpaid->value,
            ServiceSessionPaymentStatus::DepositPaid->value,
        ]);
    }

    // ── Computed helpers ──────────────────────────────────────────────────────

    /**
     * The agreed session amount — negotiated price if set, otherwise listing price.
     * This is what ALL deposit/balance calculations are based on.
     */
    public function getAgreedAmountCentsAttribute(): int
    {
        return $this->negotiated_amount_cents ?? $this->amount_cents ?? 0;
    }

    /**
     * What a 30% deposit should be, calculated from agreed amount.
     * Rounded down to nearest cent to avoid overcharging.
     */
    public function getExpectedDepositCentsAttribute(): int
    {
        return (int) floor($this->agreed_amount_cents * 0.30);
    }

    /**
     * Remaining balance after deposit.
     */
    public function getExpectedBalanceCentsAttribute(): int
    {
        return $this->agreed_amount_cents - $this->expected_deposit_cents;
    }

    /**
     * Net amount still owed by the client.
     */
    public function getRemainingCentsAttribute(): int
    {
        $paid = ($this->deposit_cents ?? 0) + ($this->balance_cents ?? 0);
        $refunded = $this->total_refunded_cents ?? 0;
        return max(0, $this->agreed_amount_cents - $paid + $refunded);
    }

    /**
     * Human-readable invoice reference, e.g. SES-2026-07-ABC1D2E3
     */
    public function getInvoiceNumberAttribute(): string
    {
        $date = $this->scheduled_at?->format('Y-m') ?? $this->created_at->format('Y-m');
        return 'SES-' . $date . '-' . strtoupper(substr($this->id, 0, 8));
    }

    /**
     * True when client has an active refund request pending provider review.
     */
    public function getHasPendingRefundRequestAttribute(): bool
    {
        return $this->refundRequests()
            ->where('status', 'pending_review')
            ->exists();
    }
}
