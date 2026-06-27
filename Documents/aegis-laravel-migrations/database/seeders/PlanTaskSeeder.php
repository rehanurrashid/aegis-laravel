<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanTaskSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $tasks = [
            // p_sarah plan — CS tasks
            ['id' => 'pt_sarah_cs_1', 'plan_id' => 'plan_sarah', 'assigned_to' => 'continuity_steward', 'steward_id' => 'cs_marcus', 'title' => 'Contact all active clients within 24 hours of activation', 'timeline' => 'Within 24 hours', 'sort_order' => 1],
            ['id' => 'pt_sarah_cs_2', 'plan_id' => 'plan_sarah', 'assigned_to' => 'continuity_steward', 'steward_id' => 'cs_marcus', 'title' => 'Coordinate referrals to covering practitioners', 'timeline' => 'Within 48 hours', 'sort_order' => 2],
            ['id' => 'pt_sarah_cs_3', 'plan_id' => 'plan_sarah', 'assigned_to' => 'continuity_steward', 'steward_id' => 'cs_marcus', 'title' => 'Notify licensing board if absence exceeds 30 days', 'timeline' => 'Day 30', 'sort_order' => 3],
            ['id' => 'pt_sarah_cs_4', 'plan_id' => 'plan_sarah', 'assigned_to' => 'continuity_steward', 'steward_id' => 'cs_marcus', 'title' => 'Secure clinical records per state regulations', 'timeline' => 'Within 72 hours', 'sort_order' => 4],
            ['id' => 'pt_sarah_cs_5', 'plan_id' => 'plan_sarah', 'assigned_to' => 'continuity_steward', 'steward_id' => 'cs_marcus', 'title' => 'File insurance suspension notices', 'timeline' => 'Within 7 days', 'sort_order' => 5],

            // p_sarah plan — SS tasks
            ['id' => 'pt_sarah_ss_1', 'plan_id' => 'plan_sarah', 'assigned_to' => 'support_steward', 'steward_id' => 'ss_linda', 'title' => 'Verify practitioner status and document findings', 'timeline' => 'Immediately upon concern', 'sort_order' => 1],
            ['id' => 'pt_sarah_ss_2', 'plan_id' => 'plan_sarah', 'assigned_to' => 'support_steward', 'steward_id' => 'ss_linda', 'title' => 'Notify Continuity Steward of confirmed incident', 'timeline' => 'Within 2 hours of confirmation', 'sort_order' => 2],
            ['id' => 'pt_sarah_ss_3', 'plan_id' => 'plan_sarah', 'assigned_to' => 'support_steward', 'steward_id' => 'ss_linda', 'title' => 'Provide weekly status updates during active incident', 'timeline' => 'Weekly', 'sort_order' => 3],

            // p_maria plan tasks
            ['id' => 'pt_maria_cs_1', 'plan_id' => 'plan_maria', 'assigned_to' => 'continuity_steward', 'steward_id' => 'cs_priya', 'title' => 'Notify couples and family clients with alternative referrals', 'timeline' => 'Within 24 hours', 'sort_order' => 1],
            ['id' => 'pt_maria_cs_2', 'plan_id' => 'plan_maria', 'assigned_to' => 'continuity_steward', 'steward_id' => 'cs_priya', 'title' => 'Coordinate intensive retreat cancellations and refunds', 'timeline' => 'Within 48 hours', 'sort_order' => 2],
            ['id' => 'pt_maria_ss_1', 'plan_id' => 'plan_maria', 'assigned_to' => 'support_steward', 'steward_id' => 'ss_james', 'title' => 'Confirm practitioner medical status with family contact', 'timeline' => 'Immediately', 'sort_order' => 1],
        ];

        foreach ($tasks as $t) {
            $t = array_merge(['deleted_at' => null, 'created_at' => now()->subMonths(6), 'updated_at' => now()->subMonths(6)], $t);
            DB::table('plan_tasks')->updateOrInsert(['id' => $t['id']], $t);
        }
    }
}
