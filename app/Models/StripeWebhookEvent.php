<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeWebhookEvent extends Model
{
    use HasFactory;

    protected $table        = 'stripe_webhook_events';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id', 'stripe_event_id', 'event_type', 'payload_json',
        'processed', 'received_at', 'processed_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'processed'    => 'boolean',
        'received_at'  => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function scopeUnprocessed($q) { return $q->where('processed', 0); }

    public function isProcessed(): bool { return $this->processed === true; }
}
