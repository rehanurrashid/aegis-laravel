<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkRequest extends Model
{
    use HasFactory;

    protected $table        = 'network_requests';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'requester_id', 'recipient_id', 'recipient_email',
        'status', 'message', 'invite_token', 'responded_at',
    ];

    protected $casts = [
        'status'       => RequestStatus::class,
        'responded_at' => 'datetime',
    ];

    protected $hidden = ['invite_token'];

    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requester_id'); }
    public function recipient(): BelongsTo { return $this->belongsTo(User::class, 'recipient_id'); }

    public function scopePending($q) { return $q->where('status', RequestStatus::Pending->value); }
}
