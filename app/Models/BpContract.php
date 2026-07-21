<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\PaymentStructure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BpContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'bp_contracts';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'job_id', 'proposal_id', 'practitioner_id', 'bp_id', 'title',
        'status', 'total_value_cents', 'payment_type', 'funding_mode',
        // Escrow tracking
        'escrow_funded_cents', 'escrow_released_cents', 'escrow_refunded_cents',
        'transfer_group', 'terms',
        // Timestamps
        'signed_at', 'cancelled_at', 'cancel_reason', 'started_at',
        'completed_at', 'ended_at',
        // Dual-signature (added Wave 1 migration)
        'practitioner_signed_at', 'practitioner_signature_name',
        'bp_signed_at', 'bp_signature_name', 'fully_executed_at',
        // Rev 2 — committed payment terms + direct-charge tracking
        'payment_structure', 'upfront_percentage', 'upfront_cents', 'remaining_cents',
        'terms_note', 'terms_source', 'terms_agreed_at',
        'paid_cents', 'upfront_charge_intent_id', 'upfront_charged_at',
        'completion_charge_intent_id', 'completion_charged_at', 'payment_failed_at',
    ];

    protected $casts = [
        'status'                  => ContractStatus::class,
        'total_value_cents'       => 'integer',
        'escrow_funded_cents'     => 'integer',
        'escrow_released_cents'   => 'integer',
        'escrow_refunded_cents'   => 'integer',
        'signed_at'               => 'datetime',
        'cancelled_at'            => 'datetime',
        'started_at'              => 'datetime',
        'completed_at'            => 'datetime',
        'ended_at'                => 'datetime',
        'practitioner_signed_at'      => 'datetime',
        'bp_signed_at'                => 'datetime',
        'fully_executed_at'           => 'datetime',
        // Rev 2
        'payment_structure'           => PaymentStructure::class,
        'upfront_percentage'          => 'integer',
        'upfront_cents'               => 'integer',
        'remaining_cents'             => 'integer',
        'paid_cents'                  => 'integer',
        'terms_agreed_at'             => 'datetime',
        'upfront_charged_at'          => 'datetime',
        'completion_charged_at'       => 'datetime',
        'payment_failed_at'           => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function job(): BelongsTo          { return $this->belongsTo(BpJob::class, 'job_id'); }
    public function proposal(): BelongsTo     { return $this->belongsTo(BpProposal::class, 'proposal_id'); }
    public function practitioner(): BelongsTo { return $this->belongsTo(User::class, 'practitioner_id'); }
    public function bp(): BelongsTo           { return $this->belongsTo(User::class, 'bp_id'); }

    public function terms(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BpContractTerms::class, 'contract_id');
    }

    public function meta(): HasMany           { return $this->hasMany(ContractMeta::class, 'contract_id'); }
    public function milestones(): HasMany     { return $this->hasMany(BpMilestone::class, 'contract_id'); }
    public function invoices(): HasMany       { return $this->hasMany(BpInvoice::class, 'contract_id'); }
    public function escrowLedger(): HasMany   { return $this->hasMany(BpEscrowLedger::class, 'contract_id'); }
    public function reviews(): HasMany        { return $this->hasMany(BpContractReview::class, 'contract_id'); }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($q)          { return $q->where('status', ContractStatus::Active->value); }
    public function scopePendingFunding($q)  { return $q->where('status', ContractStatus::PendingFunding->value); }

    // ── Computed helpers ─────────────────────────────────────────────────────

    /** Human-readable summary of committed payment terms. */
    public function getTermsSummaryAttribute(): string
    {
        $structure = $this->payment_structure;
        if (!$structure) {
            return 'Legacy escrow contract';
        }
        $pct = (int) ($this->upfront_percentage ?? 0);
        return $structure->chipLabel($pct);
    }

    /** Amount still held in Aegis escrow (funded but not yet released or refunded). */
    public function escrowHeldCents(): int
    {
        return max(0,
            (int) ($this->escrow_funded_cents ?? 0)
            - (int) ($this->escrow_released_cents ?? 0)
            - (int) ($this->escrow_refunded_cents ?? 0)
        );
    }

    /** True if both parties have signed. */
    public function isFullyExecuted(): bool
    {
        return (bool) $this->fully_executed_at;
    }

    /** True if provider has signed. */
    public function practitionerHasSigned(): bool
    {
        return (bool) $this->practitioner_signed_at;
    }

    /** True if BP has signed. */
    public function bpHasSigned(): bool
    {
        return (bool) $this->bp_signed_at;
    }
}
