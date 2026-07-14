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
        // Added by 2026_07_13_000002_add_vault_item_file_columns
        'description', 'file_name', 'file_size', 'mime_type', 's3_key',
        'client_location', 'client_phone', 'client_email', 'client_service', 'client_notes',
        'issued_at', 'expires_at', 'tags',
    ];

    protected $casts = [
        'zone'                    => VaultZone::class,
        'status'                  => VaultItemStatus::class,
        'access_grant'            => 'array',
        'tags'                    => 'array',
        'client_priority'         => 'integer',
        'credential_password_enc' => 'encrypted',
        'issued_at'               => 'date:Y-m-d',
        'expires_at'              => 'date:Y-m-d',
        'file_size'               => 'integer',
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
