<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IncidentUpdateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentUpdate extends Model
{
    use HasFactory;

    protected $table        = 'incident_updates';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = ['id', 'incident_id', 'actor_id', 'update_type', 'message', 'created_at'];

    protected $casts = [
        'update_type' => IncidentUpdateType::class,
        'created_at'  => 'datetime',
    ];

    public function incident(): BelongsTo { return $this->belongsTo(CriticalIncident::class, 'incident_id'); }
    public function actor(): BelongsTo    { return $this->belongsTo(User::class, 'actor_id'); }
}
