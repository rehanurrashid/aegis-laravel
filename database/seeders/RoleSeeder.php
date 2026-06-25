<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->toDateTimeString();

        // roles columns: id, name, system_role, description, created_at, updated_at, deleted_at
        $roles = [
            ['id' => 'role_admin',       'name' => 'Admin',              'system_role' => 1, 'description' => 'Platform administrator with full access.',           'created_at' => $now, 'updated_at' => $now],
            ['id' => 'role_practitioner','name' => 'Practitioner',       'system_role' => 1, 'description' => 'Licensed healthcare practitioner.',                   'created_at' => $now, 'updated_at' => $now],
            ['id' => 'role_cs',          'name' => 'Continuity Steward', 'system_role' => 1, 'description' => 'Manages continuity plans on behalf of practitioners.','created_at' => $now, 'updated_at' => $now],
            ['id' => 'role_ss',          'name' => 'Support Steward',    'system_role' => 1, 'description' => 'Provides on-the-ground support and check-ins.',       'created_at' => $now, 'updated_at' => $now],
            ['id' => 'role_bp',          'name' => 'Business Partner',   'system_role' => 1, 'description' => 'External business partners offering services.',        'created_at' => $now, 'updated_at' => $now],
            ['id' => 'role_cs_reviewer', 'name' => 'Senior CS Reviewer', 'system_role' => 0, 'description' => 'Read-only elevated access for CS quality review.',    'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(['id' => $role['id']], $role);
        }

        // role_permissions columns: id, role_id, permission_key, granted, created_at, updated_at
        $adminPerms = [
            'manage_users','lock_user','unlock_user','deactivate_user','change_user_role',
            'view_complaints','assign_complaint','reply_complaint','resolve_complaint',
            'manage_packages','override_packages','view_payments','refund_payment',
            'view_audit_log','manage_roles','manage_help_articles','impersonate_user',
            'manage_announcements','view_analytics',
        ];
        foreach ($adminPerms as $perm) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => 'role_admin', 'permission_key' => $perm],
                ['id' => (string) Str::uuid(), 'role_id' => 'role_admin', 'permission_key' => $perm, 'granted' => 1, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $reviewerPerms = ['view_complaints','reply_complaint','view_audit_log','view_analytics'];
        foreach ($reviewerPerms as $perm) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => 'role_cs_reviewer', 'permission_key' => $perm],
                ['id' => (string) Str::uuid(), 'role_id' => 'role_cs_reviewer', 'permission_key' => $perm, 'granted' => 1, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $this->command->info('RoleSeeder: ' . count($roles) . ' roles seeded.');
    }
}
