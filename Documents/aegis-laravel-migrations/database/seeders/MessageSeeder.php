<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $threads = [
            [
                'id'              => 'mt_sarah_marcus',
                'subject'         => 'Active Incident — Status Update',
                'created_by_id'   => 'cs_marcus',
                'last_message_at' => $now->copy()->subHours(4),
                'is_pinned'       => 1,
                'is_muted'        => 0,
                'created_at'      => $now->copy()->subDays(3),
                'updated_at'      => $now->copy()->subHours(4),
            ],
            [
                'id'              => 'mt_sarah_priya',
                'subject'         => 'Re: Alternate Steward Coverage',
                'created_by_id'   => 'p_sarah',
                'last_message_at' => $now->copy()->subDays(7),
                'is_pinned'       => 0,
                'is_muted'        => 0,
                'created_at'      => $now->copy()->subDays(8),
                'updated_at'      => $now->copy()->subDays(7),
            ],
            [
                'id'              => 'mt_sarah_acme',
                'subject'         => 'Billing Services Proposal — Q3',
                'created_by_id'   => 'bp_acme',
                'last_message_at' => $now->copy()->subWeeks(2),
                'is_pinned'       => 0,
                'is_muted'        => 0,
                'created_at'      => $now->copy()->subWeeks(3),
                'updated_at'      => $now->copy()->subWeeks(2),
            ],
            [
                'id'              => 'mt_maria_priya',
                'subject'         => 'Annual Review — Documentation Needed',
                'created_by_id'   => 'cs_priya',
                'last_message_at' => $now->copy()->subDays(4),
                'is_pinned'       => 1,
                'is_muted'        => 0,
                'created_at'      => $now->copy()->subDays(5),
                'updated_at'      => $now->copy()->subDays(4),
            ],
        ];

        foreach ($threads as $t) {
            $t['deleted_at'] = null;
            DB::table('message_threads')->updateOrInsert(['id' => $t['id']], $t);
        }

        // Thread participants
        $participants = [
            ['thread_id' => 'mt_sarah_marcus', 'user_id' => 'p_sarah'],
            ['thread_id' => 'mt_sarah_marcus', 'user_id' => 'cs_marcus'],
            ['thread_id' => 'mt_sarah_priya',  'user_id' => 'p_sarah'],
            ['thread_id' => 'mt_sarah_priya',  'user_id' => 'cs_priya'],
            ['thread_id' => 'mt_sarah_acme',   'user_id' => 'p_sarah'],
            ['thread_id' => 'mt_sarah_acme',   'user_id' => 'bp_acme'],
            ['thread_id' => 'mt_maria_priya',  'user_id' => 'p_maria'],
            ['thread_id' => 'mt_maria_priya',  'user_id' => 'cs_priya'],
        ];
        // Insert into thread_participants if table exists, else skip (handled by message threads model)
        // Some implementations store participants in messages table itself
        // Seeding messages directly covers the relationship:

        $messages = [
            // mt_sarah_marcus — active incident thread
            [
                'id'         => (string) Str::uuid(),
                'thread_id'  => 'mt_sarah_marcus',
                'sender_id'  => 'cs_marcus',
                'body'       => 'Sarah, I\'ve activated the continuity plan and have begun client notifications. Vault access is live. I\'ll send updates every 12 hours.',
                'read_at'    => null,
                'created_at' => $now->copy()->subDays(2)->subHours(17),
                'updated_at' => $now->copy()->subDays(2)->subHours(17),
            ],
            [
                'id'         => (string) Str::uuid(),
                'thread_id'  => 'mt_sarah_marcus',
                'sender_id'  => 'ss_linda',
                'body'       => 'Marcus — confirmed with hospital. Sarah is stable. Expected discharge in 10–14 days. I\'ll maintain daily check-ins.',
                'read_at'    => $now->copy()->subDays(1)->subHours(12),
                'created_at' => $now->copy()->subDays(2)->subHours(10),
                'updated_at' => $now->copy()->subDays(2)->subHours(10),
            ],
            [
                'id'         => (string) Str::uuid(),
                'thread_id'  => 'mt_sarah_marcus',
                'sender_id'  => 'cs_marcus',
                'body'       => '18 of 22 clients notified. 3 high-acuity clients matched to covering practitioners. Waiting on 1 client callback. Records hold in place.',
                'read_at'    => null,
                'created_at' => $now->copy()->subHours(4),
                'updated_at' => $now->copy()->subHours(4),
            ],

            // mt_sarah_priya
            [
                'id'         => (string) Str::uuid(),
                'thread_id'  => 'mt_sarah_priya',
                'sender_id'  => 'p_sarah',
                'body'       => 'Priya, I\'d like to discuss the alternate steward arrangement for my plan. Can we schedule a call?',
                'read_at'    => $now->copy()->subDays(7),
                'created_at' => $now->copy()->subDays(8),
                'updated_at' => $now->copy()->subDays(8),
            ],
            [
                'id'         => (string) Str::uuid(),
                'thread_id'  => 'mt_sarah_priya',
                'sender_id'  => 'cs_priya',
                'body'       => 'Of course Sarah. I\'m available Tuesday or Thursday afternoon this week. Please let me know what works.',
                'read_at'    => $now->copy()->subDays(7),
                'created_at' => $now->copy()->subDays(7),
                'updated_at' => $now->copy()->subDays(7),
            ],

            // mt_maria_priya
            [
                'id'         => (string) Str::uuid(),
                'thread_id'  => 'mt_maria_priya',
                'sender_id'  => 'cs_priya',
                'body'       => 'Maria, your annual plan review is now overdue by 2 months. I need to schedule a review session and update the documentation. Please respond at your earliest convenience.',
                'read_at'    => null,
                'created_at' => $now->copy()->subDays(4),
                'updated_at' => $now->copy()->subDays(4),
            ],
        ];

        foreach ($messages as $m) {
            DB::table('messages')->insert($m);
        }
    }
}
