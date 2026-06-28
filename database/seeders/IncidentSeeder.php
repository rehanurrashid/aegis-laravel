<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IncidentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $incidents = [
            [
                'id'              => 'inc_sarah_active',
                'plan_id'         => 'plan_sarah',
                'practitioner_id' => 'p_sarah',
                'reported_by_id'  => 'ss_linda',
                'incident_type'   => 'incapacitation',
                'status'          => 'closed',
                'severity'        => 'critical',
                'reported_at'     => $now->copy()->subDays(3)->toDateTimeString(),
                'verified_at'     => $now->copy()->subDays(2)->subHours(20)->toDateTimeString(),
                'verified_by_id'  => 'cs_marcus',
                'activated_at'    => $now->copy()->subDays(2)->subHours(18)->toDateTimeString(),
                'closed_at'       => $now->copy()->subDays(1)->toDateTimeString(),
                'summary'         => 'Practitioner hospitalized following unexpected medical emergency. Short-term incapacitation confirmed by medical documentation. Continuity plan activated.',
                'created_at'      => $now->copy()->subDays(3)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'              => 'inc_david_reported',
                'plan_id'         => 'plan_david',
                'practitioner_id' => 'p_david',
                'reported_by_id'  => 'ss_james',
                'incident_type'   => 'missing',
                'status'          => 'reported',
                'severity'        => 'critical',
                'reported_at'     => $now->copy()->subHours(6)->toDateTimeString(),
                'verified_at'     => null,
                'verified_by_id'  => null,
                'activated_at'    => null,
                'closed_at'       => null,
                'summary'         => 'Practitioner has been unreachable for 48 hours. Support Steward unable to make contact via phone or email.',
                'created_at'      => $now->copy()->subHours(6)->toDateTimeString(),
                'updated_at'      => $now->copy()->subHours(6)->toDateTimeString(),
            ],
            [
                'id'              => 'inc_maria_verified',
                'plan_id'         => 'plan_maria',
                'practitioner_id' => 'p_maria',
                'reported_by_id'  => 'ss_james',
                'incident_type'   => 'incapacitation',
                'status'          => 'verified',
                'severity'        => 'critical',
                'reported_at'     => $now->copy()->subDays(1)->subHours(12)->toDateTimeString(),
                'verified_at'     => $now->copy()->subHours(8)->toDateTimeString(),
                'verified_by_id'  => 'cs_priya',
                'activated_at'    => null,
                'closed_at'       => null,
                'summary'         => 'Medical documentation received confirming practitioner incapacitation. Awaiting practitioner family consent before full plan activation.',
                'created_at'      => $now->copy()->subDays(1)->subHours(12)->toDateTimeString(),
                'updated_at'      => $now->copy()->subHours(8)->toDateTimeString(),
            ],
            [
                'id'              => 'inc_sarah_closed',
                'plan_id'         => 'plan_sarah',
                'practitioner_id' => 'p_sarah',
                'reported_by_id'  => 'ss_linda',
                'incident_type'   => 'extended_absence',
                'status'          => 'closed',
                'severity'        => 'warning',
                'reported_at'     => $now->copy()->subMonths(6)->subDays(10)->toDateTimeString(),
                'verified_at'     => $now->copy()->subMonths(6)->subDays(9)->toDateTimeString(),
                'verified_by_id'  => 'cs_marcus',
                'activated_at'    => $now->copy()->subMonths(6)->subDays(9)->toDateTimeString(),
                'closed_at'       => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString(),
                'summary'         => 'Practitioner took 3-week medical leave. Plan activated for duration. All clients successfully notified and temporary referrals established.',
                'created_at'      => $now->copy()->subMonths(6)->subDays(10)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString(),
            ],
        ];

        foreach ($incidents as $inc) {
            $inc['deleted_at'] = null;
            DB::table('critical_incidents')->updateOrInsert(['id' => $inc['id']], $inc);
        }

        $incidentTasks = [
            [
                'id'              => 'it_active_1',
                'incident_id'     => 'inc_sarah_active',
                'assigned_to_id'  => 'cs_marcus',
                'assigned_role'   => 'continuity_steward',
                'title'           => 'Contact all active clients to notify of temporary practice closure',
                'description'     => 'Reach out to each active client in the roster with a notification letter and referral options.',
                'status'          => 'complete',
                'timeline'        => 'Within 24 hours',
                'completed_at'    => $now->copy()->subDays(2)->subHours(5)->toDateTimeString(),
                'sort_order'      => 1,
                'created_at'      => $now->copy()->subDays(2)->subHours(18)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(2)->subHours(5)->toDateTimeString(),
            ],
            [
                'id'              => 'it_active_2',
                'incident_id'     => 'inc_sarah_active',
                'assigned_to_id'  => 'cs_marcus',
                'assigned_role'   => 'continuity_steward',
                'title'           => 'Coordinate emergency referrals to covering practitioners',
                'description'     => 'Match high-acuity clients with appropriate covering practitioners from the integrative network.',
                'status'          => 'in_progress',
                'timeline'        => 'Within 48 hours',
                'sort_order'      => 2,
                'created_at'      => $now->copy()->subDays(2)->subHours(18)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(1)->toDateTimeString(),
            ],
            [
                'id'              => 'it_active_3',
                'incident_id'     => 'inc_sarah_active',
                'assigned_to_id'  => 'cs_marcus',
                'assigned_role'   => 'continuity_steward',
                'title'           => 'Notify licensing board if absence exceeds 30 days',
                'description'     => 'File required regulatory notification with state licensing board.',
                'status'          => 'pending',
                'timeline'        => 'Day 30 of incident',
                'sort_order'      => 3,
                'created_at'      => $now->copy()->subDays(2)->subHours(18)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(2)->subHours(18)->toDateTimeString(),
            ],
            [
                'id'              => 'it_active_4',
                'incident_id'     => 'inc_sarah_active',
                'assigned_to_id'  => 'ss_linda',
                'assigned_role'   => 'support_steward',
                'title'           => 'Provide daily status updates to Continuity Steward',
                'description'     => 'Document and communicate any new information regarding practitioner status.',
                'status'          => 'in_progress',
                'timeline'        => 'Daily during active incident',
                'sort_order'      => 4,
                'created_at'      => $now->copy()->subDays(2)->subHours(18)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(1)->toDateTimeString(),
            ],
            [
                'id'               => 'it_active_5',
                'incident_id'      => 'inc_sarah_active',
                'assigned_to_id'   => 'cs_marcus',
                'assigned_role'    => 'continuity_steward',
                'title'            => 'Secure and transfer clinical records to designated custodian',
                'description'      => 'Coordinate secure transfer of clinical records per state regulations.',
                'status'           => 'exception',
                'timeline'         => 'Within 72 hours',
                'exception_reason' => 'Awaiting signed consent from practitioner\'s legal representative before records can be transferred.',
                'sort_order'       => 5,
                'created_at'       => $now->copy()->subDays(2)->subHours(18)->toDateTimeString(),
                'updated_at'       => $now->copy()->subDays(1)->toDateTimeString(),
            ],
        ];

        foreach ($incidentTasks as $t) {
            $t = array_merge(['completed_at' => null, 'exception_reason' => null], $t);
            DB::table('incident_tasks')->updateOrInsert(['id' => $t['id']], $t);
        }

        // incident_meta
        $meta = [
            ['id' => (string) Str::uuid(), 'incident_id' => 'inc_sarah_active',   'meta_key' => 'documentation_type', 'meta_value' => 'hospitalization_record', 'created_at' => $now->copy()->subDays(3)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(3)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'incident_id' => 'inc_sarah_active',   'meta_key' => 'report_narrative',   'meta_value' => 'Practitioner was admitted to St. David\'s Medical Center on the morning of the incident date.', 'created_at' => $now->copy()->subDays(3)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(3)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'incident_id' => 'inc_sarah_active',   'meta_key' => 'vault_unsealed_at',  'meta_value' => $now->copy()->subDays(2)->subHours(17)->toISOString(), 'created_at' => $now->copy()->subDays(2)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(2)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'incident_id' => 'inc_david_reported', 'meta_key' => 'documentation_type', 'meta_value' => 'police_report', 'created_at' => $now->copy()->subHours(6)->toDateTimeString(), 'updated_at' => $now->copy()->subHours(6)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'incident_id' => 'inc_david_reported', 'meta_key' => 'report_narrative',   'meta_value' => 'SS James Carter has attempted contact by phone (3 attempts), email, and emergency contact on file.', 'created_at' => $now->copy()->subHours(6)->toDateTimeString(), 'updated_at' => $now->copy()->subHours(6)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'incident_id' => 'inc_maria_verified', 'meta_key' => 'documentation_type', 'meta_value' => 'doctors_note', 'created_at' => $now->copy()->subHours(8)->toDateTimeString(), 'updated_at' => $now->copy()->subHours(8)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'incident_id' => 'inc_sarah_closed',   'meta_key' => 'resolution_notes',   'meta_value' => 'Practitioner returned to full practice capacity. All temporary referrals transitioned back.', 'created_at' => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString(), 'updated_at' => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString()],
        ];
        foreach ($meta as $m) {
            DB::table('incident_meta')->insert($m);
        }

        // incident_updates columns: id, incident_id, actor_id, update_type, message, created_at
        // Removed: author_id→actor_id, body→message
        // Added: update_type (NOT NULL enum)
        $updates = [
            [
                'id'          => (string) Str::uuid(),
                'incident_id' => 'inc_sarah_active',
                'actor_id'    => 'cs_marcus',
                'update_type' => 'activated',
                'message'     => 'Incident activated. Vault access granted. Beginning client notification process.',
                'created_at'  => $now->copy()->subDays(2)->subHours(18)->toDateTimeString(),
            ],
            [
                'id'          => (string) Str::uuid(),
                'incident_id' => 'inc_sarah_active',
                'actor_id'    => 'ss_linda',
                'update_type' => 'ss_notified',
                'message'     => 'Spoke with hospital staff. Practitioner is stable but will be incapacitated for approximately 2–3 weeks.',
                'created_at'  => $now->copy()->subDays(2)->subHours(10)->toDateTimeString(),
            ],
            [
                'id'          => (string) Str::uuid(),
                'incident_id' => 'inc_sarah_active',
                'actor_id'    => 'cs_marcus',
                'update_type' => 'task_added',
                'message'     => 'Client notification complete — 18 of 22 clients contacted directly. 4 clients via voicemail. Referral coordination in progress.',
                'created_at'  => $now->copy()->subDays(1)->subHours(15)->toDateTimeString(),
            ],
        ];
        foreach ($updates as $u) {
            DB::table('incident_updates')->insert($u);
        }
    }
}
