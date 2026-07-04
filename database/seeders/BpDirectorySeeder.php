<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * BpDirectorySeeder
 *
 * 1. Fixes the live DB: clears business_partner_public from any user whose
 *    role is NOT business_partner (catches nd_* practitioners that were
 *    accidentally flagged in earlier dev runs).
 *
 * 2. Seeds 6 additional realistic Business Partner demo users so the
 *    Search Business Partners tab has visible, profileable content.
 *
 * Run:  php artisan db:seed --class=BpDirectorySeeder
 */
class BpDirectorySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ── 1. Fix: strip business_partner_public from non-BP users ──────────
        $fixed = DB::table('users')
            ->where('role', '!=', 'business_partner')
            ->where('business_partner_public', 1)
            ->update(['business_partner_public' => 0]);

        $this->command->info("Cleared business_partner_public from {$fixed} non-BP user(s).");

        // ── 2. Seed demo BP directory users ───────────────────────────────────
        $partners = [
            [
                'id'                      => 'bp_dir_marisol',
                'role'                    => 'business_partner',
                'display_name'            => 'Marisol Vega',
                'email'                   => 'marisol@dir.aegis',
                'phone'                   => '555-301-0001',
                'location'                => 'Miami, FL',
                'avatar_initials'         => 'MV',
                'title'                   => 'Medical Billing Specialist',
                'specialty'               => 'Medical Billing, AR Management, Denial Management',
                'slug'                    => 'marisol-vega',
                'business_partner_public' => 1,
                'bp_type'                 => 'freelancer',
                'bp_hourly_rate_cents'    => 8500,
                'verified'                => 1,
            ],
            [
                'id'                      => 'bp_dir_kevin',
                'role'                    => 'business_partner',
                'display_name'            => 'Kevin Osei',
                'credentials'             => 'CPCS',
                'email'                   => 'kevin@dir.aegis',
                'phone'                   => '555-301-0002',
                'location'                => 'Atlanta, GA',
                'avatar_initials'         => 'KO',
                'title'                   => 'Credentialing Specialist',
                'specialty'               => 'CAQH, PECOS, Re-credentialing, Provider Enrollment',
                'slug'                    => 'kevin-osei-cpcs',
                'business_partner_public' => 1,
                'bp_type'                 => 'freelancer',
                'bp_hourly_rate_cents'    => 5500,
                'verified'                => 1,
            ],
            [
                'id'                      => 'bp_dir_riya',
                'role'                    => 'business_partner',
                'display_name'            => 'Riya Patel',
                'email'                   => 'riya@dir.aegis',
                'phone'                   => '555-301-0003',
                'location'                => 'Austin, TX',
                'avatar_initials'         => 'RP',
                'title'                   => 'Digital Marketing Strategist',
                'specialty'               => 'SEO, Google Ads, Social Media, Healthcare Marketing',
                'slug'                    => 'riya-patel-marketing',
                'business_partner_public' => 1,
                'bp_type'                 => 'freelancer',
                'bp_hourly_rate_cents'    => 7000,
                'verified'                => 1,
            ],
            [
                'id'                      => 'bp_dir_daniel',
                'role'                    => 'business_partner',
                'display_name'            => 'Daniel Torres',
                'credentials'             => 'CPA',
                'email'                   => 'daniel@dir.aegis',
                'phone'                   => '555-301-0004',
                'location'                => 'New York, NY',
                'avatar_initials'         => 'DT',
                'title'                   => 'Healthcare Accountant',
                'specialty'               => 'Tax Planning, GAAP, Practice Valuation, Revenue Cycle',
                'slug'                    => 'daniel-torres-cpa',
                'business_partner_public' => 1,
                'bp_type'                 => 'freelancer',
                'bp_hourly_rate_cents'    => 17500,
                'verified'                => 1,
            ],
            [
                'id'                      => 'bp_dir_apex',
                'role'                    => 'business_partner',
                'display_name'            => 'Apex Billing Co.',
                'email'                   => 'contact@apexbilling.dir.aegis',
                'phone'                   => '555-301-0005',
                'location'                => 'Chicago, IL',
                'organization'            => 'Apex Billing Co. LLC',
                'avatar_initials'         => 'AB',
                'title'                   => 'Medical Billing Agency',
                'specialty'               => 'Revenue Cycle Management, Claims Processing, Credentialing',
                'slug'                    => 'apex-billing-co',
                'business_partner_public' => 1,
                'bp_type'                 => 'agency',
                'bp_team_size'            => 12,
                'bp_hourly_rate_cents'    => 15000,
                'verified'                => 1,
            ],
            [
                'id'                      => 'bp_dir_bright',
                'role'                    => 'business_partner',
                'display_name'            => 'Bright Minds Admin',
                'email'                   => 'contact@brightminds.dir.aegis',
                'phone'                   => '555-301-0006',
                'location'                => 'Phoenix, AZ',
                'organization'            => 'Bright Minds Administrative Services',
                'avatar_initials'         => 'BA',
                'title'                   => 'Virtual Administrative Services',
                'specialty'               => 'Virtual Assistants, Scheduling, Data Entry, EHR Management',
                'slug'                    => 'bright-minds-admin',
                'business_partner_public' => 1,
                'bp_type'                 => 'agency',
                'bp_team_size'            => 6,
                'bp_hourly_rate_cents'    => 3500,
                'verified'                => 1,
            ],
        ];

        foreach ($partners as $p) {
            $row = array_merge([
                'password'              => bcrypt('demo-password'),
                'bio'                   => '',
                'stripe_connected'      => 0,
                'two_factor_enabled'    => 0,
                'maat_addon'            => 0,
                'tier'                  => 'access',
                'practitioner_public'   => 0,
                'cs_public'             => 0,
                'created_at'            => $now->copy()->subMonths(rand(1, 8))->toDateTimeString(),
                'updated_at'            => $now->toDateTimeString(),
            ], $p);

            DB::table('users')->updateOrInsert(['id' => $p['id']], $row);

            // User roles pivot — uuid PK must be supplied on insert
            DB::table('user_roles')->updateOrInsert(
                ['user_id' => $p['id'], 'role' => 'business_partner'],
                ['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $p['id'], 'role' => 'business_partner']
            );

            $this->command->info("Seeded BP: {$p['display_name']} ({$p['slug']})");
        }
    }
}
