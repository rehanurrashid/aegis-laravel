<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractMeta extends Model
{
    use HasFactory;

    protected $table        = 'contract_meta';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = ['id', 'contract_id', 'meta_key', 'meta_value', 'meta_type'];

    protected $casts = [
        'meta_type' => MetaType::class,
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(BpContract::class, 'contract_id');
    }
}
