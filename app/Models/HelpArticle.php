<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'help_articles';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'category', 'title', 'body',
        'role_visibility', 'sort_order', 'published',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'published'  => 'boolean',
    ];

    public function scopePublished($q) { return $q->where('published', 1); }
    public function scopeOrdered($q)   { return $q->orderBy('sort_order'); }
    public function scopeOfCategory($q, string $cat) { return $q->where('category', $cat); }
}
