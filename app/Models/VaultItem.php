<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VaultItemStatus;
use App\Enums\VaultZone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VaultItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'vault_items';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'practitioner_id', 'zone', 'category', 'title', 'sub_label', 'status',
        'credential_username', 'credential_password_enc', 'credential_url',
        'client_name', 'client_priority', 'file_ref', 'access_grant',
    ];

    protected $casts = [
        'zone'                    => VaultZone::class,
        'status'                  => VaultItemStatus::class,
        'access_grant'            => 'array',
        'client_priority'         => 'integer',
        'credential_password_enc' => 'encrypted',
    ];

    protected $hidden = ['credential_password_enc'];

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function meta(): HasMany
    {
        return $this->hasMany(VaultItemMeta::class, 'vault_item_id');
    }

    public function accessLog(): HasMany
    {
        return $this->hasMany(VaultAccessLog::class, 'vault_item_id');
    }

    public function scopeInZone($q, VaultZone $zone)    { return $q->where('zone', $zone->value); }
    public function scopePriority($q)                    { return $q->where('status', VaultItemStatus::Priority->value); }
    public function scopeForPractitioner($q, string $id) { return $q->where('practitioner_id', $id); }

    public function isCredential(): bool { return $this->zone === VaultZone::Credentials; }
}
