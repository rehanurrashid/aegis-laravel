<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StewardRole;
use App\Enums\StewardStatus;
use App\Enums\VaultAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanSteward extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'plan_stewards';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'plan_id', 'steward_id', 'role', 'steward_category', 'status',
        'permissions', 'vault_access', 'responsibilities',
        'signed_at', 'review_due_at', 'invited_at', 'request_sent_at',
        'expires_at', 'declined_at', 'declined_reason', 'ss_acknowledged_at',
    ];

    protected $casts = [
        'role'               => StewardRole::class,
        'status'             => StewardStatus::class,
        'vault_access'       => VaultAccess::class,
        'permissions'        => 'array',
        'responsibilities'   => 'array',
        'signed_at'          => 'datetime',
        'review_due_at'      => 'datetime',
        'invited_at'         => 'datetime',
        'request_sent_at'    => 'datetime',
        'expires_at'         => 'datetime',
        'declined_at'        => 'datetime',
        'ss_acknowledged_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ContinuityPlan::class, 'plan_id');
    }

    public function steward(): BelongsTo
    {
        return $this->belongsTo(User::class, 'steward_id');
    }

    public function scopeActive($q)      { return $q->where('status', StewardStatus::Active->value); }
    public function scopePrimary($q)     { return $q->where('role', StewardRole::Primary->value); }
    public function scopeAlternate($q)   { return $q->where('role', StewardRole::Alternate->value); }
    public function scopeOfCategory($q, string $cat) { return $q->where('steward_category', $cat); }
}
