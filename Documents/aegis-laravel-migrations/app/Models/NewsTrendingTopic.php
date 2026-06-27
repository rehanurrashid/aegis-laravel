<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsTrendingTopic extends Model
{
    use HasFactory;

    protected $table        = 'news_trending_topics';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = ['id', 'topic', 'score'];

    protected $casts = [
        'score' => 'integer',
    ];

    public function scopeTop($q, int $limit = 5)
    {
        return $q->orderByDesc('score')->limit($limit);
    }
}
