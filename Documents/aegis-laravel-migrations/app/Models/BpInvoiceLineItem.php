<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpInvoiceLineItem extends Model
{
    use HasFactory;

    protected $table        = 'bp_invoice_line_items';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'invoice_id', 'description', 'quantity',
        'unit_amount_cents', 'line_total_cents', 'sort_order',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'unit_amount_cents' => 'integer',
        'line_total_cents'  => 'integer',
        'sort_order'        => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BpInvoice::class, 'invoice_id');
    }
}
