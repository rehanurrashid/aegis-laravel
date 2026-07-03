<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * NetworkRecommendationSeeder
 *
 * Populates two things:
 *   1. A pool of "network-directory" practitioner users (nd_*) — public,
 *      searchable providers that give the Network Search Providers tab
 *      a realistic set of shadow candidates. They're not tied to any
 *      other demo interaction (no referrals, no incidents).
 *   2. Rows in network_recommendations for each active demo practitioner:
 *        • 6 partner_category rows  (the top "Recommended Network Partners" carousel)
 *        • 6 shadow_provider rows   (the "Recommended Shadow Providers" carousel)
 *
 *   Also seeds one global fallback set (user_id NULL) so brand-new users
 *   see something on the Search tab immediately.
 */
class NetworkRecommendationSeeder extends Seeder
{
    /** Recipients that get per-user recommendations. */
    private const RECIPIENTS = ['p_sarah', 'p_david', 'p_maria', 'p_access_only'];

    /**
     * Network-directory practitioners — extra users seeded here (not in
     * UserSeeder) so they can be referenced by shadow_provider recs.
     * Each entry is a minimal User row; the shape mirrors UserSeeder's rows.
     */
    private function directoryUsers(): array
    {
        // services_mode = 1 means this practitioner offers services to other
        // providers (e.g. supervision, consultation, group co-therapy).
        // Marking 4 out of 10 gives the "Clinical-service providers" toggle
        // on the Search Providers sidebar visible results to surface.
        return [
            ['id' => 'nd_rachel_moore', 'display_name' => 'Rachel Moore',    'credentials' => 'MD',    'title' => 'Psychiatrist',                           'specialty' => 'Anxiety, Mood Disorders, PTSD',      'location' => 'New York, NY',   'avatar_initials' => 'RM', 'slug' => 'rachel-moore-md',    'services_mode' => 1],
            ['id' => 'nd_sarah_nguyen', 'display_name' => 'Sarah Nguyen',    'credentials' => 'PsyD',  'title' => 'Psychologist',                           'specialty' => 'CBT, Trauma, Depression',            'location' => 'Brooklyn, NY',   'avatar_initials' => 'SN', 'slug' => 'sarah-nguyen-psyd',  'services_mode' => 0],
            ['id' => 'nd_maya_torres',  'display_name' => 'Maya Torres',     'credentials' => 'LCSW',  'title' => 'Licensed Clinical Social Worker',        'specialty' => 'DBT, Anxiety, LGBTQ+',               'location' => 'Queens, NY',     'avatar_initials' => 'MT', 'slug' => 'maya-torres-lcsw',   'services_mode' => 1],
            ['id' => 'nd_james_okafor', 'display_name' => 'James Okafor',    'credentials' => 'LMFT',  'title' => 'Marriage & Family Therapist',            'specialty' => 'Couples, Family Conflict, IFS',      'location' => 'Bronx, NY',      'avatar_initials' => 'JO', 'slug' => 'james-okafor-lmft',  'services_mode' => 0],
            ['id' => 'nd_alicia_reeves','display_name' => 'Alicia Reeves',   'credentials' => 'LPC',   'title' => 'Licensed Professional Counselor',        'specialty' => 'ACT, Stress, Life Transitions',      'location' => 'New York, NY',   'avatar_initials' => 'AR', 'slug' => 'alicia-reeves-lpc',  'services_mode' => 0],
            ['id' => 'nd_nina_park',    'display_name' => 'Nina Park',       'credentials' => 'RDN',   'title' => 'Registered Dietitian',                   'specialty' => 'Eating Disorders, Functional Nutrition','location' => 'Manhattan, NY','avatar_initials' => 'NP', 'slug' => 'nina-park-rdn',      'services_mode' => 1],
            ['id' => 'nd_daniel_kim',   'display_name' => 'Daniel Kim',      'credentials' => 'MD',    'title' => 'Neurologist',                            'specialty' => 'Neuropsychiatry, Migraine, Sleep',   'location' => 'Boston, MA',     'avatar_initials' => 'DK', 'slug' => 'daniel-kim-md',      'services_mode' => 0],
            ['id' => 'nd_luisa_pena',   'display_name' => 'Luisa Peña',      'credentials' => 'PhD',   'title' => 'Clinical Psychologist',                  'specialty' => 'Trauma, EMDR, Bilingual (ES)',       'location' => 'Miami, FL',      'avatar_initials' => 'LP', 'slug' => 'luisa-pena-phd',     'services_mode' => 1],
            ['id' => 'nd_omar_haddad',  'display_name' => 'Omar Haddad',     'credentials' => 'DO',    'title' => 'Primary Care Physician',                 'specialty' => 'Integrative Care, Chronic Illness',  'location' => 'Chicago, IL',    'avatar_initials' => 'OH', 'slug' => 'omar-haddad-do',     'services_mode' => 0],
            ['id' => 'nd_hannah_ward',  'display_name' => 'Hannah Ward',     'credentials' => 'LMFT',  'title' => 'Somatic Therapist',                      'specialty' => 'Somatic Experiencing, Attachment',   'location' => 'Portland, OR',   'avatar_initials' => 'HW', 'slug' => 'hannah-ward-lmft',   'services_mode' => 0],
        ];
    }

    /** Six recommended specialty categories per practitioner (partner_category). */
    private function categoryTemplates(): array
    {
        return [
            ['label' => 'Psychiatrist',      'description' => 'Medication management',  'icon' => 'pill-shape',      'nearby_count' => 3, 'priority' => 'high'],
            ['label' => 'Therapist / LCSW',  'description' => 'Ongoing psychotherapy',  'icon' => 'heart-2',         'nearby_count' => 6, 'priority' => 'high'],
            ['label' => 'Neurologist',       'description' => 'Neuropsychiatric care',  'icon' => 'flask',           'nearby_count' => 2, 'priority' => 'medium'],
            ['label' => 'Primary Care',      'description' => 'Holistic coordination',  'icon' => 'clipboard-check', 'nearby_count' => 5, 'priority' => 'medium'],
            ['label' => 'Dietitian',         'description' => 'Eating & metabolic',     'icon' => 'clipboard-list',  'nearby_count' => 3, 'priority' => 'medium'],
            ['label' => 'Medical Billing',   'description' => 'Revenue cycle',          'icon' => 'briefcase',       'nearby_count' => 4, 'priority' => 'biz'],
        ];
    }

    /**
     * Shadow-provider slate. Points at the nd_* users seeded above.
     * Match scores are illustrative — real values will come from the
     * matching engine in a later phase.
     */
    private function shadowSlate(): array
    {
        return [
            ['provider_user_id' => 'nd_rachel_moore',  'match_score' => 96],
            ['provider_user_id' => 'nd_sarah_nguyen',  'match_score' => 93],
            ['provider_user_id' => 'nd_nina_park',     'match_score' => 92],
            ['provider_user_id' => 'nd_james_okafor',  'match_score' => 90],
            ['provider_user_id' => 'nd_maya_torres',   'match_score' => 88],
            ['provider_user_id' => 'nd_alicia_reeves', 'match_score' => 84],
        ];
    }

    public function run(): void
    {
        $now = now();

        // ── 1. Directory practitioners ────────────────────────────────────
        foreach ($this->directoryUsers() as $u) {
            $row = array_merge($u, [
                'role'                  => 'practitioner',
                'email'                 => $u['id'] . '@directory.aegis',
                'password'              => bcrypt('demo-password'),
                'phone'                 => '',
                'organization'          => '',
                'bio'                   => '',
                'practitioner_public'   => 1,
                'tier'                  => 'access',
                'services_mode'         => (int) ($u['services_mode'] ?? 0),
                'maat_addon'            => 0,
                'two_factor_enabled'    => 0,
                'verified'              => 1,
                'stripe_connected'      => 0,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
            DB::table('users')->updateOrInsert(['id' => $u['id']], $row);
        }

        // ── 2. Per-user recommendations ───────────────────────────────────
        foreach (self::RECIPIENTS as $recipientId) {
            $this->seedFor($recipientId, $now);
        }

        // ── 3. Global fallback (user_id = NULL) ───────────────────────────
        $this->seedFor(null, $now);
    }

    private function seedFor(?string $recipientId, $now): void
    {
        // Partner category rows
        foreach ($this->categoryTemplates() as $i => $tpl) {
            $id = $this->makeId('nrc', $recipientId, $i);
            DB::table('network_recommendations')->updateOrInsert(
                ['id' => $id],
                array_merge($tpl, [
                    'user_id'          => $recipientId,
                    'kind'             => 'partner_category',
                    'provider_user_id' => null,
                    'match_score'      => null,
                    'sort_order'       => $i,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ])
            );
        }

        // Shadow provider rows
        foreach ($this->shadowSlate() as $i => $tpl) {
            $id = $this->makeId('nrs', $recipientId, $i);
            DB::table('network_recommendations')->updateOrInsert(
                ['id' => $id],
                [
                    'id'               => $id,
                    'user_id'          => $recipientId,
                    'kind'             => 'shadow_provider',
                    'label'            => '',            // display comes from providerUser
                    'description'      => null,
                    'icon'             => null,
                    'nearby_count'     => null,
                    'priority'         => null,
                    'provider_user_id' => $tpl['provider_user_id'],
                    'match_score'      => $tpl['match_score'],
                    'sort_order'       => $i,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]
            );
        }
    }

    /**
     * Stable, deterministic IDs so repeat seeder runs update-in-place
     * instead of creating duplicates.
     */
    private function makeId(string $prefix, ?string $recipientId, int $index): string
    {
        $owner = $recipientId ?? 'global';
        return $prefix . '_' . $owner . '_' . str_pad((string) $index, 2, '0', STR_PAD_LEFT);
    }
}
