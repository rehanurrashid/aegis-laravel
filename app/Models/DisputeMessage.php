<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisputeMessage extends Model
{
    use HasFactory;

    protected $table        = 'dispute_messages';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false; // only created_at, set at insert

    protected $fillable = [
        'id', 'dispute_id', 'author_id', 'author_role', 'body', 'attachment_url', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class, 'dispute_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
