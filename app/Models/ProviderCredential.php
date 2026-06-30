<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderCredential extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'provider_credentials';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'cred_type', 'icon', 'name', 'subtitle',
        'issuer', 'number', 'issued_on', 'expires_on',
        'document_path', 'sort_order',
    ];

    protected $appends = ['is_insurance', 'days_remaining'];

    protected $casts = [
        'issued_on'  => 'date',
        'expires_on' => 'date',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────
    public function getDaysRemainingAttribute(): ?int
    {
        return $this->expires_on ? (int) now()->startOfDay()->diffInDays($this->expires_on, false) : null;
    }

    public function getIsInsuranceAttribute(): bool
    {
        $t = strtolower((string) $this->cred_type);
        return str_contains($t, 'insurance') || str_contains($t, 'liability');
    }
}
