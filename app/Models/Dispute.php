<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DisputeReason;
use App\Enums\DisputeResolution;
use App\Enums\DisputeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispute extends Model
{
    use HasFactory;

    protected $table        = 'disputes';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'disputer_id', 'respondent_id',
        'subject_type', 'subject_id',
        'reason', 'amount_disputed_cents', 'description',
        'status', 'resolution', 'resolution_cents', 'resolution_summary',
        'resolved_by',
        'opened_at', 'respondent_replied_at', 'resolved_at', 'closed_at',
    ];

    protected $casts = [
        'reason'                => DisputeReason::class,
        'status'                => DisputeStatus::class,
        'resolution'            => DisputeResolution::class,
        'amount_disputed_cents' => 'integer',
        'resolution_cents'      => 'integer',
        'opened_at'             => 'datetime',
        'respondent_replied_at' => 'datetime',
        'resolved_at'           => 'datetime',
        'closed_at'             => 'datetime',
    ];

    public function disputer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disputer_id');
    }

    public function respondent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondent_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class, 'dispute_id')->orderBy('created_at');
    }

    /**
     * Resolve the subject to its actual model instance. Not eager-loadable
     * because it's a manual polymorphic dispatch.
     */
    public function resolveSubject()
    {
        return match ($this->subject_type) {
            'cs_invoice' => CsInvoice::find($this->subject_id),
            'bp_invoice' => BpInvoice::find($this->subject_id),
            'bp_payout'  => BpPayout::find($this->subject_id),
            'session'    => ServiceSession::find($this->subject_id),
            default      => null,
        };
    }

    public function scopeActive($q)
    {
        return $q->whereIn('status', [
            DisputeStatus::Open->value,
            DisputeStatus::AwaitingResponse->value,
            DisputeStatus::UnderReview->value,
        ]);
    }
}
