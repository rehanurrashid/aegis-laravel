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

            // p_sarah plan — alternate CS priya (archived — so sarah has 1 of 2, Add CS is open)
            [
                'id'               => 'ps_sarah_priya',
                'plan_id'          => 'plan_sarah',
                'steward_id'       => 'cs_priya',
                'role'             => 'alternate',
                'steward_category' => 'continuity_steward',
                'status'           => 'archived',
                'vault_access'     => 'none',
                'permissions'      => json_encode([]),
                'responsibilities' => json_encode([]),
                'signed_at'        => $now->copy()->subMonths(5),
                'review_due_at'    => null,
                'invited_at'       => $now->copy()->subMonths(6),
                'declined_at'      => $now->copy()->subDays(30),
                'declined_reason'  => 'Steward removed from plan.',
                'created_at'       => $now->copy()->subMonths(6),
                'updated_at'       => $now->copy()->subDays(30),
            ],

            // p_sarah plan — cs_alternate (archived invitation)
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
                'invited_at'       => $now->copy()->subDays(3),
                'expires_at'       => $now->copy()->addDays(27),
                'declined_at'      => null,
                'declined_reason'  => null,
                'created_at'       => $now->copy()->subDays(3),
                'updated_at'       => $now->copy()->subDays(3),
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

            // p_sarah plan — pending SS invite (ss_james, invited not yet accepted)
            [
                'id'               => 'ps_sarah_james_pending',
                'plan_id'          => 'plan_sarah',
                'steward_id'       => 'ss_james',
                'role'             => 'alternate',
                'steward_category' => 'support_steward',
                'status'           => 'pending',
                'vault_access'     => 'none',
                'permissions'      => json_encode([]),
                'responsibilities' => json_encode([]),
                'invited_at'       => $now->copy()->subDays(8),
                'expires_at'       => $now->copy()->addDays(22),
                'created_at'       => $now->copy()->subDays(8),
                'updated_at'       => $now->copy()->subDays(8),
            ],

            // p_sarah plan — suspended SS (archived + signed_at set)
            [
                'id'               => 'ps_sarah_ss_suspended',
                'plan_id'          => 'plan_sarah',
                'steward_id'       => 'ss_linda',   // using linda as a second entry (suspended)
                'role'             => 'support',
                'steward_category' => 'support_steward',
                'status'           => 'archived',
                'vault_access'     => 'none',
                'permissions'      => json_encode([]),
                'responsibilities' => json_encode(['Track practice expenses during the transition']),
                'signed_at'        => $now->copy()->subMonths(4),
                'declined_reason'  => 'Temporary medical leave — access paused until return.',
                'invited_at'       => $now->copy()->subMonths(5),
                'created_at'       => $now->copy()->subMonths(5),
                'updated_at'       => $now->copy()->subDays(14),
            ],

            // p_sarah plan — declined SS external invite
            [
                'id'               => 'ps_sarah_ss_declined',
                'plan_id'          => 'plan_sarah',
                'steward_id'       => 'ss_james',   // reuse as declined (different record)
                'role'             => 'support',
                'steward_category' => 'support_steward',
                'status'           => 'declined',
                'vault_access'     => 'none',
                'permissions'      => json_encode([]),
                'responsibilities' => json_encode([]),
                'invited_at'       => $now->copy()->subDays(20),
                'declined_at'      => $now->copy()->subDays(12),
                'declined_reason'  => 'Unable to take on additional responsibilities at this time.',
                'created_at'       => $now->copy()->subDays(20),
                'updated_at'       => $now->copy()->subDays(12),
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

            // p_maria plan — primary CS p_sarah (so p_sarah sees "I'm CS For" tab with rich data)
            [
                'id'               => 'ps_maria_sarah',
                'plan_id'          => 'plan_maria',
                'steward_id'       => 'p_sarah',
                'role'             => 'primary',
                'steward_category' => 'continuity_steward',
                'status'           => 'active',
                'vault_access'     => 'full',
                'permissions'      => json_encode(['view_plan', 'manage_incident', 'access_vault', 'issue_documents']),
                'responsibilities' => json_encode([
                    'Activate continuity plan upon verified incident',
                    'Coordinate client notifications',
                    'Manage vault access during active incidents',
                ]),
                'signed_at'        => $now->copy()->subMonths(8),
                'review_due_at'    => $now->copy()->addDays(25), // upcoming review — shows in Next Review Due chip
                'invited_at'       => $now->copy()->subMonths(9),
                'created_at'       => $now->copy()->subMonths(9),
                'updated_at'       => $now->copy()->subMonths(8),
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

            // p_david plan — p_sarah as primary SS (so sarah sees "I'm SS For" tab)
            [
                'id'               => 'ps_david_ss_sarah',
                'plan_id'          => 'plan_david',
                'steward_id'       => 'p_sarah',
                'steward_category' => 'support_steward',
                'role'             => 'primary',
                'status'           => 'active',
                'vault_access'     => 'none',
                'permissions'      => json_encode([]),
                'signed_at'        => $now->copy()->subMonths(3),
                'review_due_at'    => $now->copy()->addMonths(9),
                'invited_at'       => $now->copy()->subMonths(3)->subDays(5),
                'created_at'       => $now->copy()->subMonths(3),
                'updated_at'       => $now->copy()->subMonths(3),
            ],

            // p_sarah plan — suspended CS priya (archived + declined_reason = suspended)
            [
                'id'               => 'ps_sarah_cs_suspended',
                'plan_id'          => 'plan_sarah',
                'steward_id'       => 'cs_priya',
                'role'             => 'alternate',
                'steward_category' => 'continuity_steward',
                'status'           => 'archived',
                'vault_access'     => 'none',
                'permissions'      => json_encode([]),
                'responsibilities' => json_encode([]),
                'signed_at'        => $now->copy()->subMonths(5),
                'declined_reason'  => 'On extended medical leave — access paused until return.',
                'fee_cents'        => 50000,
                'payment_terms'    => 'on_close',
                'invited_at'       => $now->copy()->subMonths(6),
                'created_at'       => $now->copy()->subMonths(5),
                'updated_at'       => $now->copy()->subMonths(1),
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
