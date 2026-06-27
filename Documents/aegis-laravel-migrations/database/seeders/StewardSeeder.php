<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StewardSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $stewards = [
            // ── p_sarah plan — primary CS marcus ──────────────────────────
            [
                'id'               => 'ps_sarah_marcus',
                'plan_id'          => 'plan_sarah',
                'steward_id'       => 'cs_marcus',
                'role'             => 'primary',
                'steward_category' => 'continuity_steward',
                'status'           => 'active',
                'vault_access'     => 'full',
                'permissions'      => json_encode(['view_plan', 'manage_incident', 'access_vault', 'issue_documents']),
                'responsibilities' => json_encode([
                    'Activate continuity plan upon verified incident',
                    'Coordinate client notifications',
                    'Manage vault access during active incidents',
                    'Oversee handoff to covering practitioners',
                ]),
                'signed_at'        => $now->copy()->subMonths(6),
                'review_due_at'    => $now->copy()->addMonths(6),
                'invited_at'       => $now->copy()->subMonths(7),
                'created_at'       => $now->copy()->subMonths(7),
                'updated_at'       => $now->copy()->subMonths(6),
            ],

            // p_sarah plan — alternate CS priya
            [
                'id'               => 'ps_sarah_priya',
                'plan_id'          => 'plan_sarah',
                'steward_id'       => 'cs_priya',
                'role'             => 'alternate',
                'steward_category' => 'continuity_steward',
                'status'           => 'active',
                'vault_access'     => 'scoped',
                'permissions'      => json_encode(['view_plan', 'manage_incident', 'access_vault']),
                'responsibilities' => json_encode([
                    'Serve as backup CS when primary is unavailable',
                    'Maintain awareness of plan status',
                ]),
                'signed_at'        => $now->copy()->subMonths(5),
                'review_due_at'    => $now->copy()->addMonths(7),
                'invited_at'       => $now->copy()->subMonths(6),
                'created_at'       => $now->copy()->subMonths(6),
                'updated_at'       => $now->copy()->subMonths(5),
            ],

            // p_sarah plan — invited CS (not yet accepted)
            [
                'id'               => 'ps_sarah_alternate',
                'plan_id'          => 'plan_sarah',
                'steward_id'       => 'cs_alternate',
                'role'             => 'alternate',
                'steward_category' => 'continuity_steward',
                'status'           => 'invited',
                'vault_access'     => 'none',
                'permissions'      => json_encode(['view_plan']),
                'responsibilities' => null,
                'signed_at'        => null,
                'invited_at'       => $now->copy()->subDays(10),
                'created_at'       => $now->copy()->subDays(10),
                'updated_at'       => $now->copy()->subDays(10),
            ],

            // p_sarah plan — support steward linda
            [
                'id'               => 'ps_sarah_linda',
                'plan_id'          => 'plan_sarah',
                'steward_id'       => 'ss_linda',
                'role'             => 'support',
                'steward_category' => 'support_steward',
                'status'           => 'active',
                'vault_access'     => 'none',
                'permissions'      => json_encode(['report_incident', 'log_checkin', 'view_plan_summary']),
                'responsibilities' => json_encode([
                    'Monitor practitioner wellbeing',
                    'Report critical incidents when observed',
                    'Conduct regular check-ins',
                ]),
                'signed_at'        => null,
                'ss_acknowledged_at' => $now->copy()->subMonths(6),
                'invited_at'       => $now->copy()->subMonths(7),
                'created_at'       => $now->copy()->subMonths(7),
                'updated_at'       => $now->copy()->subMonths(6),
            ],

            // p_sarah plan — resigned CS (edge case UC-CS-093)
            [
                'id'               => 'ps_sarah_resigned',
                'plan_id'          => 'plan_sarah',
                'steward_id'       => 'cs_resigned',
                'role'             => 'alternate',
                'steward_category' => 'continuity_steward',
                'status'           => 'archived',
                'vault_access'     => 'none',
                'permissions'      => json_encode([]),
                'signed_at'        => $now->copy()->subMonths(10),
                'declined_at'      => $now->copy()->subDays(14),
                'declined_reason'  => 'Practitioner workload no longer permits continuity steward responsibilities.',
                'invited_at'       => $now->copy()->subMonths(11),
                'created_at'       => $now->copy()->subMonths(11),
                'updated_at'       => $now->copy()->subDays(14),
            ],

            // ── p_maria plan — primary CS priya ───────────────────────────
            [
                'id'               => 'ps_maria_priya',
                'plan_id'          => 'plan_maria',
                'steward_id'       => 'cs_priya',
                'role'             => 'primary',
                'steward_category' => 'continuity_steward',
                'status'           => 'active',
                'vault_access'     => 'full',
                'permissions'      => json_encode(['view_plan', 'manage_incident', 'access_vault', 'issue_documents']),
                'responsibilities' => json_encode([
                    'Primary continuity response for Maria Santos practice',
                    'Annual plan review coordination',
                ]),
                'signed_at'        => $now->copy()->subMonths(13),
                'review_due_at'    => $now->copy()->subMonths(1), // OVERDUE
                'invited_at'       => $now->copy()->subMonths(14),
                'created_at'       => $now->copy()->subMonths(14),
                'updated_at'       => $now->copy()->subMonths(13),
            ],

            // p_maria plan — support steward james
            [
                'id'               => 'ps_maria_james',
                'plan_id'          => 'plan_maria',
                'steward_id'       => 'ss_james',
                'role'             => 'support',
                'steward_category' => 'support_steward',
                'status'           => 'active',
                'vault_access'     => 'none',
                'permissions'      => json_encode(['report_incident', 'log_checkin']),
                'responsibilities' => json_encode(['Monitor practitioner wellbeing']),
                'ss_acknowledged_at' => $now->copy()->subMonths(13),
                'invited_at'       => $now->copy()->subMonths(14),
                'created_at'       => $now->copy()->subMonths(14),
                'updated_at'       => $now->copy()->subMonths(13),
            ],

            // ── p_david plan — no primary CS yet, pending request to cs_marcus
            [
                'id'               => 'ps_david_marcus',
                'plan_id'          => 'plan_david',
                'steward_id'       => 'cs_marcus',
                'role'             => 'primary',
                'steward_category' => 'continuity_steward',
                'status'           => 'request_incoming',
                'vault_access'     => 'none',
                'permissions'      => json_encode([]),
                'request_sent_at'  => $now->copy()->subDays(3),
                'invited_at'       => null,
                'created_at'       => $now->copy()->subDays(3),
                'updated_at'       => $now->copy()->subDays(3),
            ],
        ];

        foreach ($stewards as $s) {
            $s = array_merge([
                'permissions'      => null,
                'responsibilities' => null,
                'signed_at'        => null,
                'review_due_at'    => null,
                'invited_at'       => null,
                'request_sent_at'  => null,
                'expires_at'       => null,
                'declined_at'      => null,
                'declined_reason'  => null,
                'ss_acknowledged_at' => null,
                'deleted_at'       => null,
            ], $s);

            DB::table('plan_stewards')->updateOrInsert(['id' => $s['id']], $s);
        }
    }
}
