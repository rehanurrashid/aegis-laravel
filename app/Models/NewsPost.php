<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'news_posts';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'author_id', 'title', 'body', 'post_type',
        'role_visibility', 'audience', 'published', 'pinned', 'published_at',
        'tags', 'links', 'media', 'poll_question', 'poll_options', 'poll_closes_at',
    ];

    protected $casts = [
        'published'      => 'boolean',
        'pinned'         => 'boolean',
        'published_at'   => 'datetime',
        'poll_closes_at' => 'datetime',
        'tags'           => 'array',
        'links'          => 'array',
        'media'          => 'array',
        'poll_options'   => 'array',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany  { return $this->hasMany(NewsComment::class, 'post_id'); }
    public function reactions(): HasMany { return $this->hasMany(NewsReaction::class, 'post_id'); }
    public function pollVotes(): HasMany { return $this->hasMany(NewsPollVote::class, 'post_id'); }

    public function scopePublished($q) { return $q->where('published', 1); }
    public function scopePinned($q)    { return $q->where('pinned', 1); }
    public function scopeOfType($q, string $t) { return $q->where('post_type', $t); }
}
