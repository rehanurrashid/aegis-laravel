<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $sarahId = 'p_sarah';

        // ── news_posts ───────────────────────────────────────────────────────
        $posts = [
            // 1. Pinned platform announcement
            [
                'id'              => 'np_announcement_launch',
                'author_id'       => 'admin_root',
                'title'           => 'Welcome to the Aegis Integrative Network',
                'body'            => 'We are thrilled to launch the Aegis platform — a first-of-its-kind continuity planning system for mental health practitioners. Your plan, your vault, your network — all in one place.',
                'post_type'       => 'platform',
                'role_visibility' => 'all',
                'audience'        => 'all',
                'published'       => 1,
                'pinned'          => 1,
                'tags'            => json_encode(['Announcement', 'Platform']),
                'links'           => json_encode([['label' => 'Learn more about Aegis', 'url' => 'https://aegis.devlet.tech']]),
                'poll_question'   => null,
                'poll_options'    => null,
                'poll_closes_at'  => null,
                'published_at'    => $now->copy()->subMonths(6)->toDateTimeString(),
                'created_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
            ],
            // 2. Provider post (self-post for p_sarah)
            [
                'id'              => 'np_sarah_post',
                'author_id'       => 'p_sarah',
                'title'           => 'Tips for Trauma-Informed Supervision During Clinical Transitions',
                'body'            => "After 12 years in the field, I've learned that the most overlooked element of clinical transitions is how they affect supervisee wellbeing.\n\nWhen a practitioner is suddenly unavailable, the ripple effects on supervisees can be profound — especially those working with trauma populations. Here are three strategies I've found invaluable:\n\n1. Establish a clear chain of supervisory coverage in your continuity plan.\n2. Brief your supervisees annually on what happens during a continuity event.\n3. Designate a peer supervisor they can reach immediately.",
                'post_type'       => 'provider',
                'role_visibility' => 'all',
                'audience'        => 'all',
                'published'       => 1,
                'pinned'          => 0,
                'tags'            => json_encode(['Supervision', 'TraumaInformedCare', 'ClinicalTransitions']),
                'links'           => null,
                'poll_question'   => null,
                'poll_options'    => null,
                'poll_closes_at'  => null,
                'published_at'    => $now->copy()->subDays(12)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(12)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(10)->toDateTimeString(),
            ],
            // 3. Poll post
            [
                'id'              => 'np_poll_ethics',
                'author_id'       => 'admin_root',
                'title'           => 'Poll: What is your biggest challenge with continuity planning?',
                'body'            => 'We want to hear from practitioners about the most significant barriers to maintaining an active continuity plan. Your responses help us prioritize platform improvements.',
                'post_type'       => 'poll',
                'role_visibility' => 'practitioner',
                'audience'        => 'providers',
                'published'       => 1,
                'pinned'          => 0,
                'tags'            => json_encode(['ContinuityPlanning', 'PracticeResilience']),
                'links'           => null,
                'poll_question'   => 'What is your biggest continuity planning challenge?',
                'poll_options'    => json_encode([
                    ['key' => 'time_constraints', 'label' => 'Not enough time to complete the plan'],
                    ['key' => 'cost_concerns',    'label' => 'Cost of maintaining the platform'],
                    ['key' => 'finding_steward',  'label' => 'Difficulty finding a qualified steward'],
                    ['key' => 'tech_complexity',  'label' => 'Platform complexity'],
                ]),
                'poll_closes_at'  => $now->copy()->addDays(14)->toDateTimeString(),
                'published_at'    => $now->copy()->subDays(5)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(5)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(5)->toDateTimeString(),
            ],
            // 4. Resource post
            [
                'id'              => 'np_resource_checklist',
                'author_id'       => 'cs_marcus',
                'title'           => 'New Resource: Checklist for Activating a Continuity Plan',
                'body'            => "Based on my experience responding to practitioner incidents, I've developed a step-by-step activation checklist that covers everything from client notification to vault access.\n\nDownload it below and adapt it to your practice.",
                'post_type'       => 'resource',
                'role_visibility' => 'all',
                'audience'        => 'all',
                'published'       => 1,
                'pinned'          => 0,
                'tags'            => json_encode(['ContinuityPlanning', 'Resources', 'Checklist']),
                'links'           => json_encode([
                    ['label' => 'Download Activation Checklist (PDF)', 'url' => 'https://aegis.devlet.tech/resources/activation-checklist.pdf'],
                    ['label' => 'View NASW Continuity Standards', 'url' => 'https://www.socialworkers.org'],
                ]),
                'poll_question'   => null,
                'poll_options'    => null,
                'poll_closes_at'  => null,
                'published_at'    => $now->copy()->subDays(18)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(18)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(18)->toDateTimeString(),
            ],
            // 5. Question post
            [
                'id'              => 'np_question_malpractice',
                'author_id'       => 'p_david',
                'title'           => 'Does your malpractice policy cover continuity events?',
                'body'            => "I've been reviewing my malpractice coverage and realized I'm unclear on whether my policy covers liability during a continuity activation — specifically when my CS is seeing clients on my behalf.\n\nHas anyone navigated this? Would love to know what questions to ask my insurer.",
                'post_type'       => 'question',
                'role_visibility' => 'all',
                'audience'        => 'all',
                'published'       => 1,
                'pinned'          => 0,
                'tags'            => json_encode(['Malpractice', 'Insurance', 'RiskManagement']),
                'links'           => null,
                'poll_question'   => null,
                'poll_options'    => null,
                'poll_closes_at'  => null,
                'published_at'    => $now->copy()->subDays(3)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(3)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(3)->toDateTimeString(),
            ],
            // 6. Milestone post
            [
                'id'              => 'np_milestone_100',
                'author_id'       => 'admin_root',
                'title'           => '🎉 Aegis Reaches 100 Verified Practitioners',
                'body'            => "We are proud to announce that Aegis has reached 100 verified practitioners across California, Texas, and New York.\n\nThis milestone reflects the growing recognition that practice continuity is not optional — it's a professional responsibility. Thank you for being part of this community.",
                'post_type'       => 'milestone',
                'role_visibility' => 'all',
                'audience'        => 'all',
                'published'       => 1,
                'pinned'          => 0,
                'tags'            => json_encode(['Milestone', 'Community']),
                'links'           => null,
                'poll_question'   => null,
                'poll_options'    => null,
                'poll_closes_at'  => null,
                'published_at'    => $now->copy()->subDays(7)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(7)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(7)->toDateTimeString(),
            ],
            // 7. Event announcement post
            [
                'id'              => 'np_event_announce',
                'author_id'       => 'admin_root',
                'title'           => 'Upcoming: Ethics in Telehealth — Live Q&A',
                'body'            => "Join us for a live 90-minute session with NASW faculty covering ethical boundaries in remote therapy, HIPAA considerations, and crisis protocol.\n\n1.5 CEUs awarded. Free for all Aegis members.",
                'post_type'       => 'event',
                'role_visibility' => 'all',
                'audience'        => 'all',
                'published'       => 1,
                'pinned'          => 0,
                'tags'            => json_encode(['Telehealth', 'Ethics', 'CEU']),
                'links'           => json_encode([['label' => 'Register for Free', 'url' => 'https://zoom.us/j/ethics-live']]),
                'poll_question'   => null,
                'poll_options'    => null,
                'poll_closes_at'  => null,
                'published_at'    => $now->copy()->subDays(2)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(2)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            // 8. Older post for scroll/pagination coverage
            [
                'id'              => 'np_older_boundaries',
                'author_id'       => 'p_maria',
                'title'           => 'Managing Professional Boundaries in Telehealth',
                'body'            => "The shift to telehealth has created new boundary challenges that in-person practice didn't present. Here are the key boundary areas I discuss with every supervisee:\n\n• Session environment (home office vs. bedroom)\n• Appearance and professional presentation\n• Technical interruptions and their management\n• Emergency protocols when client is remote\n\nI'd love to hear how others are handling these in their practices.",
                'post_type'       => 'provider',
                'role_visibility' => 'all',
                'audience'        => 'all',
                'published'       => 1,
                'pinned'          => 0,
                'tags'            => json_encode(['Telehealth', 'Boundaries', 'BestPractices']),
                'links'           => null,
                'poll_question'   => null,
                'poll_options'    => null,
                'poll_closes_at'  => null,
                'published_at'    => $now->copy()->subDays(25)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(25)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(25)->toDateTimeString(),
            ],
            // 9. Draft (not visible on feed — tests published=0)
            [
                'id'              => 'np_draft_unpublished',
                'author_id'       => 'p_maria',
                'title'           => 'Upcoming Webinar: Couples Therapy Best Practices 2026',
                'body'            => 'Draft content pending review...',
                'post_type'       => 'provider',
                'role_visibility' => 'all',
                'audience'        => 'all',
                'published'       => 0,
                'pinned'          => 0,
                'tags'            => null,
                'links'           => null,
                'poll_question'   => null,
                'poll_options'    => null,
                'poll_closes_at'  => null,
                'published_at'    => null,
                'created_at'      => $now->copy()->subDays(2)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(1)->toDateTimeString(),
            ],
        ];

        foreach ($posts as $p) {
            $p['deleted_at'] = null;
            DB::table('news_posts')->updateOrInsert(['id' => $p['id']], $p);
        }

        // ── news_comments ────────────────────────────────────────────────────
        // Clear and re-insert (UUIDs change each seed run without deterministic IDs)
        DB::table('news_comments')->whereIn('post_id', [
            'np_sarah_post', 'np_resource_checklist', 'np_question_malpractice', 'np_poll_ethics',
        ])->delete();

        $comments = [
            ['post_id' => 'np_sarah_post',         'author_id' => 'p_david',    'body' => 'Really valuable insights, Sarah. The point about supervisee wellbeing resonates deeply.', 'created_at' => $now->copy()->subDays(11)],
            ['post_id' => 'np_sarah_post',         'author_id' => 'p_maria',    'body' => 'Thank you for sharing this. I\'ve bookmarked it for our next group supervision session.', 'created_at' => $now->copy()->subDays(10)],
            ['post_id' => 'np_sarah_post',         'author_id' => 'cs_marcus',  'body' => 'As a CS, I see the impact of this gap constantly. This framework is exactly what practitioners need to share with their supervisees.', 'created_at' => $now->copy()->subDays(9)],
            ['post_id' => 'np_resource_checklist', 'author_id' => 'cs_priya',   'body' => 'Marcus, this checklist is incredibly thorough. I\'ve been waiting for something like this.', 'created_at' => $now->copy()->subDays(17)],
            ['post_id' => 'np_resource_checklist', 'author_id' => 'p_sarah',    'body' => 'Saving this for my annual continuity plan review. Thank you!', 'created_at' => $now->copy()->subDays(16)],
            ['post_id' => 'np_question_malpractice','author_id' => 'cs_marcus', 'body' => 'Great question. My understanding is that most malpractice policies do cover this if the CS is properly designated in the plan and the policy. Worth calling your carrier directly.', 'created_at' => $now->copy()->subDays(2)],
            ['post_id' => 'np_poll_ethics',        'author_id' => 'p_david',    'body' => 'Voted time constraints — it\'s always that for me.', 'created_at' => $now->copy()->subDays(4)],
        ];

        foreach ($comments as $c) {
            DB::table('news_comments')->insert([
                'id'         => 'nc_' . Str::lower(Str::random(12)),
                'post_id'    => $c['post_id'],
                'author_id'  => $c['author_id'],
                'body'       => $c['body'],
                'created_at' => $c['created_at']->toDateTimeString(),
                'updated_at' => $c['created_at']->toDateTimeString(),
                'deleted_at' => null,
            ]);
        }

        // ── news_reactions ───────────────────────────────────────────────────
        // column: reaction (not reaction_type)
        DB::table('news_reactions')->whereIn('post_id', array_column($posts, 'id'))->delete();

        $reactions = [
            ['post_id' => 'np_sarah_post',          'user_id' => 'p_david',    'reaction' => 'like'],
            ['post_id' => 'np_sarah_post',          'user_id' => 'p_maria',    'reaction' => 'like'],
            ['post_id' => 'np_sarah_post',          'user_id' => 'cs_marcus',  'reaction' => 'like'],
            ['post_id' => 'np_resource_checklist',  'user_id' => 'cs_priya',   'reaction' => 'like'],
            ['post_id' => 'np_resource_checklist',  'user_id' => 'p_sarah',    'reaction' => 'like'],
            ['post_id' => 'np_resource_checklist',  'user_id' => 'p_david',    'reaction' => 'save'],
            ['post_id' => 'np_announcement_launch', 'user_id' => 'p_sarah',    'reaction' => 'like'],
            ['post_id' => 'np_milestone_100',       'user_id' => 'p_sarah',    'reaction' => 'like'],
            ['post_id' => 'np_milestone_100',       'user_id' => 'p_david',    'reaction' => 'like'],
            ['post_id' => 'np_event_announce',      'user_id' => 'p_sarah',    'reaction' => 'save'],
            ['post_id' => 'np_question_malpractice','user_id' => 'p_sarah',    'reaction' => 'like'],
        ];

        foreach ($reactions as $r) {
            DB::table('news_reactions')->insert([
                'id'         => 'nr_' . Str::lower(Str::random(12)),
                'post_id'    => $r['post_id'],
                'user_id'    => $r['user_id'],
                'reaction'   => $r['reaction'],
                'created_at' => $now->copy()->subDays(rand(1, 10))->toDateTimeString(),
            ]);
        }

        // ── news_poll_votes ──────────────────────────────────────────────────
        DB::table('news_poll_votes')->where('post_id', 'np_poll_ethics')->delete();
        DB::table('news_poll_votes')->insert([
            ['id' => 'nv_' . Str::lower(Str::random(12)), 'post_id' => 'np_poll_ethics', 'user_id' => 'p_sarah', 'option_key' => 'time_constraints', 'created_at' => $now->copy()->subDays(4)->toDateTimeString()],
            ['id' => 'nv_' . Str::lower(Str::random(12)), 'post_id' => 'np_poll_ethics', 'user_id' => 'p_david', 'option_key' => 'cost_concerns',    'created_at' => $now->copy()->subDays(3)->toDateTimeString()],
            ['id' => 'nv_' . Str::lower(Str::random(12)), 'post_id' => 'np_poll_ethics', 'user_id' => 'p_maria', 'option_key' => 'finding_steward',  'created_at' => $now->copy()->subDays(2)->toDateTimeString()],
        ]);

        // ── news_trending_topics ─────────────────────────────────────────────
        DB::table('news_trending_topics')->truncate();
        $topics = [
            ['ContinuityPlanning', 42],
            ['TraumaInformedCare', 31],
            ['PracticeResilience', 28],
            ['Telehealth',         24],
            ['ClinicalSupervision',19],
        ];
        foreach ($topics as [$topic, $score]) {
            DB::table('news_trending_topics')->insert([
                'id'         => (string) \Illuminate\Support\Str::uuid(),
                'topic'      => '#' . $topic,
                'score'      => $score,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);
        }

        // ── news_library_items ───────────────────────────────────────────────
        $library = [
            ['id' => 'nl_licensing_guide',    'title' => 'State-by-State Licensing Board Notification Requirements', 'category' => 'compliance', 'file_ref' => 'library/licensing_board_guide.pdf',       'role_visibility' => 'all',               'sort_order' => 1],
            ['id' => 'nl_hipaa_templates',    'title' => 'HIPAA-Compliant Client Notification Templates',            'category' => 'templates',  'file_ref' => 'library/hipaa_notification_templates.docx','role_visibility' => 'practitioner',      'sort_order' => 2],
            ['id' => 'nl_cs_checklist',       'title' => 'CS Incident Activation Checklist',                         'category' => 'operations', 'file_ref' => 'library/cs_activation_checklist.pdf',     'role_visibility' => 'continuity_steward','sort_order' => 3],
        ];
        foreach ($library as $l) {
            DB::table('news_library_items')->updateOrInsert(['id' => $l['id']], array_merge($l, [
                'published'  => 1,
                'url'        => null,
                'created_at' => $now->copy()->subMonths(3)->toDateTimeString(),
                'updated_at' => $now->copy()->subMonths(3)->toDateTimeString(),
                'deleted_at' => null,
            ]));
        }

        // ── news_events ──────────────────────────────────────────────────────
        $events = [
            [
                'id' => 'ne_webinar_continuity', 'title' => 'Aegis Platform Webinar — Getting Started with Continuity Planning',
                'description' => 'Join our monthly onboarding webinar. Ideal for newly licensed practitioners and those updating their plans for 2026.',
                'location' => 'Virtual — Zoom', 'category' => 'webinar', 'ceu_credits' => 1.5, 'is_free' => 1, 'price_cents' => 0,
                'rsvp_url' => 'https://zoom.us/j/aegis-demo',
                'rsvps_json' => json_encode([$sarahId => ['status' => 'going', 'at' => $now->copy()->subDays(3)->toIso8601String()]]),
                'organizer' => "Aegis / MA'AT Practice Firm", 'status' => 'approved', 'role_visibility' => 'all', 'published' => 1,
                'starts_at' => $now->copy()->addDays(7)->setTime(14, 0)->toDateTimeString(),
                'ends_at'   => $now->copy()->addDays(7)->setTime(15, 30)->toDateTimeString(),
                'created_at'=> $now->copy()->subDays(10)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(3)->toDateTimeString(),
            ],
            [
                'id' => 'ne_today_ethics', 'title' => 'Ethics in Telehealth — Live Q&A with NASW Faculty',
                'description' => 'A live 90-minute session covering ethical boundaries in remote therapy.',
                'location' => 'Virtual — Zoom', 'category' => 'webinar', 'ceu_credits' => 1.5, 'is_free' => 1, 'price_cents' => 0,
                'rsvp_url' => 'https://zoom.us/j/ethics-live', 'rsvps_json' => json_encode([]),
                'organizer' => 'NASW California', 'status' => 'approved', 'role_visibility' => 'all', 'published' => 1,
                'starts_at' => $now->copy()->setTime(10, 0)->toDateTimeString(),
                'ends_at'   => $now->copy()->setTime(11, 30)->toDateTimeString(),
                'created_at'=> $now->copy()->subDays(5)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(1)->toDateTimeString(),
            ],
            [
                'id' => 'ne_trauma_workshop', 'title' => 'Trauma-Informed Supervision Workshop — Advanced Practicum',
                'description' => 'An intensive day-long workshop for licensed clinical supervisors. 6 CEU credits awarded.',
                'location' => 'Los Angeles, CA — Marriott Downtown', 'category' => 'workshop', 'ceu_credits' => 6.0, 'is_free' => 0, 'price_cents' => 19900,
                'rsvp_url' => null,
                'rsvps_json' => json_encode([$sarahId => ['status' => 'going', 'at' => $now->copy()->subDays(5)->toIso8601String()]]),
                'organizer' => 'SoCal Trauma Collective', 'status' => 'approved', 'role_visibility' => 'all', 'published' => 1,
                'starts_at' => $now->copy()->addDays(1)->setTime(9, 0)->toDateTimeString(),
                'ends_at'   => $now->copy()->addDays(1)->setTime(17, 0)->toDateTimeString(),
                'created_at'=> $now->copy()->subDays(20)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(5)->toDateTimeString(),
            ],
            [
                'id' => 'ne_mental_health_summit', 'title' => 'Integrative Mental Health Summit 2026',
                'description' => 'A three-day conference for psychologists, LMFTs, LCSWs, and LPCCs.',
                'location' => 'San Francisco, CA — Moscone Center', 'category' => 'conference', 'ceu_credits' => 12.0, 'is_free' => 0, 'price_cents' => 49900,
                'rsvp_url' => null, 'rsvps_json' => json_encode([]),
                'organizer' => 'CAMFT', 'status' => 'approved', 'role_visibility' => 'all', 'published' => 1,
                'starts_at' => $now->copy()->addDays(5)->setTime(8, 30)->toDateTimeString(),
                'ends_at'   => $now->copy()->addDays(7)->setTime(17, 0)->toDateTimeString(),
                'created_at'=> $now->copy()->subMonths(2)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(1)->toDateTimeString(),
            ],
            [
                'id' => 'ne_networking_evening', 'title' => 'Aegis Practitioner Networking — Bay Area Chapter',
                'description' => 'An informal evening mixer for Aegis members in the Bay Area.',
                'location' => 'San Francisco, CA — The Interval at Long Now', 'category' => 'networking', 'ceu_credits' => 0, 'is_free' => 1, 'price_cents' => 0,
                'rsvp_url' => null, 'rsvps_json' => json_encode([]),
                'organizer' => 'Aegis Community', 'status' => 'approved', 'role_visibility' => 'practitioner', 'published' => 1,
                'starts_at' => $now->copy()->addDays(14)->setTime(18, 30)->toDateTimeString(),
                'ends_at'   => $now->copy()->addDays(14)->setTime(21, 0)->toDateTimeString(),
                'created_at'=> $now->copy()->subDays(7)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(7)->toDateTimeString(),
            ],
        ];

        foreach ($events as $ev) {
            $ev['deleted_at'] = null;
            DB::table('news_events')->updateOrInsert(['id' => $ev['id']], $ev);
        }
    }
}
