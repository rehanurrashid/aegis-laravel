<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Wipe old data for clean reseed
        DB::table('referral_meta')->whereIn('referral_id', [
            'ref_sarah_david_1','ref_sarah_maria_1','ref_david_sarah_1',
            'ref_maria_sarah_1','ref_sarah_access_1',
            // new
            'ref_david_sarah_2','ref_maria_sarah_2','ref_sarah_david_2',
            'ref_sarah_maria_2','ref_sarah_david_3','ref_access_sarah_1',
            'ref_sarah_maria_3','ref_david_sarah_3','ref_maria_sarah_old',
        ])->delete();
        DB::table('referrals')->whereIn('id', [
            'ref_sarah_david_1','ref_sarah_maria_1','ref_david_sarah_1',
            'ref_maria_sarah_1','ref_sarah_access_1',
            'ref_david_sarah_2','ref_maria_sarah_2','ref_sarah_david_2',
            'ref_sarah_david_3','ref_access_sarah_1','ref_sarah_maria_2',
            'ref_sarah_maria_3','ref_david_sarah_3','ref_maria_sarah_old',
        ])->delete();

        $referrals = [
            // ── PENDING TAB (received by Sarah, status=sent, awaiting her response) ──

            // Urgent — from David
            [
                'id'           => 'ref_david_sarah_2',
                'sender_id'    => 'p_david',
                'recipient_id' => 'p_sarah',
                'status'       => 'sent',
                'subject'      => 'Urgent — Adult Female, Acute Anxiety & Panic',
                'responded_at' => null,
                'closed_at'    => null,
                'created_at'   => $now->copy()->subHours(6),
                'updated_at'   => $now->copy()->subHours(6),
                'deleted_at'   => null,
            ],
            // Soon — from Maria
            [
                'id'           => 'ref_maria_sarah_2',
                'sender_id'    => 'p_maria',
                'recipient_id' => 'p_sarah',
                'status'       => 'sent',
                'subject'      => 'Adolescent Client — Self-Esteem & Identity',
                'responded_at' => null,
                'closed_at'    => null,
                'created_at'   => $now->copy()->subDays(1),
                'updated_at'   => $now->copy()->subDays(1),
                'deleted_at'   => null,
            ],
            // Routine — from David
            [
                'id'           => 'ref_david_sarah_3',
                'sender_id'    => 'p_david',
                'recipient_id' => 'p_sarah',
                'status'       => 'sent',
                'subject'      => 'Career & Life Transition Support',
                'responded_at' => null,
                'closed_at'    => null,
                'created_at'   => $now->copy()->subDays(2),
                'updated_at'   => $now->copy()->subDays(2),
                'deleted_at'   => null,
            ],

            // ── SENT TAB (sent by Sarah, various statuses) ──

            // Sent — awaiting response from David
            [
                'id'           => 'ref_sarah_david_1',
                'sender_id'    => 'p_sarah',
                'recipient_id' => 'p_david',
                'status'       => 'sent',
                'subject'      => 'Client Referral — Adult Male, Depression Focus',
                'responded_at' => null,
                'closed_at'    => null,
                'created_at'   => $now->copy()->subDays(3),
                'updated_at'   => $now->copy()->subDays(3),
                'deleted_at'   => null,
            ],
            // Sent — awaiting response from Maria (overdue-ish)
            [
                'id'           => 'ref_sarah_david_2',
                'sender_id'    => 'p_sarah',
                'recipient_id' => 'p_david',
                'status'       => 'sent',
                'subject'      => 'Grief Counseling Referral',
                'responded_at' => null,
                'closed_at'    => null,
                'created_at'   => $now->copy()->subDays(8),
                'updated_at'   => $now->copy()->subDays(8),
                'deleted_at'   => null,
            ],
            // Accepted — from Sarah to Maria
            [
                'id'           => 'ref_sarah_maria_1',
                'sender_id'    => 'p_sarah',
                'recipient_id' => 'p_maria',
                'status'       => 'accepted',
                'subject'      => 'Couples Referral — Conflict Resolution',
                'responded_at' => $now->copy()->subWeeks(2)->addDays(1),
                'closed_at'    => null,
                'created_at'   => $now->copy()->subWeeks(2),
                'updated_at'   => $now->copy()->subWeeks(2)->addDays(1),
                'deleted_at'   => null,
            ],
            // Accepted — from Sarah to Maria (second)
            [
                'id'           => 'ref_sarah_maria_2',
                'sender_id'    => 'p_sarah',
                'recipient_id' => 'p_maria',
                'status'       => 'accepted',
                'subject'      => 'Trauma-Informed CBT Client',
                'responded_at' => $now->copy()->subDays(5),
                'closed_at'    => null,
                'created_at'   => $now->copy()->subDays(7),
                'updated_at'   => $now->copy()->subDays(5),
                'deleted_at'   => null,
            ],
            // Declined — from Sarah to David
            [
                'id'           => 'ref_sarah_david_3',
                'sender_id'    => 'p_sarah',
                'recipient_id' => 'p_david',
                'status'       => 'declined',
                'subject'      => 'ADHD Adult Assessment Referral',
                'responded_at' => $now->copy()->subDays(4),
                'closed_at'    => null,
                'created_at'   => $now->copy()->subDays(6),
                'updated_at'   => $now->copy()->subDays(4),
                'deleted_at'   => null,
            ],

            // ── RECEIVED (declined) — Sarah declined David's earlier referral ──
            [
                'id'           => 'ref_david_sarah_1',
                'sender_id'    => 'p_david',
                'recipient_id' => 'p_sarah',
                'status'       => 'declined',
                'subject'      => 'Trauma Client Referral',
                'responded_at' => $now->copy()->subDays(10),
                'closed_at'    => null,
                'created_at'   => $now->copy()->subDays(12),
                'updated_at'   => $now->copy()->subDays(10),
                'deleted_at'   => null,
            ],

            // ── COMPLETED THIS MONTH (status=closed, closed_at this month) ──
            [
                'id'           => 'ref_maria_sarah_1',
                'sender_id'    => 'p_maria',
                'recipient_id' => 'p_sarah',
                'status'       => 'closed',
                'subject'      => 'Individual Therapy — Anxiety Focus',
                'responded_at' => $now->copy()->subDays(18),
                'closed_at'    => $now->copy()->subDays(3),
                'created_at'   => $now->copy()->subDays(22),
                'updated_at'   => $now->copy()->subDays(3),
                'deleted_at'   => null,
            ],
            [
                'id'           => 'ref_sarah_maria_3',
                'sender_id'    => 'p_sarah',
                'recipient_id' => 'p_maria',
                'status'       => 'closed',
                'subject'      => 'Postpartum Support Client',
                'responded_at' => $now->copy()->subDays(14),
                'closed_at'    => $now->copy()->subDays(2),
                'created_at'   => $now->copy()->subDays(16),
                'updated_at'   => $now->copy()->subDays(2),
                'deleted_at'   => null,
            ],

            // ── ARCHIVE (closed/declined/cancelled, older) ──
            [
                'id'           => 'ref_sarah_access_1',
                'sender_id'    => 'p_sarah',
                'recipient_id' => 'p_access_only',
                'status'       => 'cancelled',
                'subject'      => 'Adolescent Referral — Self-Esteem',
                'responded_at' => null,
                'closed_at'    => null,
                'created_at'   => $now->copy()->subMonths(1),
                'updated_at'   => $now->copy()->subDays(15),
                'deleted_at'   => null,
            ],
            // Unanswered/expired — no responded_at
            [
                'id'           => 'ref_access_sarah_1',
                'sender_id'    => 'p_access_only',
                'recipient_id' => 'p_sarah',
                'status'       => 'declined',
                'subject'      => 'CBT Referral — OCD',
                'responded_at' => $now->copy()->subMonths(2)->addDays(5),
                'closed_at'    => null,
                'created_at'   => $now->copy()->subMonths(2),
                'updated_at'   => $now->copy()->subMonths(2)->addDays(5),
                'deleted_at'   => null,
            ],
            [
                'id'           => 'ref_maria_sarah_old',
                'sender_id'    => 'p_maria',
                'recipient_id' => 'p_sarah',
                'status'       => 'closed',
                'subject'      => 'Geriatric Client — Mild Cognitive Concerns',
                'responded_at' => $now->copy()->subMonths(3)->addDays(2),
                'closed_at'    => $now->copy()->subMonths(2)->subDays(5),
                'created_at'   => $now->copy()->subMonths(3),
                'updated_at'   => $now->copy()->subMonths(2)->subDays(5),
                'deleted_at'   => null,
            ],
        ];

        foreach ($referrals as $r) {
            DB::table('referrals')->updateOrInsert(['id' => $r['id']], $r);
        }

        // ── Meta rows ──
        $meta = [
            // Pending — urgent from David
            ['referral_id' => 'ref_david_sarah_2', 'meta_key' => 'client_initials',  'meta_value' => 'R.T.'],
            ['referral_id' => 'ref_david_sarah_2', 'meta_key' => 'client_age_range', 'meta_value' => '28-35'],
            ['referral_id' => 'ref_david_sarah_2', 'meta_key' => 'presenting_issue', 'meta_value' => 'Panic disorder, agoraphobia onset'],
            ['referral_id' => 'ref_david_sarah_2', 'meta_key' => 'urgency',          'meta_value' => 'urgent'],
            ['referral_id' => 'ref_david_sarah_2', 'meta_key' => 'notes',            'meta_value' => 'Client had ER visit last week. Needs immediate intake. Prefers female therapist.'],

            // Pending — soon from Maria
            ['referral_id' => 'ref_maria_sarah_2', 'meta_key' => 'client_initials',  'meta_value' => 'K.A.'],
            ['referral_id' => 'ref_maria_sarah_2', 'meta_key' => 'client_age_range', 'meta_value' => '16-19'],
            ['referral_id' => 'ref_maria_sarah_2', 'meta_key' => 'presenting_issue', 'meta_value' => 'Adolescent identity & self-esteem, family conflict'],
            ['referral_id' => 'ref_maria_sarah_2', 'meta_key' => 'urgency',          'meta_value' => 'soon'],
            ['referral_id' => 'ref_maria_sarah_2', 'meta_key' => 'notes',            'meta_value' => 'Parental consent obtained. Bilingual (Spanish/English) preferred.'],

            // Pending — routine from David
            ['referral_id' => 'ref_david_sarah_3', 'meta_key' => 'client_initials',  'meta_value' => 'M.F.'],
            ['referral_id' => 'ref_david_sarah_3', 'meta_key' => 'client_age_range', 'meta_value' => '40-50'],
            ['referral_id' => 'ref_david_sarah_3', 'meta_key' => 'presenting_issue', 'meta_value' => 'Career burnout, life transition coaching'],
            ['referral_id' => 'ref_david_sarah_3', 'meta_key' => 'urgency',          'meta_value' => 'routine'],

            // Sent — awaiting David
            ['referral_id' => 'ref_sarah_david_1', 'meta_key' => 'client_initials',  'meta_value' => 'J.M.'],
            ['referral_id' => 'ref_sarah_david_1', 'meta_key' => 'client_age_range', 'meta_value' => '30-40'],
            ['referral_id' => 'ref_sarah_david_1', 'meta_key' => 'presenting_issue', 'meta_value' => 'Major depressive disorder, history of trauma'],
            ['referral_id' => 'ref_sarah_david_1', 'meta_key' => 'urgency',          'meta_value' => 'soon'],
            ['referral_id' => 'ref_sarah_david_1', 'meta_key' => 'notes',            'meta_value' => 'Client prefers morning appointments. No medication currently.'],

            // Sent — overdue David
            ['referral_id' => 'ref_sarah_david_2', 'meta_key' => 'client_initials',  'meta_value' => 'L.B.'],
            ['referral_id' => 'ref_sarah_david_2', 'meta_key' => 'client_age_range', 'meta_value' => '55-65'],
            ['referral_id' => 'ref_sarah_david_2', 'meta_key' => 'presenting_issue', 'meta_value' => 'Complicated grief, spousal loss'],
            ['referral_id' => 'ref_sarah_david_2', 'meta_key' => 'urgency',          'meta_value' => 'routine'],

            // Sent — accepted Maria
            ['referral_id' => 'ref_sarah_maria_1', 'meta_key' => 'client_initials',  'meta_value' => 'P.H.'],
            ['referral_id' => 'ref_sarah_maria_1', 'meta_key' => 'client_age_range', 'meta_value' => '35-45'],
            ['referral_id' => 'ref_sarah_maria_1', 'meta_key' => 'presenting_issue', 'meta_value' => 'Couples conflict, communication breakdown'],
            ['referral_id' => 'ref_sarah_maria_1', 'meta_key' => 'urgency',          'meta_value' => 'routine'],

            // Sent — accepted Maria #2
            ['referral_id' => 'ref_sarah_maria_2', 'meta_key' => 'client_initials',  'meta_value' => 'A.N.'],
            ['referral_id' => 'ref_sarah_maria_2', 'meta_key' => 'client_age_range', 'meta_value' => '25-35'],
            ['referral_id' => 'ref_sarah_maria_2', 'meta_key' => 'presenting_issue', 'meta_value' => 'Complex trauma, CBT focus'],
            ['referral_id' => 'ref_sarah_maria_2', 'meta_key' => 'urgency',          'meta_value' => 'soon'],

            // Sent — declined David
            ['referral_id' => 'ref_sarah_david_3', 'meta_key' => 'client_initials',  'meta_value' => 'T.W.'],
            ['referral_id' => 'ref_sarah_david_3', 'meta_key' => 'client_age_range', 'meta_value' => '30-40'],
            ['referral_id' => 'ref_sarah_david_3', 'meta_key' => 'presenting_issue', 'meta_value' => 'Adult ADHD evaluation & treatment'],
            ['referral_id' => 'ref_sarah_david_3', 'meta_key' => 'urgency',          'meta_value' => 'routine'],
            ['referral_id' => 'ref_sarah_david_3', 'meta_key' => 'decline_reason',   'meta_value' => 'Not accepting new clients at this time.'],

            // Declined David→Sarah
            ['referral_id' => 'ref_david_sarah_1', 'meta_key' => 'client_initials',  'meta_value' => 'C.V.'],
            ['referral_id' => 'ref_david_sarah_1', 'meta_key' => 'client_age_range', 'meta_value' => '22-28'],
            ['referral_id' => 'ref_david_sarah_1', 'meta_key' => 'presenting_issue', 'meta_value' => 'Complex PTSD, multiple trauma events'],
            ['referral_id' => 'ref_david_sarah_1', 'meta_key' => 'urgency',          'meta_value' => 'urgent'],
            ['referral_id' => 'ref_david_sarah_1', 'meta_key' => 'decline_reason',   'meta_value' => 'Specialty mismatch — outside scope of practice.'],

            // Completed this month — Maria→Sarah
            ['referral_id' => 'ref_maria_sarah_1', 'meta_key' => 'client_initials',  'meta_value' => 'O.P.'],
            ['referral_id' => 'ref_maria_sarah_1', 'meta_key' => 'client_age_range', 'meta_value' => '38-45'],
            ['referral_id' => 'ref_maria_sarah_1', 'meta_key' => 'presenting_issue', 'meta_value' => 'Generalized anxiety disorder'],
            ['referral_id' => 'ref_maria_sarah_1', 'meta_key' => 'urgency',          'meta_value' => 'soon'],

            // Completed this month — Sarah→Maria
            ['referral_id' => 'ref_sarah_maria_3', 'meta_key' => 'client_initials',  'meta_value' => 'B.R.'],
            ['referral_id' => 'ref_sarah_maria_3', 'meta_key' => 'client_age_range', 'meta_value' => '28-34'],
            ['referral_id' => 'ref_sarah_maria_3', 'meta_key' => 'presenting_issue', 'meta_value' => 'Postpartum depression & anxiety'],
            ['referral_id' => 'ref_sarah_maria_3', 'meta_key' => 'urgency',          'meta_value' => 'urgent'],

            // Archive — cancelled
            ['referral_id' => 'ref_sarah_access_1', 'meta_key' => 'client_initials',  'meta_value' => 'D.K.'],
            ['referral_id' => 'ref_sarah_access_1', 'meta_key' => 'client_age_range', 'meta_value' => '14-17'],
            ['referral_id' => 'ref_sarah_access_1', 'meta_key' => 'presenting_issue', 'meta_value' => 'Adolescent self-esteem, social anxiety'],
            ['referral_id' => 'ref_sarah_access_1', 'meta_key' => 'urgency',          'meta_value' => 'routine'],

            // Archive — declined old
            ['referral_id' => 'ref_access_sarah_1', 'meta_key' => 'client_initials',  'meta_value' => 'F.G.'],
            ['referral_id' => 'ref_access_sarah_1', 'meta_key' => 'client_age_range', 'meta_value' => '35-45'],
            ['referral_id' => 'ref_access_sarah_1', 'meta_key' => 'presenting_issue', 'meta_value' => 'OCD, contamination subtype'],
            ['referral_id' => 'ref_access_sarah_1', 'meta_key' => 'urgency',          'meta_value' => 'soon'],
            ['referral_id' => 'ref_access_sarah_1', 'meta_key' => 'decline_reason',   'meta_value' => 'Insurance not accepted.'],

            // Archive — closed old
            ['referral_id' => 'ref_maria_sarah_old', 'meta_key' => 'client_initials',  'meta_value' => 'E.Q.'],
            ['referral_id' => 'ref_maria_sarah_old', 'meta_key' => 'client_age_range', 'meta_value' => '70-80'],
            ['referral_id' => 'ref_maria_sarah_old', 'meta_key' => 'presenting_issue', 'meta_value' => 'Mild cognitive decline concerns, caregiver stress'],
            ['referral_id' => 'ref_maria_sarah_old', 'meta_key' => 'urgency',          'meta_value' => 'routine'],
        ];

        foreach ($meta as $m) {
            $m['id']         = (string) Str::uuid();
            $m['meta_type']  = 'string';
            $m['created_at'] = now();
            $m['updated_at'] = now();
            // use updateOrInsert keyed on referral_id + meta_key to avoid dupes on reseed
            DB::table('referral_meta')->updateOrInsert(
                ['referral_id' => $m['referral_id'], 'meta_key' => $m['meta_key']],
                $m
            );
        }
    }
}
