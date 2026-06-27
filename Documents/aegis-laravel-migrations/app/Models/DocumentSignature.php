<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SignerRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSignature extends Model
{
    use HasFactory;

    protected $table        = 'document_signatures';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id', 'document_id', 'signer_id', 'signer_role',
        'signature_name', 'signature_ip', 'signed_at', 'created_at',
    ];

    protected $casts = [
        'signer_role' => SignerRole::class,
        'signed_at'   => 'datetime',
        'created_at'  => 'datetime',
    ];

    public function document(): BelongsTo { return $this->belongsTo(ContinuityDocument::class, 'document_id'); }
    public function signer(): BelongsTo   { return $this->belongsTo(User::class, 'signer_id'); }
}
