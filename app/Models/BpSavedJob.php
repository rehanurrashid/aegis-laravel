<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpSavedJob extends Model
{
    use HasFactory;

    protected $table        = 'bp_saved_jobs';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = ['id', 'bp_id', 'job_id', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function bp(): BelongsTo  { return $this->belongsTo(User::class, 'bp_id'); }
    public function job(): BelongsTo { return $this->belongsTo(BpJob::class, 'job_id'); }
}
