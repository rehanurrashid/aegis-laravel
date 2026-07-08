<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserSessionSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Session data per demo user: realistic devices, IPs, times
        $sessionData = [
            // Practitioner: p_sarah — 3 sessions
            ['user_id' => 'p_sarah', 'device_label' => 'MacBook Pro — Chrome 124', 'ip_address' => '192.168.1.101', 'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/124.0', 'last_seen_at' => $now->copy()->subMinutes(3),  'created_at' => $now->copy()->subDays(14)],
            ['user_id' => 'p_sarah', 'device_label' => 'iPhone 15 Pro — Safari',   'ip_address' => '73.148.22.91',  'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Safari/604', 'last_seen_at' => $now->copy()->subHours(2),  'created_at' => $now->copy()->subDays(7)],
            ['user_id' => 'p_sarah', 'device_label' => 'iPad Air — Safari',         'ip_address' => '73.148.22.92',  'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 17_0) Safari/604',         'last_seen_at' => $now->copy()->subDays(3),   'created_at' => $now->copy()->subDays(10)],

            // Practitioner: p_david — 2 sessions
            ['user_id' => 'p_david', 'device_label' => 'Windows 11 — Chrome 124',  'ip_address' => '98.45.110.20',  'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0', 'last_seen_at' => $now->copy()->subMinutes(10), 'created_at' => $now->copy()->subDays(5)],
            ['user_id' => 'p_david', 'device_label' => 'Android — Chrome Mobile',   'ip_address' => '98.45.110.21',  'user_agent' => 'Mozilla/5.0 (Linux; Android 14) Chrome/124.0 Mobile', 'last_seen_at' => $now->copy()->subDays(1),   'created_at' => $now->copy()->subDays(6)],

            // Practitioner: p_maria — 2 sessions
            ['user_id' => 'p_maria', 'device_label' => 'MacBook Air — Safari',      'ip_address' => '104.28.5.77',   'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) Safari/604', 'last_seen_at' => $now->copy()->subMinutes(45), 'created_at' => $now->copy()->subDays(3)],
            ['user_id' => 'p_maria', 'device_label' => 'Windows 10 — Firefox 125',  'ip_address' => '104.28.5.78',   'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64) Firefox/125.0',       'last_seen_at' => $now->copy()->subDays(4),   'created_at' => $now->copy()->subDays(8)],

            // CS: cs_marcus — 2 sessions
            ['user_id' => 'cs_marcus', 'device_label' => 'MacBook Pro — Chrome 124', 'ip_address' => '67.150.99.44', 'user_agent' => 'Mozilla/5.0 (Macintosh) Chrome/124.0', 'last_seen_at' => $now->copy()->subMinutes(8), 'created_at' => $now->copy()->subDays(2)],
            ['user_id' => 'cs_marcus', 'device_label' => 'iPhone 14 — Safari',       'ip_address' => '67.150.99.45', 'user_agent' => 'Mozilla/5.0 (iPhone) Safari/604',     'last_seen_at' => $now->copy()->subHours(6),  'created_at' => $now->copy()->subDays(5)],

            // SS: ss_linda — 1 session
            ['user_id' => 'ss_linda', 'device_label' => 'Windows 11 — Edge 122',    'ip_address' => '71.202.55.10', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Edg/122.0', 'last_seen_at' => $now->copy()->subMinutes(20), 'created_at' => $now->copy()->subDays(1)],

            // BP: bp_acme — 2 sessions
            ['user_id' => 'bp_acme',  'device_label' => 'MacBook Pro — Chrome 124', 'ip_address' => '52.70.190.22', 'user_agent' => 'Mozilla/5.0 (Macintosh) Chrome/124.0', 'last_seen_at' => $now->copy()->subMinutes(5),  'created_at' => $now->copy()->subDays(4)],
            ['user_id' => 'bp_acme',  'device_label' => 'Linux — Firefox 125',      'ip_address' => '52.70.190.23', 'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) Firefox/125.0', 'last_seen_at' => $now->copy()->subHours(3), 'created_at' => $now->copy()->subDays(7)],

            // BP: bp_jamal — 1 session
            ['user_id' => 'bp_jamal', 'device_label' => 'Windows 10 — Chrome 124', 'ip_address' => '88.99.14.5', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/124.0', 'last_seen_at' => $now->copy()->subMinutes(15), 'created_at' => $now->copy()->subDays(2)],
        ];

        foreach ($sessionData as $row) {
            // Skip if user doesn't exist (demo env may differ)
            if (!DB::table('users')->where('id', $row['user_id'])->exists()) {
                continue;
            }

            DB::table('user_sessions')->updateOrInsert(
                [
                    'user_id'     => $row['user_id'],
                    'device_label' => $row['device_label'],
                ],
                [
                    'id'           => Str::uuid()->toString(),
                    'user_id'      => $row['user_id'],
                    'session_token'=> Str::random(64),
                    'ip_address'   => $row['ip_address'],
                    'user_agent'   => $row['user_agent'],
                    'device_label' => $row['device_label'],
                    'last_seen_at' => $row['last_seen_at'],
                    'revoked_at'   => null,
                    'created_at'   => $row['created_at'],
                ]
            );
        }
    }
}
