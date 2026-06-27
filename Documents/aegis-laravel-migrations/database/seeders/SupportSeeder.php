<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupportSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── Complaints (all 4 statuses) ────────────────────────────────────
        $complaints = [
            // status=open — from p_david, unassigned
            [
                'id'                 => 'comp_david_open',
                'submitter_id'       => 'p_david',
                'subject'            => 'Unable to send invitation to Continuity Steward',
                'body'               => 'I have been trying to invite a Continuity Steward to my plan for the past 3 days. Each time I submit the invitation form, I receive a generic error message and the invitation does not appear in my dashboard. I have tried 3 different browsers.',
                'category'           => 'support_ticket',
                'submission_channel' => 'ticket',
                'status'             => 'open',
                'priority'           => 'normal',
                'assigned_to'        => null,
                'escalated_at'       => null,
                'resolved_at'        => null,
                'created_at'         => $now->copy()->subDays(2),
                'updated_at'         => $now->copy()->subDays(2),
            ],
            // status=in_progress — from p_sarah, assigned to admin, with replies
            [
                'id'                 => 'comp_sarah_in_progress',
                'submitter_id'       => 'p_sarah',
                'subject'            => 'Vault item category not saving correctly',
                'body'               => 'When I edit a vault item and change its category, the change does not persist after saving. The item reverts to its original category when I refresh the page.',
                'category'           => 'support_ticket',
                'submission_channel' => 'ticket',
                'status'             => 'in_progress',
                'priority'           => 'high',
                'assigned_to'        => 'admin_root',
                'escalated_at'       => null,
                'resolved_at'        => null,
                'created_at'         => $now->copy()->subDays(5),
                'updated_at'         => $now->copy()->subDays(1),
            ],
            // status=resolved — from bp_jamal
            [
                'id'                 => 'comp_jamal_resolved',
                'submitter_id'       => 'bp_jamal',
                'subject'            => 'Question about invoice payment processing timeline',
                'body'               => 'How long does it typically take for payment to reach my connected Stripe account after a practitioner pays an invoice? I sent an invoice 5 days ago and the practitioner paid it but I haven\'t received the funds.',
                'category'           => 'support_ticket',
                'submission_channel' => 'ticket',
                'status'             => 'resolved',
                'priority'           => 'normal',
                'assigned_to'        => 'admin_root',
                'escalated_at'       => null,
                'resolved_at'        => $now->copy()->subDays(3),
                'created_at'         => $now->copy()->subDays(8),
                'updated_at'         => $now->copy()->subDays(3),
            ],
            // status=closed — old ticket from cs_marcus
            [
                'id'                 => 'comp_marcus_closed',
                'submitter_id'       => 'cs_marcus',
                'subject'            => 'Feedback: Dashboard task notifications could be more prominent',
                'body'               => 'Suggestion: When a new incident task is assigned to me, the notification in the dashboard feels easy to miss. A more prominent visual indicator (color badge, alert banner) would help ensure tasks are actioned quickly during critical incidents.',
                'category'           => 'feedback',
                'submission_channel' => 'ticket',
                'status'             => 'closed',
                'priority'           => 'low',
                'assigned_to'        => 'admin_root',
                'escalated_at'       => null,
                'resolved_at'        => $now->copy()->subMonths(1)->subDays(20),
                'created_at'         => $now->copy()->subMonths(2),
                'updated_at'         => $now->copy()->subMonths(1)->subDays(18),
            ],
            // feedback — button channel, praise category
            [
                'id'                 => 'comp_feedback_praise',
                'submitter_id'       => 'p_sarah',
                'subject'            => 'Quick Feedback',
                'body'               => 'The platform is really well designed. The vault section especially makes me feel confident that my practice is protected.',
                'category'           => 'feedback',
                'submission_channel' => 'button',
                'status'             => 'closed',
                'priority'           => 'low',
                'assigned_to'        => null,
                'escalated_at'       => null,
                'resolved_at'        => $now->copy()->subMonths(1),
                'created_at'         => $now->copy()->subMonths(1),
                'updated_at'         => $now->copy()->subMonths(1),
            ],
            // feedback — questionnaire channel, feature request
            [
                'id'                 => 'comp_feedback_feature',
                'submitter_id'       => 'cs_priya',
                'subject'            => 'Questionnaire Response',
                'body'               => 'Feature request: Would love to see a mobile app version of the CS dashboard for responding to incident notifications on the go.',
                'category'           => 'feedback',
                'submission_channel' => 'questionnaire',
                'status'             => 'open',
                'priority'           => 'low',
                'assigned_to'        => null,
                'escalated_at'       => null,
                'resolved_at'        => null,
                'created_at'         => $now->copy()->subDays(10),
                'updated_at'         => $now->copy()->subDays(10),
            ],
        ];

        foreach ($complaints as $c) {
            $c['deleted_at'] = null;
            DB::table('complaints')->updateOrInsert(['id' => $c['id']], $c);
        }

        // ── Complaint Replies ──────────────────────────────────────────────
        $replies = [
            // comp_sarah_in_progress — 2 public replies + 1 internal note
            [
                'id'          => (string) Str::uuid(),
                'complaint_id'=> 'comp_sarah_in_progress',
                'author_id'   => 'admin_root',
                'body'        => 'Thank you for reporting this, Sarah. We have been able to reproduce the issue in our test environment. It appears to be a caching issue with the vault item form. Our engineering team is investigating.',
                'is_internal' => 0,
                'created_at'  => $now->copy()->subDays(4),
            ],
            [
                'id'          => (string) Str::uuid(),
                'complaint_id'=> 'comp_sarah_in_progress',
                'author_id'   => 'admin_root',
                'body'        => '[INTERNAL NOTE] This appears related to the vault category enum mismatch bug from the last deploy. Assigning to engineering — should be resolved in the next patch.',
                'is_internal' => 1,
                'created_at'  => $now->copy()->subDays(4)->addHours(1),
            ],
            [
                'id'          => (string) Str::uuid(),
                'complaint_id'=> 'comp_sarah_in_progress',
                'author_id'   => 'admin_root',
                'body'        => 'Update: The fix has been deployed. Could you please try editing a vault item category again and let us know if the issue persists?',
                'is_internal' => 0,
                'created_at'  => $now->copy()->subDays(1),
            ],

            // comp_jamal_resolved — resolution reply
            [
                'id'          => (string) Str::uuid(),
                'complaint_id'=> 'comp_jamal_resolved',
                'author_id'   => 'admin_root',
                'body'        => 'Hi Jamal, payments are transferred to connected Stripe accounts within 2–5 business days of the payment being processed. The timeline depends on your Stripe account settings. For the invoice in question, the transfer should arrive by tomorrow. If it doesn\'t, please follow up and we\'ll investigate directly with Stripe. Marking this as resolved — let us know if you have further questions.',
                'is_internal' => 0,
                'created_at'  => $now->copy()->subDays(3),
            ],

            // comp_marcus_closed — response and close
            [
                'id'          => (string) Str::uuid(),
                'complaint_id'=> 'comp_marcus_closed',
                'author_id'   => 'admin_root',
                'body'        => 'Thank you for this feedback, Marcus. We\'ve logged this as a UX improvement request with our design team. A more prominent notification system for incident-related tasks is on our product roadmap for Q1 2025.',
                'is_internal' => 0,
                'created_at'  => $now->copy()->subMonths(1)->subDays(20),
            ],
        ];

        foreach ($replies as $r) {
            DB::table('complaint_replies')->insert($r);
        }

        // ── Complaint Meta ─────────────────────────────────────────────────
        DB::table('complaint_meta')->insert([
            'id' => (string) Str::uuid(), 'complaint_id' => 'comp_sarah_in_progress', 'meta_key' => 'browser', 'meta_value' => 'Chrome 119',
        ]);
        DB::table('complaint_meta')->insert([
            'id' => (string) Str::uuid(), 'complaint_id' => 'comp_sarah_in_progress', 'meta_key' => 'os', 'meta_value' => 'macOS 14.1',
        ]);

        // ── Help Articles (5 across 3 categories, mix published/unpublished) ──
        $articles = [
            ['id' => (string) Str::uuid(), 'category' => 'getting_started', 'title' => 'Setting Up Your Continuity Plan: A Step-by-Step Guide',        'body' => '<p>Your Continuity Plan is the foundation of your practice resilience on Aegis. This guide walks you through each section of the plan setup process...</p>', 'role_visibility' => 'practitioner', 'sort_order' => 1, 'published' => 1, 'created_at' => $now->copy()->subMonths(5)],
            ['id' => (string) Str::uuid(), 'category' => 'getting_started', 'title' => 'How to Invite a Continuity Steward',                               'body' => '<p>Inviting a Continuity Steward is one of the most important steps in activating your continuity plan. Here\'s how to find and invite a qualified steward...</p>', 'role_visibility' => 'practitioner', 'sort_order' => 2, 'published' => 1, 'created_at' => $now->copy()->subMonths(5)],
            ['id' => (string) Str::uuid(), 'category' => 'vault',           'title' => 'Understanding Vault Zones and Access Controls',                    'body' => '<p>The Aegis Vault is organized into four zones: Credentials, Roster, Documents, and Instructions. Each zone serves a distinct purpose...</p>', 'role_visibility' => 'all', 'sort_order' => 1, 'published' => 1, 'created_at' => $now->copy()->subMonths(4)],
            ['id' => (string) Str::uuid(), 'category' => 'billing',         'title' => 'How Aegis Payments and Payouts Work',                              'body' => '<p>Aegis uses Stripe Connect to process payments between practitioners and business partners or continuity stewards. This article explains the payment flow...</p>', 'role_visibility' => 'all', 'sort_order' => 1, 'published' => 1, 'created_at' => $now->copy()->subMonths(3)],
            ['id' => (string) Str::uuid(), 'category' => 'billing',         'title' => 'W-9 Verification for Business Partners — DRAFT',                   'body' => '<p>DRAFT — Content pending legal review...</p>', 'role_visibility' => 'business_partner', 'sort_order' => 2, 'published' => 0, 'created_at' => $now->copy()->subDays(7)],
        ];
        foreach ($articles as $a) {
            $a['updated_at'] = $a['created_at'];
            $a['deleted_at'] = null;
            DB::table('help_articles')->insert($a);
        }
    }
}
