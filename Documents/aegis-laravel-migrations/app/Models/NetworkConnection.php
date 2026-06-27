<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConnectionStatus;
use App\Enums\ConnectionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetworkConnection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'network_connections';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'connected_user_id', 'connection_type', 'status', 'connected_at',
    ];

    protected $casts = [
        'connection_type' => ConnectionType::class,
        'status'          => ConnectionStatus::class,
        'connected_at'    => 'datetime',
    ];

    public function owner(): BelongsTo  { return $this->belongsTo(User::class, 'user_id'); }
    public function target(): BelongsTo { return $this->belongsTo(User::class, 'connected_user_id'); }

    public function scopeActive($q)   { return $q->where('status', ConnectionStatus::Active->value); }
    public function scopeArchived($q) { return $q->where('status', ConnectionStatus::Archived->value); }
    public function scopeOfType($q, ConnectionType $t) { return $q->where('connection_type', $t->value); }
}
