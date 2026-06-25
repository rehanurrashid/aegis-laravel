<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PractitionerPaymentKind;
use App\Enums\PractitionerPaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PractitionerPayment extends Model
{
    use HasFactory;

    protected $table        = 'practitioner_payments';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'practitioner_id', 'payment_method_id', 'kind',
        'amount_cents', 'currency', 'status', 'payment_method_label',
        'stripe_charge_id', 'paid_at',
    ];

    protected $casts = [
        'kind'         => PractitionerPaymentKind::class,
        'status'       => PractitionerPaymentStatus::class,
        'amount_cents' => 'integer',
        'paid_at'      => 'datetime',
    ];

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PractitionerPaymentMethod::class, 'payment_method_id');
    }

    public function scopePaid($q)
    {
        return $q->where('status', PractitionerPaymentStatus::Paid->value);
    }

    public function scopeOfKind($q, PractitionerPaymentKind $k)
    {
        return $q->where('kind', $k->value);
    }
}
