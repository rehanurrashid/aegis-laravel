<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CheckinStatus;
use App\Enums\StewardType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Provider check-ins by CS or SS. Table renamed from `ss_provider_checkins`
 * to `provider_checkins` in migration 000072. `ss_id` renamed to `steward_id`,
 * `steward_type` enum column added to discriminate cs vs ss.
 *
 * Model class name retained as SsProviderCheckin for backwards-compat with
 * existing seeders, services, and controllers that reference it.
 */
class SsProviderCheckin extends Model
{
    use HasFactory;

    protected $table        = 'provider_checkins';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id', 'steward_id', 'steward_type', 'practitioner_id',
        'status', 'note', 'checked_at', 'created_at',
    ];

    protected $casts = [
        'steward_type' => StewardType::class,
        'status'       => CheckinStatus::class,
        'checked_at'   => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function steward(): BelongsTo
    {
        return $this->belongsTo(User::class, 'steward_id');
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function scopeByCs($q) { return $q->where('steward_type', StewardType::Cs->value); }
    public function scopeBySs($q) { return $q->where('steward_type', StewardType::Ss->value); }

    public function scopeConcerns($q)
    {
        return $q->whereIn('status', [
            CheckinStatus::Concern->value,
            CheckinStatus::Unreachable->value,
        ]);
    }
}
