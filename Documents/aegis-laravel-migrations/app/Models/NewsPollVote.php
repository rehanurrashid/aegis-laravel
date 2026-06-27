<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsPollVote extends Model
{
    use HasFactory;

    protected $table        = 'news_poll_votes';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = ['id', 'post_id', 'user_id', 'option_key', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function post(): BelongsTo { return $this->belongsTo(NewsPost::class, 'post_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
