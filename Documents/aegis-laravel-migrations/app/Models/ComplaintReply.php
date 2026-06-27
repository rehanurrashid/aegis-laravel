<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintReply extends Model
{
    use HasFactory;

    protected $table        = 'complaint_replies';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = ['id', 'complaint_id', 'author_id', 'body', 'is_internal', 'created_at'];

    protected $casts = [
        'is_internal' => 'boolean',
        'created_at'  => 'datetime',
    ];

    public function complaint(): BelongsTo { return $this->belongsTo(Complaint::class, 'complaint_id'); }
    public function author(): BelongsTo    { return $this->belongsTo(User::class, 'author_id'); }

    public function scopePublic($q)   { return $q->where('is_internal', 0); }
    public function scopeInternal($q) { return $q->where('is_internal', 1); }
}
