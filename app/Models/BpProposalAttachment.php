<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpProposalAttachment extends Model
{
    protected $table        = 'bp_proposal_attachments';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id', 'proposal_id', 'filename', 'path', 'mime_type', 'size_bytes', 'uploaded_at',
    ];

    protected $casts = [
        'size_bytes'  => 'integer',
        'uploaded_at' => 'datetime',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(BpProposal::class, 'proposal_id');
    }
}
