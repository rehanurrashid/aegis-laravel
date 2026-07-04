<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SarahConnectionRequestsSeeder
 *
 * Seeds realistic pending connection requests FOR and FROM p_sarah so the
 * Network page "Pending Connection Requests" panel and stat chips have
 * meaningful demo data.
 *
 * Incoming (recipient = p_sarah):  5 clinical practitioners, 3 BPs
 * Outgoing (requester = p_sarah):  4 clinical, 2 BP (shows in bp_pending stat)
 *
 * Run:  php artisan db:seed --class=SarahConnectionRequestsSeeder
 */
class SarahConnectionRequestsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ── Ensure required users exist (BP directory + shadow practitioners) ──
        // These are also seeded by BpDirectorySeeder + NetworkRecommendationSeeder,
        // but we guard here so this seeder is safely runnable in isolation.
        $requiredUsers = [
            // Business Partners
            [
                'id' => 'bp_dir_marisol', 'role' => 'business_partner',
                'display_name' => 'Marisol Vega', 'email' => 'marisol@dir.aegis',
                'avatar_initials' => 'MV', 'slug' => 'marisol-vega',
                'title' => 'Medical Billing Specialist', 'location' => 'Miami, FL',
                'business_partner_public' => 1, 'bp_type' => 'freelancer', 'verified' => 1,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'bp_dir_riya', 'role' => 'business_partner',
                'display_name' => 'Riya Patel', 'email' => 'riya@dir.aegis',
                'avatar_initials' => 'RP', 'slug' => 'riya-patel-marketing',
                'title' => 'Digital Marketing Strategist', 'location' => 'Austin, TX',
                'business_partner_public' => 1, 'bp_type' => 'freelancer', 'verified' => 1,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'bp_dir_apex', 'role' => 'business_partner',
                'display_name' => 'Apex Billing Co.', 'email' => 'contact@apexbilling.dir.aegis',
                'avatar_initials' => 'AB', 'slug' => 'apex-billing-co',
                'title' => 'Medical Billing Agency', 'location' => 'Chicago, IL',
                'business_partner_public' => 1, 'bp_type' => 'agency', 'verified' => 1,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'bp_dir_kevin', 'role' => 'business_partner',
                'display_name' => 'Kevin Osei', 'email' => 'kevin@dir.aegis',
                'avatar_initials' => 'KO', 'slug' => 'kevin-osei-cpcs',
                'title' => 'Credentialing Specialist', 'location' => 'Atlanta, GA',
                'business_partner_public' => 1, 'bp_type' => 'freelancer', 'verified' => 1,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'bp_dir_daniel', 'role' => 'business_partner',
                'display_name' => 'Daniel Torres', 'email' => 'daniel@dir.aegis',
                'avatar_initials' => 'DT', 'slug' => 'daniel-torres-cpa',
                'title' => 'Healthcare Accountant', 'location' => 'New York, NY',
                'business_partner_public' => 1, 'bp_type' => 'freelancer', 'verified' => 1,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
            // Shadow practitioners (nd_* users)
            [
                'id' => 'nd_rachel_moore', 'role' => 'practitioner',
                'display_name' => 'Rachel Moore', 'email' => 'rachel.moore@nd.aegis',
                'credentials' => 'MD', 'avatar_initials' => 'RM', 'slug' => 'rachel-moore-md',
                'title' => 'Psychiatrist', 'location' => 'New York, NY', 'verified' => 1,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'nd_ben_okafor', 'role' => 'practitioner',
                'display_name' => 'Benjamin Okafor', 'email' => 'ben.okafor@nd.aegis',
                'credentials' => 'MD', 'avatar_initials' => 'BO', 'slug' => 'benjamin-okafor-md',
                'title' => 'Psychiatrist', 'location' => 'Brooklyn, NY', 'verified' => 0,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'nd_priya_sharma', 'role' => 'practitioner',
                'display_name' => 'Priya Sharma', 'email' => 'priya.sharma@nd.aegis',
                'credentials' => 'LCSW', 'avatar_initials' => 'PS', 'slug' => 'priya-sharma-lcsw',
                'title' => 'Licensed Clinical Social Worker', 'location' => 'Queens, NY', 'verified' => 0,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'nd_grace_chen', 'role' => 'practitioner',
                'display_name' => 'Grace Chen', 'email' => 'grace.chen@nd.aegis',
                'credentials' => 'MD', 'avatar_initials' => 'GC', 'slug' => 'grace-chen-md',
                'title' => 'Primary Care Physician', 'location' => 'Flushing, NY', 'verified' => 1,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'nd_theo_grant', 'role' => 'practitioner',
                'display_name' => 'Theodore Grant', 'email' => 'theo.grant@nd.aegis',
                'credentials' => 'PhD', 'avatar_initials' => 'TG', 'slug' => 'theodore-grant-phd',
                'title' => 'Clinical Psychologist', 'location' => 'Newark, NJ', 'verified' => 0,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'nd_fatima_ali', 'role' => 'practitioner',
                'display_name' => 'Fatima Ali', 'email' => 'fatima.ali@nd.aegis',
                'credentials' => 'MD', 'avatar_initials' => 'FA', 'slug' => 'fatima-ali-md',
                'title' => 'Neurologist', 'location' => 'Jersey City, NJ', 'verified' => 1,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'nd_clara_novak', 'role' => 'practitioner',
                'display_name' => 'Clara Novak', 'email' => 'clara.novak@nd.aegis',
                'credentials' => 'MD', 'avatar_initials' => 'CN', 'slug' => 'clara-novak-md',
                'title' => 'Geriatric Psychiatrist', 'location' => 'Philadelphia, PA', 'verified' => 0,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'nd_marcus_bell', 'role' => 'practitioner',
                'display_name' => 'Marcus Bell', 'email' => 'marcus.bell@nd.aegis',
                'credentials' => 'MD', 'avatar_initials' => 'MB', 'slug' => 'marcus-bell-md',
                'title' => 'Internal Medicine', 'location' => 'Hoboken, NJ', 'verified' => 0,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 'nd_amber_cole', 'role' => 'practitioner',
                'display_name' => 'Amber Cole', 'email' => 'amber.cole@nd.aegis',
                'credentials' => 'RD', 'avatar_initials' => 'AC', 'slug' => 'amber-cole-rd',
                'title' => 'Registered Dietitian', 'location' => 'New York, NY', 'verified' => 1,
                'password' => bcrypt('password'), 'created_at' => $now, 'updated_at' => $now,
            ],
        ];

        foreach ($requiredUsers as $u) {
            DB::table('users')->updateOrInsert(['id' => $u['id']], $u);
        }
        $this->command->info('Ensured ' . count($requiredUsers) . ' prerequisite users exist.');

        $requests = [
            // ──────────────────────────────────────────────────────────────────
            // INCOMING — practitioners sending requests to Sarah
            // ──────────────────────────────────────────────────────────────────
            [
                'id'           => 'nr_rachel_sarah',
                'requester_id' => 'nd_rachel_moore',
                'recipient_id' => 'p_sarah',
                'status'       => 'pending',
                'message'      => 'Hi Sarah, I\'m a psychiatrist at NYU Langone with a focus on mood disorders. Would love to connect and explore referral opportunities with your practice.',
                'created_at'   => $now->copy()->subHours(3)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_ben_sarah',
                'requester_id' => 'nd_ben_okafor',
                'recipient_id' => 'p_sarah',
                'status'       => 'pending',
                'message'      => 'Hello Dr. Johnson, I specialize in bipolar and ADHD. Many of my patients need the kind of therapy-based support your practice offers — excited to connect.',
                'created_at'   => $now->copy()->subHours(18)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_priya_sarah',
                'requester_id' => 'nd_priya_sharma',
                'recipient_id' => 'p_sarah',
                'status'       => 'pending',
                'message'      => 'Hi Sarah! I\'m an LCSW working primarily with South Asian communities around trauma and CBT. Your profile stood out — I\'d love to build a referral partnership.',
                'created_at'   => $now->copy()->subDays(1)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_grace_sarah',
                'requester_id' => 'nd_grace_chen',
                'recipient_id' => 'p_sarah',
                'status'       => 'pending',
                'message'      => 'Sarah — I\'m a primary care physician in Flushing and regularly refer patients for mental health support. Would love to have you in my network.',
                'created_at'   => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_theo_sarah',
                'requester_id' => 'nd_theo_grant',
                'recipient_id' => 'p_sarah',
                'status'       => 'pending',
                'message'      => 'Hi Dr. Johnson — reaching out from Newark. I do a lot of dual-diagnosis work and think our practices would complement each other well.',
                'created_at'   => $now->copy()->subDays(3)->toDateTimeString(),
            ],

            // ──────────────────────────────────────────────────────────────────
            // INCOMING — business partners sending requests to Sarah
            // ──────────────────────────────────────────────────────────────────
            [
                'id'           => 'nr_marisol_sarah',
                'requester_id' => 'bp_dir_marisol',
                'recipient_id' => 'p_sarah',
                'status'       => 'pending',
                'message'      => 'Hi Dr. Johnson, I\'m a medical billing specialist and noticed your practice isn\'t listed with a billing partner yet. I\'d love to show you how I can improve your revenue cycle.',
                'created_at'   => $now->copy()->subHours(6)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_riya_sarah',
                'requester_id' => 'bp_dir_riya',
                'recipient_id' => 'p_sarah',
                'status'       => 'pending',
                'message'      => 'Hello Sarah! I specialize in healthcare marketing — SEO, Google Ads, and social. I think I can help you grow your patient base meaningfully. Let\'s connect!',
                'created_at'   => $now->copy()->subDays(1)->subHours(4)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_apex_sarah',
                'requester_id' => 'bp_dir_apex',
                'recipient_id' => 'p_sarah',
                'status'       => 'pending',
                'message'      => 'Hi Dr. Johnson — Apex Billing Co. works with over 40 mental health practices in the tri-state area. We\'d love to discuss a billing partnership.',
                'created_at'   => $now->copy()->subDays(2)->subHours(2)->toDateTimeString(),
            ],

            // ──────────────────────────────────────────────────────────────────
            // OUTGOING — Sarah sent these, waiting for response
            // ──────────────────────────────────────────────────────────────────
            [
                'id'           => 'nr_sarah_fatima',
                'requester_id' => 'p_sarah',
                'recipient_id' => 'nd_fatima_ali',
                'status'       => 'pending',
                'message'      => 'Hi Dr. Ali — I frequently work with patients who present with neurological components to their mental health. Would love to establish a referral relationship.',
                'created_at'   => $now->copy()->subDays(1)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_sarah_clara',
                'requester_id' => 'p_sarah',
                'recipient_id' => 'nd_clara_novak',
                'status'       => 'pending',
                'message'      => 'Hi Clara, your geriatric psychiatry focus is exactly what some of my older patients need. Excited to connect!',
                'created_at'   => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_sarah_marcus_bell',
                'requester_id' => 'p_sarah',
                'recipient_id' => 'nd_marcus_bell',
                'status'       => 'pending',
                'message'      => 'Hi Dr. Bell — reaching out to build a relationship for patients who need integrated primary care and mental health support.',
                'created_at'   => $now->copy()->subDays(3)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_sarah_amber',
                'requester_id' => 'p_sarah',
                'recipient_id' => 'nd_amber_cole',
                'status'       => 'pending',
                'message'      => 'Hi Amber, I have several patients working on eating disorder recovery who could benefit from nutritional support. Would love to connect.',
                'created_at'   => $now->copy()->subDays(4)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_sarah_kevin',
                'requester_id' => 'p_sarah',
                'recipient_id' => 'bp_dir_kevin',
                'status'       => 'pending',
                'message'      => 'Hi Kevin — I need credentialing support for a new insurance panel. Your CAQH and PECOS experience looks like exactly what I need.',
                'created_at'   => $now->copy()->subDays(1)->subHours(6)->toDateTimeString(),
            ],
            [
                'id'           => 'nr_sarah_daniel_bp',
                'requester_id' => 'p_sarah',
                'recipient_id' => 'bp_dir_daniel',
                'status'       => 'pending',
                'message'      => 'Hi Daniel, looking for a CPA with healthcare experience to help with my practice finances. Your background looks ideal.',
                'created_at'   => $now->copy()->subDays(2)->subHours(3)->toDateTimeString(),
            ],
        ];

        $defaults = [
            'recipient_email' => null,
            'invite_token'    => null,
            'responded_at'    => null,
            'updated_at'      => null,
        ];

        foreach ($requests as $r) {
            $r['updated_at']    = $r['created_at'];
            $r['invite_token']  = 'tok_' . Str::lower(Str::random(24));
            $row = array_merge($defaults, $r);
            DB::table('network_requests')->updateOrInsert(['id' => $r['id']], $row);
            $this->command->info("Seeded: {$r['id']}");
        }

        $this->command->info('Done — ' . count($requests) . ' connection requests seeded for p_sarah.');
    }
}
