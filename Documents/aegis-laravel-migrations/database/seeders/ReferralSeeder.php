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

        $referrals = [
            // status=sent — waiting
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
            ],
            // status=accepted
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
            ],
            // status=declined
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
            ],
            // status=closed
            [
                'id'           => 'ref_maria_sarah_1',
                'sender_id'    => 'p_maria',
                'recipient_id' => 'p_sarah',
                'status'       => 'closed',
                'subject'      => 'Individual Therapy — Anxiety Focus',
                'responded_at' => $now->copy()->subMonths(2)->addDays(2),
                'closed_at'    => $now->copy()->subMonths(1),
                'created_at'   => $now->copy()->subMonths(2),
                'updated_at'   => $now->copy()->subMonths(1),
            ],
            // status=cancelled
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
            ],
        ];

        foreach ($referrals as $r) {
            $r['deleted_at'] = null;
            DB::table('referrals')->updateOrInsert(['id' => $r['id']], $r);
        }

        // Referral meta
        $refMeta = [
            ['id' => (string) Str::uuid(), 'referral_id' => 'ref_sarah_david_1',  'meta_key' => 'client_age_range', 'meta_value' => '30-40'],
            ['id' => (string) Str::uuid(), 'referral_id' => 'ref_sarah_david_1',  'meta_key' => 'presenting_issue', 'meta_value' => 'Major depressive disorder, history of trauma'],
            ['id' => (string) Str::uuid(), 'referral_id' => 'ref_sarah_maria_1',  'meta_key' => 'client_age_range', 'meta_value' => '35-45'],
            ['id' => (string) Str::uuid(), 'referral_id' => 'ref_sarah_maria_1',  'meta_key' => 'presenting_issue', 'meta_value' => 'Couples conflict, communication breakdown'],
        ];
        foreach ($refMeta as $m) {
            DB::table('referral_meta')->insert($m);
        }
    }
}
