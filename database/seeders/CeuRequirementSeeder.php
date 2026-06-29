<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds one realistic CEU requirement per practitioner so the
 * "CEU Requirements" card on the dashboard is populated on day one.
 */
class CeuRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $practitionerIds = DB::table('users')
            ->where('role', 'practitioner')
            ->pluck('id')
            ->toArray();

        $rows = [];
        foreach ($practitionerIds as $userId) {
            $rows[] = [
                'id'             => (string) Str::uuid(),
                'user_id'        => $userId,
                'jurisdiction'   => 'California — BBS (LMFT)',
                'total_hours'    => 36,
                'cycle'          => 'biannual',
                'due_date'       => '2026-12-31',
                'required_types' => 'Ethics (6), Law & Ethics, Suicide Risk Assessment',
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        if (!empty($rows)) {
            DB::table('ceu_requirements')->insertOrIgnore($rows);
        }
    }
}
