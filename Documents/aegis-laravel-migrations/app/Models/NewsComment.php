<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'news_comments';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = ['id', 'post_id', 'author_id', 'body'];

    public function post(): BelongsTo   { return $this->belongsTo(NewsPost::class, 'post_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
}
