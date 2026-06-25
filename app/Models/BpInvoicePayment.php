<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpInvoicePayment extends Model
{
    use HasFactory;

    protected $table        = 'bp_invoice_payments';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id', 'invoice_id', 'payer_id', 'amount_cents',
        'method', 'status', 'stripe_payment_intent', 'paid_at', 'created_at',
    ];

    protected $casts = [
        'method'       => PaymentMethod::class,
        'status'       => PaymentStatus::class,
        'amount_cents' => 'integer',
        'paid_at'      => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(BpInvoice::class, 'invoice_id'); }
    public function payer(): BelongsTo   { return $this->belongsTo(User::class, 'payer_id'); }
}
