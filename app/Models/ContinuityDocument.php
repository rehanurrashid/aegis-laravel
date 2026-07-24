<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContinuityDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'continuity_documents';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'plan_id', 'party_b_id', 'party_c_id', 'practitioner_id', 'reference', 'title',
        'doc_type', 'category', 'body', 'notes', 'status',
        'amends_document_id', 'holder_steward_id', 'file_ref',
        'is_supporting', 'related_to', 'auto_renew',
        'effective_date',
        'issued_at', 'expires_at', 'archived_at',
        'signed_by_id', 'signature_name', 'signature_ip', 'signed_at',
        'countersigned_by_id', 'countersignature_name', 'countersignature_ip', 'countersigned_at',
    ];

    protected $casts = [
        'status'          => DocumentStatus::class,
        'issued_at'       => 'datetime',
        'expires_at'      => 'datetime',
        'archived_at'     => 'datetime',
        'signed_at'       => 'datetime',
        'countersigned_at'=> 'datetime',
        'effective_date'  => 'date',
        'is_supporting'   => 'boolean',
        'auto_renew'      => 'boolean',
    ];

    public function plan(): BelongsTo         { return $this->belongsTo(ContinuityPlan::class, 'plan_id'); }
    public function practitioner(): BelongsTo { return $this->belongsTo(User::class, 'practitioner_id'); }
    public function holderSteward(): BelongsTo { return $this->belongsTo(User::class, 'holder_steward_id'); }
    public function signedBy(): BelongsTo      { return $this->belongsTo(User::class, 'signed_by_id'); }
    public function countersignedBy(): BelongsTo { return $this->belongsTo(User::class, 'countersigned_by_id'); }

    public function amends(): BelongsTo
    {
        return $this->belongsTo(ContinuityDocument::class, 'amends_document_id');
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(ContinuityDocument::class, 'amends_document_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(DocumentSignature::class, 'document_id');
    }

    public function scopeActive($q)   { return $q->where('status', DocumentStatus::Active->value); }
    public function scopeArchived($q) { return $q->where('status', DocumentStatus::Archived->value); }
}
