<?php
declare(strict_types=1);
namespace App\Models;

use App\Enums\ServiceRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'service_requests';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'service_id', 'practitioner_id', 'inquirer_id',
        'inquirer_name', 'inquirer_email', 'message',
        'preferred_timezone', 'preferred_date', 'preferred_time',
        'status', 'response_note', 'responded_at',
        // Rev 4 — payment terms
        'proposed_payment_structure',
        'proposed_upfront_percentage',
        'proposed_terms_note',
        'terms_source',
    ];

    protected $casts = [
        'status'                      => ServiceRequestStatus::class,
        'responded_at'                => 'datetime',
        'proposed_payment_structure'  => \App\Enums\PaymentStructure::class,
        'proposed_upfront_percentage' => 'integer',
    ];

    public function service(): BelongsTo      { return $this->belongsTo(Service::class, 'service_id'); }
    public function practitioner(): BelongsTo { return $this->belongsTo(User::class, 'practitioner_id'); }
    public function inquirer(): BelongsTo     { return $this->belongsTo(User::class, 'inquirer_id'); }
    public function sessions(): HasMany       { return $this->hasMany(ServiceSession::class, 'service_request_id'); }
    public function scopeNew($q)              { return $q->where('status', ServiceRequestStatus::New->value); }
}
