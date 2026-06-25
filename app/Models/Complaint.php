<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Enums\SubmissionChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'complaints';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'submitter_id', 'subject', 'body',
        'category', 'submission_channel', 'status', 'priority',
        'assigned_to', 'escalated_at', 'resolved_at',
    ];

    protected $casts = [
        'category'           => ComplaintCategory::class,
        'submission_channel' => SubmissionChannel::class,
        'status'             => ComplaintStatus::class,
        'priority'           => ComplaintPriority::class,
        'escalated_at'       => 'datetime',
        'resolved_at'        => 'datetime',
    ];

    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitter_id'); }
    public function assignee(): BelongsTo  { return $this->belongsTo(User::class, 'assigned_to'); }

    public function meta(): HasMany    { return $this->hasMany(ComplaintMeta::class, 'complaint_id'); }
    public function replies(): HasMany { return $this->hasMany(ComplaintReply::class, 'complaint_id'); }

    public function scopeOpen($q)
    {
        return $q->whereIn('status', [
            ComplaintStatus::Open->value,
            ComplaintStatus::InProgress->value,
        ]);
    }

    public function scopeUrgent($q)    { return $q->where('priority', ComplaintPriority::Urgent->value); }
    public function scopeUnassigned($q) { return $q->whereNull('assigned_to'); }
}
