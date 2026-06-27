<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaultItemMeta extends Model
{
    use HasFactory;

    protected $table        = 'vault_item_meta';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = ['id', 'vault_item_id', 'meta_key', 'meta_value', 'meta_type'];

    protected $casts = [
        'meta_type' => MetaType::class,
    ];

    public function vaultItem(): BelongsTo
    {
        return $this->belongsTo(VaultItem::class, 'vault_item_id');
    }
}
