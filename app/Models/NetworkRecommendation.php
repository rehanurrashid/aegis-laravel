<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * NetworkRecommendation
 *
 * Powers the two "Recommended" carousels on the Network Search Providers tab:
 *  - kind = 'partner_category' → rnp-card (specialty categories nearby)
 *  - kind = 'shadow_provider'  → rsp-card (AI-matched shadow practitioners)
 *
 * A row with user_id NULL is a global default surfaced to any practitioner
 * who has no per-user recommendations of that kind seeded yet.
 */
class NetworkRecommendation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'network_recommendations';

    protected $keyType   = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'kind',
        'label',
        'description',
        'icon',
        'nearby_count',
        'priority',
        'provider_user_id',
        'match_score',
        'sort_order',
    ];

    protected $casts = [
        'nearby_count' => 'integer',
        'match_score'  => 'integer',
        'sort_order'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function providerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_user_id');
    }
}
