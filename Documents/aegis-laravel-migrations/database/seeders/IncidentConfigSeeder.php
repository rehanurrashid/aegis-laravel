<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IncidentConfigSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $configs = [
            // p_sarah plan configs
            [
                'id'               => 'ic_sarah_short_term',
                'plan_id'          => 'plan_sarah',
                'incident_type'    => 'short_term_incapacitation',
                'is_active'        => 1,
                'docs_required'    => json_encode(['doctors_note', 'hospitalization_record']),
                'authorized_ss_ids'=> json_encode(['ss_linda']),
                'authorized_cs_ids'=> json_encode(['cs_marcus', 'cs_priya']),
                'created_at'       => $now->copy()->subMonths(6),
                'updated_at'       => $now->copy()->subMonths(6),
            ],
            [
                'id'               => 'ic_sarah_extended',
                'plan_id'          => 'plan_sarah',
                'incident_type'    => 'extended_leave',
                'is_active'        => 1,
                'docs_required'    => json_encode(['doctors_note', 'leave_documentation']),
                'authorized_ss_ids'=> json_encode(['ss_linda']),
                'authorized_cs_ids'=> json_encode(['cs_marcus', 'cs_priya']),
                'created_at'       => $now->copy()->subMonths(6),
                'updated_at'       => $now->copy()->subMonths(6),
            ],
            [
                'id'               => 'ic_sarah_death',
                'plan_id'          => 'plan_sarah',
                'incident_type'    => 'death',
                'is_active'        => 1,
                'docs_required'    => json_encode(['death_certificate']),
                'authorized_ss_ids'=> json_encode(['ss_linda']),
                'authorized_cs_ids'=> json_encode(['cs_marcus']),
                'created_at'       => $now->copy()->subMonths(6),
                'updated_at'       => $now->copy()->subMonths(6),
            ],
            [
                'id'               => 'ic_sarah_missing',
                'plan_id'          => 'plan_sarah',
                'incident_type'    => 'missing_person',
                'is_active'        => 1,
                'docs_required'    => json_encode(['police_report']),
                'authorized_ss_ids'=> json_encode(['ss_linda']),
                'authorized_cs_ids'=> json_encode(['cs_marcus', 'cs_priya']),
                'created_at'       => $now->copy()->subMonths(6),
                'updated_at'       => $now->copy()->subMonths(6),
            ],

            // p_maria plan configs
            [
                'id'               => 'ic_maria_short_term',
                'plan_id'          => 'plan_maria',
                'incident_type'    => 'short_term_incapacitation',
                'is_active'        => 1,
                'docs_required'    => json_encode(['doctors_note']),
                'authorized_ss_ids'=> json_encode(['ss_james']),
                'authorized_cs_ids'=> json_encode(['cs_priya']),
                'created_at'       => $now->copy()->subMonths(13),
                'updated_at'       => $now->copy()->subMonths(13),
            ],
        ];

        foreach ($configs as $c) {
            DB::table('plan_incident_configs')->updateOrInsert(['id' => $c['id']], $c);
        }
    }
}
