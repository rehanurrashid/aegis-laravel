<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpEngagementRequest extends Model
{
    protected $table = 'bp_engagement_requests';
    public $incrementing = false;
    protected $keyType  = 'string';

    protected $fillable = [
        'id', 'bp_id', 'practitioner_id', 'type',
        'engagement_type', 'start_date', 'duration', 'budget', 'payment_terms',
        'notes', 'service', 'size', 'timeline', 'urgent',
        'meeting_type', 'preferred_time', 'meeting_duration', 'timezone', 'agenda',
        'include_nda', 'require_baa', 'auto_contract',
        'status', 'response_note', 'responded_at',
    ];

    protected $casts = [
        'urgent'        => 'boolean',
        'include_nda'   => 'boolean',
        'require_baa'   => 'boolean',
        'auto_contract' => 'boolean',
        'start_date'    => 'date',
        'responded_at'  => 'datetime',
    ];

    public function bp(): BelongsTo            { return $this->belongsTo(User::class, 'bp_id'); }
    public function practitioner(): BelongsTo  { return $this->belongsTo(User::class, 'practitioner_id'); }
}
