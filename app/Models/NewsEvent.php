<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'news_events';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'title', 'description', 'location',
        'starts_at', 'ends_at', 'role_visibility', 'published',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'published' => 'boolean',
    ];

    public function scopePublished($q) { return $q->where('published', 1); }
    public function scopeUpcoming($q)  { return $q->where('starts_at', '>=', now())->orderBy('starts_at'); }
}
