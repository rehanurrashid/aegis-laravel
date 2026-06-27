<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NetworkSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $connections = [
            ['id' => 'nc_sarah_maria', 'user_id' => 'p_sarah', 'connected_user_id' => 'p_maria',   'connection_type' => 'practitioner',    'status' => 'active',   'connected_at' => $now->copy()->subMonths(5)->toDateTimeString()],
            ['id' => 'nc_sarah_david', 'user_id' => 'p_sarah', 'connected_user_id' => 'p_david',   'connection_type' => 'practitioner',    'status' => 'active',   'connected_at' => $now->copy()->subMonths(3)->toDateTimeString()],
            ['id' => 'nc_sarah_acme',  'user_id' => 'p_sarah', 'connected_user_id' => 'bp_acme',   'connection_type' => 'business_partner', 'status' => 'active',  'connected_at' => $now->copy()->subMonths(4)->toDateTimeString()],
            ['id' => 'nc_sarah_jamal', 'user_id' => 'p_sarah', 'connected_user_id' => 'bp_jamal',  'connection_type' => 'business_partner', 'status' => 'active',  'connected_at' => $now->copy()->subMonths(2)->toDateTimeString()],
            ['id' => 'nc_maria_acme',  'user_id' => 'p_maria', 'connected_user_id' => 'bp_acme',   'connection_type' => 'business_partner', 'status' => 'active',  'connected_at' => $now->copy()->subMonths(6)->toDateTimeString()],
            ['id' => 'nc_david_jamal', 'user_id' => 'p_david', 'connected_user_id' => 'bp_jamal',  'connection_type' => 'business_partner', 'status' => 'archived','connected_at' => $now->copy()->subMonths(5)->toDateTimeString()],
        ];

        foreach ($connections as $c) {
            $c = array_merge([
                'deleted_at' => null,
                'created_at' => $c['connected_at'],
                'updated_at' => $c['connected_at'],
            ], $c);
            DB::table('network_connections')->updateOrInsert(['id' => $c['id']], $c);
        }

        $requests = [
            [
                'id'           => 'nr_access_only_sarah',
                'requester_id' => 'p_access_only',
                'recipient_id' => 'p_sarah',
                'status'       => 'pending',
                'message'      => 'Hi Sarah, I came across your profile and would love to connect.',
                'invite_token' => 'tok_' . Str::random(24),
                'created_at'   => $now->copy()->subDays(2)->toDateTimeString(),
                'updated_at'   => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_sarah_maria_orig',
                'requester_id' => 'p_sarah',
                'recipient_id' => 'p_maria',
                'status'       => 'accepted',
                'message'      => 'Hi Maria, would love to build a referral relationship given our complementary specialties.',
                'invite_token' => 'tok_' . Str::random(24),
                'responded_at' => $now->copy()->subMonths(5)->toDateTimeString(),
                'created_at'   => $now->copy()->subMonths(5)->subDays(2)->toDateTimeString(),
                'updated_at'   => $now->copy()->subMonths(5)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_david_access',
                'requester_id' => 'p_david',
                'recipient_id' => 'p_access_only',
                'status'       => 'declined',
                'message'      => 'Hi Jordan, reaching out to connect.',
                'invite_token' => 'tok_' . Str::random(24),
                'responded_at' => $now->copy()->subDays(5)->toDateTimeString(),
                'created_at'   => $now->copy()->subWeeks(1)->toDateTimeString(),
                'updated_at'   => $now->copy()->subDays(5)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_jamal_sarah',
                'requester_id' => 'bp_jamal',
                'recipient_id' => 'p_sarah',
                'status'       => 'cancelled',
                'message'      => 'Would love to discuss billing services.',
                'invite_token' => 'tok_' . Str::random(24),
                'created_at'   => $now->copy()->subMonths(3)->toDateTimeString(),
                'updated_at'   => $now->copy()->subMonths(2)->subDays(10)->toDateTimeString(),
            ],
        ];

        foreach ($requests as $r) {
            $r = array_merge(['recipient_email' => null, 'responded_at' => null], $r);
            DB::table('network_requests')->updateOrInsert(['id' => $r['id']], $r);
        }

        // shadow_connections columns: id, user_id, shadow_user_id, shadow_name, source, created_at, deleted_at
        // Removed: practitioner_id→user_id, relationship (doesn't exist)
        $shadows = [
            ['id' => (string) Str::uuid(), 'user_id' => 'p_sarah', 'shadow_user_id' => 'cs_marcus', 'source' => 'continuity_steward', 'created_at' => $now->copy()->subMonths(7)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'user_id' => 'p_sarah', 'shadow_user_id' => 'ss_linda',  'source' => 'support_steward',    'created_at' => $now->copy()->subMonths(7)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'user_id' => 'p_maria', 'shadow_user_id' => 'cs_priya',  'source' => 'continuity_steward', 'created_at' => $now->copy()->subMonths(13)->toDateTimeString()],
        ];
        foreach ($shadows as $s) {
            DB::table('shadow_connections')->insert($s);
        }
    }
}
