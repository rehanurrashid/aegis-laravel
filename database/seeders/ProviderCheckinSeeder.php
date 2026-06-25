<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds provider_checkins (renamed from ss_provider_checkins in migration 000072)
 * and ss_provider_notes.
 *
 * provider_checkins columns: id, steward_id, practitioner_id, steward_type, status, note, checked_at, created_at
 * ss_provider_notes columns: id, ss_id, practitioner_id, body, created_at, updated_at, deleted_at
 * Fixed: note_type and is_private removed from ss_provider_notes (don't exist)
 */
class ProviderCheckinSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── SS check-ins ───────────────────────────────────────────────────
        $ssCheckins = [
            ['steward_id' => 'ss_linda', 'practitioner_id' => 'p_sarah', 'status' => 'ok',          'note' => 'Routine check-in. Practitioner confirmed well and in office.',             'checked_at' => $now->copy()->subDays(14)->toDateTimeString()],
            ['steward_id' => 'ss_linda', 'practitioner_id' => 'p_sarah', 'status' => 'ok',          'note' => 'Phone check-in. No concerns.',                                            'checked_at' => $now->copy()->subDays(7)->toDateTimeString()],
            ['steward_id' => 'ss_linda', 'practitioner_id' => 'p_sarah', 'status' => 'concern',     'note' => 'Unable to reach by phone. Will attempt again tomorrow.',                  'checked_at' => $now->copy()->subDays(4)->toDateTimeString()],
            ['steward_id' => 'ss_linda', 'practitioner_id' => 'p_sarah', 'status' => 'unreachable', 'note' => 'Second attempt failed. Contacted emergency contact. Incident reported.',  'checked_at' => $now->copy()->subDays(3)->toDateTimeString()],
            ['steward_id' => 'ss_linda', 'practitioner_id' => 'p_sarah', 'status' => 'ok',          'note' => 'Hospital confirmed stable. Daily monitoring in effect.',                   'checked_at' => $now->copy()->subDays(2)->toDateTimeString()],
            ['steward_id' => 'ss_linda', 'practitioner_id' => 'p_sarah', 'status' => 'ok',          'note' => 'No change. Practitioner expected to be discharged in 7–10 days.',         'checked_at' => $now->copy()->subDays(1)->toDateTimeString()],
            ['steward_id' => 'ss_james', 'practitioner_id' => 'p_david', 'status' => 'ok',          'note' => 'Regular check-in.',                                                       'checked_at' => $now->copy()->subDays(10)->toDateTimeString()],
            ['steward_id' => 'ss_james', 'practitioner_id' => 'p_david', 'status' => 'unreachable', 'note' => 'No response — 3 attempts. Police wellness check requested. Incident filed.','checked_at' => $now->copy()->subHours(6)->toDateTimeString()],
            ['steward_id' => 'ss_james', 'practitioner_id' => 'p_maria', 'status' => 'ok',          'note' => 'Monthly check-in. All well.',                                              'checked_at' => $now->copy()->subWeeks(3)->toDateTimeString()],
            ['steward_id' => 'ss_james', 'practitioner_id' => 'p_maria', 'status' => 'concern',     'note' => 'Practitioner mentioned feeling overworked. Flagged for CS review.',        'checked_at' => $now->copy()->subDays(2)->toDateTimeString()],
        ];

        foreach ($ssCheckins as $c) {
            DB::table('provider_checkins')->insert([
                'id'           => (string) Str::uuid(),
                'steward_id'   => $c['steward_id'],
                'practitioner_id' => $c['practitioner_id'],
                'steward_type' => 'ss',
                'status'       => $c['status'],
                'note'         => $c['note'],
                'checked_at'   => $c['checked_at'],
                'created_at'   => $c['checked_at'],
            ]);
        }

        // ── CS proactive check-ins ─────────────────────────────────────────
        $csCheckins = [
            ['steward_id' => 'cs_marcus', 'practitioner_id' => 'p_sarah', 'status' => 'ok',      'note' => 'Quarterly standby touch-base. Plan review on schedule for Q3.',          'checked_at' => $now->copy()->subDays(45)->toDateTimeString()],
            ['steward_id' => 'cs_marcus', 'practitioner_id' => 'p_sarah', 'status' => 'ok',      'note' => 'Confirmed contact info up to date. Reviewed incident protocols.',         'checked_at' => $now->copy()->subDays(20)->toDateTimeString()],
            ['steward_id' => 'cs_marcus', 'practitioner_id' => 'p_sarah', 'status' => 'concern', 'note' => 'SS Linda flagged elevated stress observations. Reaching out to discuss.',  'checked_at' => $now->copy()->subDays(6)->toDateTimeString()],
            ['steward_id' => 'cs_marcus', 'practitioner_id' => 'p_sarah', 'status' => 'ok',      'note' => 'Incident response coordination complete. Vault access confirmed.',          'checked_at' => $now->copy()->subDays(3)->toDateTimeString()],
            ['steward_id' => 'cs_priya',  'practitioner_id' => 'p_maria', 'status' => 'ok',      'note' => 'Annual plan re-attestation scheduled for next week.',                      'checked_at' => $now->copy()->subDays(8)->toDateTimeString()],
            ['steward_id' => 'cs_priya',  'practitioner_id' => 'p_maria', 'status' => 'concern', 'note' => 'Per SS James report. Following up directly with Maria re: caseload.',      'checked_at' => $now->copy()->subDays(1)->toDateTimeString()],
        ];

        foreach ($csCheckins as $c) {
            DB::table('provider_checkins')->insert([
                'id'              => (string) Str::uuid(),
                'steward_id'      => $c['steward_id'],
                'practitioner_id' => $c['practitioner_id'],
                'steward_type'    => 'cs',
                'status'          => $c['status'],
                'note'            => $c['note'],
                'checked_at'      => $c['checked_at'],
                'created_at'      => $c['checked_at'],
            ]);
        }

        // ss_provider_notes columns: id, ss_id, practitioner_id, body, created_at, updated_at, deleted_at
        // Fixed: note_type and is_private removed (don't exist in migration)
        $notes = [
            ['ss_id' => 'ss_linda', 'practitioner_id' => 'p_sarah', 'body' => 'Practitioner mentioned increased caseload stress in our last call. Monitoring closely.',                                              'created_at' => $now->copy()->subDays(7)->toDateTimeString()],
            ['ss_id' => 'ss_linda', 'practitioner_id' => 'p_sarah', 'body' => 'Confirmed hospitalization via hospital patient services line. Case number provided to CS Marcus Williams.',                          'created_at' => $now->copy()->subDays(3)->toDateTimeString()],
            ['ss_id' => 'ss_james', 'practitioner_id' => 'p_maria', 'body' => 'Maria mentioned she is behind on her annual plan review. Encouraged her to reach out to CS Priya.',                                 'created_at' => $now->copy()->subDays(10)->toDateTimeString()],
        ];
        foreach ($notes as $n) {
            DB::table('ss_provider_notes')->insert([
                'id'              => (string) Str::uuid(),
                'ss_id'           => $n['ss_id'],
                'practitioner_id' => $n['practitioner_id'],
                'body'            => $n['body'],
                'created_at'      => $n['created_at'],
                'updated_at'      => $n['created_at'],
            ]);
        }
    }
}
