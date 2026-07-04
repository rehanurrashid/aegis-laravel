<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * FixNinaConnectionSeeder — removes any stale network_connections row that
 * links nd_nina_park with connection_type = 'business_partner'.
 *
 * Nina Park (nd_nina_park) is a practitioner seeded by
 * NetworkRecommendationSeeder as a shadow-candidate. She is NOT a business
 * partner. A previous seeder run appears to have inserted a row with the
 * wrong connection_type, causing her to appear in the "My Partners" tab.
 *
 * Run once:
 *   php artisan db:seed --class=FixNinaConnectionSeeder
 */
class FixNinaConnectionSeeder extends Seeder
{
    public function run(): void
    {
        // Delete any connection row where nd_nina_park appears as either party
        // with connection_type = 'business_partner'.
        $deleted = DB::table('network_connections')
            ->where('connection_type', 'business_partner')
            ->where(function ($q) {
                $q->where('user_id', 'nd_nina_park')
                  ->orWhere('connected_user_id', 'nd_nina_park');
            })
            ->delete();

        $this->command->info("Deleted {$deleted} stale business_partner connection(s) for nd_nina_park.");

        // Also delete any incorrect rows for other nd_* directory practitioners
        // (they are all role=practitioner, never role=business_partner).
        $ndIds = DB::table('users')
            ->where('id', 'like', 'nd_%')
            ->where('role', 'practitioner')
            ->pluck('id');

        if ($ndIds->isNotEmpty()) {
            $cleaned = DB::table('network_connections')
                ->where('connection_type', 'business_partner')
                ->where(function ($q) use ($ndIds) {
                    $q->whereIn('user_id', $ndIds)
                      ->orWhereIn('connected_user_id', $ndIds);
                })
                ->delete();

            $this->command->info("Cleaned {$cleaned} additional stale bp-type rows for nd_* practitioner users.");
        }
    }
}
