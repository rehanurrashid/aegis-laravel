<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminAuditLog extends Model
{
    use HasFactory;

    protected $table        = 'admin_audit_log';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id', 'admin_id', 'action', 'linkable_type', 'linkable_id',
        'target_user_id', 'meta_json', 'created_at',
    ];

    protected $casts = [
        'meta_json'  => 'array',
        'created_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeByAction($q, string $action) { return $q->where('action', $action); }
}
