<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CsInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'cs_invoices';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'cs_id', 'practitioner_id', 'invoice_number', 'status',
        'total_cents', 'currency', 'issued_at', 'due_at', 'paid_at',
    ];

    protected $casts = [
        'status'      => InvoiceStatus::class,
        'total_cents' => 'integer',
        'issued_at'   => 'datetime',
        'due_at'      => 'datetime',
        'paid_at'     => 'datetime',
    ];

    public function cs(): BelongsTo           { return $this->belongsTo(User::class, 'cs_id'); }
    public function practitioner(): BelongsTo { return $this->belongsTo(User::class, 'practitioner_id'); }

    public function scopeCollectible($q)
    {
        return $q->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value]);
    }

    public function scopePaid($q) { return $q->where('status', InvoiceStatus::Paid->value); }
}
