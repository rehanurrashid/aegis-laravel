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
        'id', 'title', 'description', 'location', 'category',
        'ceu_credits', 'is_free', 'price_cents', 'rsvp_url', 'rsvps_json',
        'organizer', 'status', 'role_visibility', 'published',
        'starts_at', 'ends_at',
    ];

    protected $casts = [
        'starts_at'   => 'datetime',
        'ends_at'     => 'datetime',
        'published'   => 'boolean',
        'is_free'     => 'boolean',
        'ceu_credits' => 'decimal:2',
        'rsvps_json'  => 'array',
    ];

    public function scopePublished($q) { return $q->where('published', 1); }
    public function scopeApproved($q)  { return $q->where('status', 'approved'); }
    public function scopeUpcoming($q)  { return $q->where('starts_at', '>=', now())->orderBy('starts_at'); }
    public function scopePast($q)      { return $q->where('starts_at', '<', now())->orderByDesc('starts_at'); }

    /** Check whether a given user ID has RSVPed as 'going'. */
    public function isAttending(string $userId): bool
    {
        $rsvps = $this->rsvps_json ?? [];
        return ($rsvps[$userId]['status'] ?? null) === 'going';
    }
}
