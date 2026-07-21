<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStructure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable snapshot of committed payment terms captured the moment
 * both parties sign a Rev 2 BP contract. No updates should be made
 * after creation — open a new contract if terms need to change.
 *
 * No standard timestamps (created_at / updated_at) — use snapshotted_at.
 */
class BpContractTerms extends Model
{
    protected $table        = 'bp_contract_terms';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id',
        'contract_id',
        'payment_structure',
        'upfront_percentage',
        'upfront_cents',
        'remaining_cents',
        'total_value_cents',
        'terms_note',
        'terms_source',
        'snapshotted_at',
    ];

    protected $casts = [
        'payment_structure'  => PaymentStructure::class,
        'upfront_percentage' => 'integer',
        'upfront_cents'      => 'integer',
        'remaining_cents'    => 'integer',
        'total_value_cents'  => 'integer',
        'snapshotted_at'     => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(BpContract::class, 'contract_id');
    }

    /** Human-readable summary of the snapshotted terms. */
    public function getSummaryAttribute(): string
    {
        $pct = (int) ($this->upfront_percentage ?? 0);
        return $this->payment_structure
            ? $this->payment_structure->chipLabel($pct)
            : 'Unknown structure';
    }
}
