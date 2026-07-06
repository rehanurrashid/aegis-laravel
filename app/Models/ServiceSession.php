<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceSessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'service_sessions';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'service_request_id', 'service_id', 'practitioner_id', 'client_id',
        'status', 'scheduled_at', 'completed_at', 'amount_cents',
        'session_summary', 'session_action_items', 'share_notes_with_client', 'cancel_reason',
    ];

    protected $casts = [
        'status'                  => ServiceSessionStatus::class,
        'scheduled_at'            => 'datetime',
        'completed_at'            => 'datetime',
        'amount_cents'            => 'integer',
        'share_notes_with_client' => 'boolean',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    public function service(): BelongsTo      { return $this->belongsTo(Service::class, 'service_id'); }
    public function practitioner(): BelongsTo { return $this->belongsTo(User::class, 'practitioner_id'); }
    public function client(): BelongsTo       { return $this->belongsTo(User::class, 'client_id'); }

    public function scopeScheduled($q) { return $q->where('status', ServiceSessionStatus::Scheduled->value); }
    public function scopeCompleted($q) { return $q->where('status', ServiceSessionStatus::Completed->value); }
}
