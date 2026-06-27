<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaxDocStatus;
use App\Enums\TaxDocType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BpTaxDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'bp_tax_documents';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = ['id', 'bp_id', 'doc_type', 'status', 'download_url', 'year'];

    protected $casts = [
        'doc_type' => TaxDocType::class,
        'status'   => TaxDocStatus::class,
        'year'     => 'integer',
    ];

    public function bp(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bp_id');
    }
}
