<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageOverride extends Model
{
    use HasFactory;

    protected $table        = 'package_overrides';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'tier', 'price_monthly_cents', 'price_annual_cents',
        'feature_flags', 'limits', 'effective_at',
    ];

    protected $casts = [
        'feature_flags'       => 'array',
        'limits'              => 'array',
        'effective_at'        => 'datetime',
        'price_monthly_cents' => 'integer',
        'price_annual_cents'  => 'integer',
    ];

    public function scopeEffective($q) { return $q->where('effective_at', '<=', now()); }
    public function scopeForTier($q, string $tier) { return $q->where('tier', $tier); }
}
