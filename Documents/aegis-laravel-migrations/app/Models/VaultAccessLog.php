<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VaultAccessType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaultAccessLog extends Model
{
    use HasFactory;

    protected $table        = 'vault_access_log';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id', 'vault_item_id', 'practitioner_id', 'actor_id',
        'access_type', 'recipient_id', 'ip_address', 'created_at',
    ];

    protected $casts = [
        'access_type' => VaultAccessType::class,
        'created_at'  => 'datetime',
    ];

    public function vaultItem(): BelongsTo    { return $this->belongsTo(VaultItem::class, 'vault_item_id'); }
    public function practitioner(): BelongsTo { return $this->belongsTo(User::class, 'practitioner_id'); }
    public function actor(): BelongsTo        { return $this->belongsTo(User::class, 'actor_id'); }
    public function recipient(): BelongsTo    { return $this->belongsTo(User::class, 'recipient_id'); }
}
