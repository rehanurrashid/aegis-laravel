<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BpInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'bp_invoices';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'bp_id', 'practitioner_id', 'contract_id', 'invoice_number',
        'status', 'subtotal_cents', 'total_cents', 'currency', 'notes',
        'issued_at', 'due_at', 'paid_at', 'voided_at',
    ];

    protected $casts = [
        'status'         => InvoiceStatus::class,
        'subtotal_cents' => 'integer',
        'total_cents'    => 'integer',
        'issued_at'      => 'datetime',
        'due_at'         => 'datetime',
        'paid_at'        => 'datetime',
        'voided_at'      => 'datetime',
    ];

    public function bp(): BelongsTo           { return $this->belongsTo(User::class, 'bp_id'); }
    public function practitioner(): BelongsTo { return $this->belongsTo(User::class, 'practitioner_id'); }
    public function contract(): BelongsTo     { return $this->belongsTo(BpContract::class, 'contract_id'); }

    public function lineItems(): HasMany { return $this->hasMany(BpInvoiceLineItem::class, 'invoice_id'); }
    public function payments(): HasMany  { return $this->hasMany(BpInvoicePayment::class, 'invoice_id'); }

    public function scopeCollectible($q)
    {
        return $q->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value]);
    }
}
