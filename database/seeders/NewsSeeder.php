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

        $posts = [
            [
                'id'              => 'np_announcement_launch',
                'author_id'       => 'admin_root',
                'title'           => 'Welcome to the Aegis Integrative Network',
                'body'            => 'We are thrilled to launch the Aegis platform — a first-of-its-kind continuity planning system for mental health practitioners.',
                'post_type'       => 'announcement',
                'role_visibility' => 'all',
                'published'       => 1,
                'pinned'          => 1,
                'published_at'    => $now->copy()->subMonths(6)->toDateTimeString(),
                'created_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(6)->toDateTimeString(),
            ],
            [
                'id'              => 'np_sarah_post',
                'author_id'       => 'p_sarah',
                'title'           => 'Tips for Trauma-Informed Supervision During Clinical Transitions',
                'body'            => 'After 12 years in the field, I\'ve learned that the most overlooked element of clinical transitions is how they affect supervisee wellbeing.',
                'post_type'       => 'post',
                'role_visibility' => 'all',
                'published'       => 1,
                'pinned'          => 0,
                'published_at'    => $now->copy()->subDays(12)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(12)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(10)->toDateTimeString(),
            ],
            [
                'id'              => 'np_poll_ethics',
                'author_id'       => 'admin_root',
                'title'           => 'Poll: What is your biggest challenge with continuity planning?',
                'body'            => 'We want to hear from practitioners about the most significant barriers to maintaining an active continuity plan.',
                'post_type'       => 'poll',
                'role_visibility' => 'practitioner',
                'published'       => 1,
                'pinned'          => 0,
                'published_at'    => $now->copy()->subDays(5)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(5)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(5)->toDateTimeString(),
            ],
            [
                'id'              => 'np_cs_resource',
                'author_id'       => 'cs_marcus',
                'title'           => 'New Resource: Checklist for Activating a Continuity Plan',
                'body'            => 'Based on my experience responding to practitioner incidents, I\'ve developed a step-by-step activation checklist.',
                'post_type'       => 'post',
                'role_visibility' => 'continuity_steward',
                'published'       => 1,
                'pinned'          => 0,
                'published_at'    => $now->copy()->subDays(18)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(18)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(18)->toDateTimeString(),
            ],
            [
                'id'              => 'np_draft_unpublished',
                'author_id'       => 'p_maria',
                'title'           => 'Upcoming Webinar: Couples Therapy Best Practices 2025',
                'body'            => 'Draft content pending review...',
                'post_type'       => 'post',
                'role_visibility' => 'all',
                'published'       => 0,
                'pinned'          => 0,
                'published_at'    => null,
                'created_at'      => $now->copy()->subDays(2)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(1)->toDateTimeString(),
            ],
        ];

        foreach ($posts as $p) {
            $p['deleted_at'] = null;
            DB::table('news_posts')->updateOrInsert(['id' => $p['id']], $p);
        }

        // news_comments: id, post_id, author_id, body, created_at, updated_at (no updated_at? softDeletes yes)
        $comments = [
            ['id' => (string) Str::uuid(), 'post_id' => 'np_sarah_post', 'author_id' => 'p_david', 'body' => 'Really valuable insights, Sarah. The point about supervisee wellbeing resonates deeply.', 'created_at' => $now->copy()->subDays(11)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(11)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'post_id' => 'np_sarah_post', 'author_id' => 'p_maria', 'body' => 'Thank you for sharing this. I\'ve bookmarked it for our next group supervision session.', 'created_at' => $now->copy()->subDays(10)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(10)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'post_id' => 'np_cs_resource', 'author_id' => 'cs_priya', 'body' => 'Marcus, this checklist is incredibly thorough.', 'created_at' => $now->copy()->subDays(17)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(17)->toDateTimeString()],
        ];
        foreach ($comments as $c) {
            DB::table('news_comments')->insert($c);
        }

        // news_reactions columns: id, post_id, user_id, reaction, created_at
        // Fixed: reaction_type → reaction
        $reactions = [
            ['id' => (string) Str::uuid(), 'post_id' => 'np_sarah_post',          'user_id' => 'p_david',  'reaction' => 'like', 'created_at' => $now->copy()->subDays(11)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'post_id' => 'np_sarah_post',          'user_id' => 'p_maria',  'reaction' => 'like', 'created_at' => $now->copy()->subDays(10)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'post_id' => 'np_cs_resource',         'user_id' => 'cs_priya', 'reaction' => 'like', 'created_at' => $now->copy()->subDays(17)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'post_id' => 'np_announcement_launch', 'user_id' => 'p_sarah',  'reaction' => 'like', 'created_at' => $now->copy()->subMonths(6)->addDays(1)->toDateTimeString()],
        ];
        foreach ($reactions as $r) {
            DB::table('news_reactions')->insert($r);
        }

        // news_poll_votes columns: id, post_id, user_id, option_key, created_at
        // Fixed: option_index → option_key (string)
        // unique constraint: (post_id, user_id) — one vote per user per poll
        DB::table('news_poll_votes')->insert([
            'id' => (string) Str::uuid(), 'post_id' => 'np_poll_ethics', 'user_id' => 'p_sarah', 'option_key' => 'time_constraints', 'created_at' => $now->copy()->subDays(4)->toDateTimeString(),
        ]);
        DB::table('news_poll_votes')->insert([
            'id' => (string) Str::uuid(), 'post_id' => 'np_poll_ethics', 'user_id' => 'p_david', 'option_key' => 'cost_concerns', 'created_at' => $now->copy()->subDays(3)->toDateTimeString(),
        ]);

        // news_trending_topics columns: id, topic, score, created_at, updated_at
        // Fixed: post_count→score, week_start removed (doesn't exist)
        $topics = [
            ['id' => (string) Str::uuid(), 'topic' => '#ContinuityPlanning', 'score' => 42, 'created_at' => $now->copy()->startOfWeek()->toDateTimeString(), 'updated_at' => $now->copy()->startOfWeek()->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'topic' => '#TraumaInformedCare', 'score' => 31, 'created_at' => $now->copy()->startOfWeek()->toDateTimeString(), 'updated_at' => $now->copy()->startOfWeek()->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'topic' => '#PracticeResilience', 'score' => 28, 'created_at' => $now->copy()->startOfWeek()->toDateTimeString(), 'updated_at' => $now->copy()->startOfWeek()->toDateTimeString()],
        ];
        foreach ($topics as $t) {
            DB::table('news_trending_topics')->insert($t);
        }

        // news_library_items columns: id, title, category, url, file_ref, role_visibility, sort_order, published, created_at, updated_at, deleted_at
        // Fixed: author_id removed (doesn't exist)
        $library = [
            ['id' => (string) Str::uuid(), 'title' => 'State-by-State Licensing Board Notification Requirements', 'category' => 'compliance', 'file_ref' => 'library/licensing_board_guide.pdf', 'role_visibility' => 'all',                 'sort_order' => 1, 'published' => 1, 'created_at' => $now->copy()->subMonths(4)->toDateTimeString(), 'updated_at' => $now->copy()->subMonths(4)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'title' => 'HIPAA-Compliant Client Notification Templates',            'category' => 'templates',  'file_ref' => 'library/hipaa_notification_templates.docx', 'role_visibility' => 'practitioner',   'sort_order' => 1, 'published' => 1, 'created_at' => $now->copy()->subMonths(3)->toDateTimeString(), 'updated_at' => $now->copy()->subMonths(3)->toDateTimeString()],
            ['id' => (string) Str::uuid(), 'title' => 'CS Incident Activation Checklist',                         'category' => 'operations', 'file_ref' => 'library/cs_activation_checklist.pdf', 'role_visibility' => 'continuity_steward', 'sort_order' => 1, 'published' => 1, 'created_at' => $now->copy()->subDays(18)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(18)->toDateTimeString()],
        ];
        foreach ($library as $l) {
            DB::table('news_library_items')->insert($l);
        }

        // ── news_events — rich seed covering all UI states ──────────────────────────
        // Columns (after 2026_07_04 migration):
        //   id, title, description, location, category, ceu_credits, is_free,
        //   price_cents, rsvp_url, rsvps_json, organizer, status,
        //   starts_at, ends_at, role_visibility, published, created_at, updated_at, deleted_at
        $sarahId = DB::table('users')->where('id', 'p_sarah')->value('id') ?? 'p_sarah';

        $events = [
            // 1. Featured upcoming — webinar, free, CEU, Sarah registered
            [
                'id'              => 'ne_webinar_continuity',
                'title'           => 'Aegis Platform Webinar — Getting Started with Continuity Planning',
                'description'     => 'Join our monthly onboarding webinar to learn how to set up your continuity plan, add stewards, and configure your vault. Ideal for newly licensed practitioners and those updating their plans for 2026.',
                'location'        => 'Virtual — Zoom',
                'category'        => 'webinar',
                'ceu_credits'     => 1.5,
                'is_free'         => 1,
                'price_cents'     => 0,
                'rsvp_url'        => 'https://zoom.us/j/aegis-demo',
                'rsvps_json'      => json_encode([$sarahId => ['status' => 'going', 'at' => $now->copy()->subDays(3)->toIso8601String()]]),
                'organizer'       => 'Aegis / MA\'AT Practice Firm',
                'status'          => 'approved',
                'role_visibility' => 'all',
                'published'       => 1,
                'starts_at'       => $now->copy()->addDays(7)->setTime(14, 0, 0)->toDateTimeString(),
                'ends_at'         => $now->copy()->addDays(7)->setTime(15, 30, 0)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(10)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(3)->toDateTimeString(),
            ],
            // 2. Today's event (urgency badge "Today")
            [
                'id'              => 'ne_today_ethics',
                'title'           => 'Ethics in Telehealth — Live Q&A with NASW Faculty',
                'description'     => 'A live 90-minute session covering ethical boundaries in remote therapy, HIPAA considerations, and crisis protocol in telehealth settings. Q&A with licensed NASW faculty.',
                'location'        => 'Virtual — Zoom',
                'category'        => 'webinar',
                'ceu_credits'     => 1.5,
                'is_free'         => 1,
                'price_cents'     => 0,
                'rsvp_url'        => 'https://zoom.us/j/ethics-live',
                'rsvps_json'      => json_encode([]),
                'organizer'       => 'NASW California',
                'status'          => 'approved',
                'role_visibility' => 'all',
                'published'       => 1,
                'starts_at'       => $now->copy()->setTime(10, 0, 0)->toDateTimeString(),
                'ends_at'         => $now->copy()->setTime(11, 30, 0)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(5)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(1)->toDateTimeString(),
            ],
            // 3. Tomorrow (urgency badge "Tomorrow")
            [
                'id'              => 'ne_trauma_workshop',
                'title'           => 'Trauma-Informed Supervision Workshop — Advanced Practicum',
                'description'     => 'An intensive day-long workshop for licensed clinical supervisors. Covers trauma-responsive supervision models, parallel process, and managing vicarious trauma. 6 CEU credits awarded.',
                'location'        => 'Los Angeles, CA — Marriott Downtown',
                'category'        => 'workshop',
                'ceu_credits'     => 6.0,
                'is_free'         => 0,
                'price_cents'     => 19900,
                'rsvp_url'        => null,
                'rsvps_json'      => json_encode([$sarahId => ['status' => 'going', 'at' => $now->copy()->subDays(5)->toIso8601String()]]),
                'organizer'       => 'SoCal Trauma Collective',
                'status'          => 'approved',
                'role_visibility' => 'all',
                'published'       => 1,
                'starts_at'       => $now->copy()->addDays(1)->setTime(9, 0, 0)->toDateTimeString(),
                'ends_at'         => $now->copy()->addDays(1)->setTime(17, 0, 0)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(20)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(5)->toDateTimeString(),
            ],
            // 4. 5 days away — conference, paid
            [
                'id'              => 'ne_mental_health_summit',
                'title'           => 'Integrative Mental Health Summit 2026',
                'description'     => 'A three-day conference bringing together psychologists, LMFTs, LCSWs, and LPCCs to explore emerging treatment modalities, licensure reform, and the future of behavioral health practice.',
                'location'        => 'San Francisco, CA — Moscone Center',
                'category'        => 'conference',
                'ceu_credits'     => 12.0,
                'is_free'         => 0,
                'price_cents'     => 49900,
                'rsvp_url'        => null,
                'rsvps_json'      => json_encode([]),
                'organizer'       => 'California Association of Marriage & Family Therapists',
                'status'          => 'approved',
                'role_visibility' => 'all',
                'published'       => 1,
                'starts_at'       => $now->copy()->addDays(5)->setTime(8, 30, 0)->toDateTimeString(),
                'ends_at'         => $now->copy()->addDays(7)->setTime(17, 0, 0)->toDateTimeString(),
                'created_at'      => $now->copy()->subMonths(2)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(1)->toDateTimeString(),
            ],
            // 5. 14 days — networking, free
            [
                'id'              => 'ne_networking_evening',
                'title'           => 'Aegis Practitioner Networking — Bay Area Chapter',
                'description'     => 'An informal evening mixer for Aegis members in the Bay Area. Share referral networks, continuity strategies, and build community with local practitioners.',
                'location'        => 'San Francisco, CA — The Interval at Long Now',
                'category'        => 'networking',
                'ceu_credits'     => 0,
                'is_free'         => 1,
                'price_cents'     => 0,
                'rsvp_url'        => null,
                'rsvps_json'      => json_encode([]),
                'organizer'       => 'Aegis Community',
                'status'          => 'approved',
                'role_visibility' => 'practitioner',
                'published'       => 1,
                'starts_at'       => $now->copy()->addDays(14)->setTime(18, 30, 0)->toDateTimeString(),
                'ends_at'         => $now->copy()->addDays(14)->setTime(21, 0, 0)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(7)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(7)->toDateTimeString(),
            ],
            // 6. 21 days — CEU training, paid
            [
                'id'              => 'ne_ceu_billing',
                'title'           => 'Medicare & Medicaid Billing for Mental Health Providers',
                'description'     => 'A comprehensive 3-hour training covering Medicare reimbursement codes for outpatient mental health, documentation requirements, and avoiding common audit triggers.',
                'location'        => 'Online — Self-paced with live Q&A',
                'category'        => 'training',
                'ceu_credits'     => 3.0,
                'is_free'         => 0,
                'price_cents'     => 7900,
                'rsvp_url'        => null,
                'rsvps_json'      => json_encode([]),
                'organizer'       => 'CEU Hub',
                'status'          => 'approved',
                'role_visibility' => 'all',
                'published'       => 1,
                'starts_at'       => $now->copy()->addDays(21)->setTime(12, 0, 0)->toDateTimeString(),
                'ends_at'         => $now->copy()->addDays(21)->setTime(15, 0, 0)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(14)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(14)->toDateTimeString(),
            ],
            // 7. Next month — workshop, paid, high CEUs
            [
                'id'              => 'ne_adhd_intensive',
                'title'           => 'ADHD Assessment & Treatment — 2-Day Intensive',
                'description'     => 'A two-day intensive workshop for clinicians seeking advanced competency in ADHD assessment across the lifespan, with a focus on differential diagnosis and evidence-based treatment planning.',
                'location'        => 'Los Angeles, CA — UCLA Conference Center',
                'category'        => 'workshop',
                'ceu_credits'     => 12.0,
                'is_free'         => 0,
                'price_cents'     => 34900,
                'rsvp_url'        => null,
                'rsvps_json'      => json_encode([]),
                'organizer'       => 'CHADD California',
                'status'          => 'approved',
                'role_visibility' => 'all',
                'published'       => 1,
                'starts_at'       => $now->copy()->addDays(34)->setTime(8, 0, 0)->toDateTimeString(),
                'ends_at'         => $now->copy()->addDays(35)->setTime(17, 0, 0)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(30)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(30)->toDateTimeString(),
            ],
            // 8. 6 weeks — conference, paid
            [
                'id'              => 'ne_apa_annual',
                'title'           => 'APA Annual Convention 2026 — Pacific Regional',
                'description'     => 'The Pacific Region gathering of the American Psychological Association. Features keynote sessions, continuing education workshops, a career fair, and collaborative research presentations.',
                'location'        => 'Seattle, WA — Washington State Convention Center',
                'category'        => 'conference',
                'ceu_credits'     => 16.0,
                'is_free'         => 0,
                'price_cents'     => 59900,
                'rsvp_url'        => 'https://apa.org/convention/2026',
                'rsvps_json'      => json_encode([]),
                'organizer'       => 'American Psychological Association',
                'status'          => 'approved',
                'role_visibility' => 'all',
                'published'       => 1,
                'starts_at'       => $now->copy()->addDays(42)->setTime(9, 0, 0)->toDateTimeString(),
                'ends_at'         => $now->copy()->addDays(45)->setTime(18, 0, 0)->toDateTimeString(),
                'created_at'      => $now->copy()->subMonths(3)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(1)->toDateTimeString(),
            ],
            // 9. 8 weeks — networking, free
            [
                'id'              => 'ne_cs_steward_meetup',
                'title'           => 'Continuity Steward Roundtable — Summer 2026',
                'description'     => 'An open virtual roundtable for Continuity Stewards to share experiences, best practices, and challenges. All CS members are welcome.',
                'location'        => 'Virtual — Google Meet',
                'category'        => 'networking',
                'ceu_credits'     => 0,
                'is_free'         => 1,
                'price_cents'     => 0,
                'rsvp_url'        => null,
                'rsvps_json'      => json_encode([]),
                'organizer'       => 'Aegis Community',
                'status'          => 'approved',
                'role_visibility' => 'continuity_steward',
                'published'       => 1,
                'starts_at'       => $now->copy()->addDays(56)->setTime(13, 0, 0)->toDateTimeString(),
                'ends_at'         => $now->copy()->addDays(56)->setTime(14, 30, 0)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(2)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            // 10. Pending (admin review — not visible on public feed)
            [
                'id'              => 'ne_pending_submission',
                'title'           => 'Community-Submitted: Perinatal Mental Health — Workshop',
                'description'     => 'A practitioner-submitted workshop on screening and supporting perinatal mental health conditions.',
                'location'        => 'San Diego, CA',
                'category'        => 'workshop',
                'ceu_credits'     => 4.0,
                'is_free'         => 0,
                'price_cents'     => 9900,
                'rsvp_url'        => null,
                'rsvps_json'      => json_encode([]),
                'organizer'       => 'Perinatal Mental Health Alliance',
                'status'          => 'pending',
                'role_visibility' => 'all',
                'published'       => 0,
                'starts_at'       => $now->copy()->addDays(30)->setTime(9, 0, 0)->toDateTimeString(),
                'ends_at'         => $now->copy()->addDays(30)->setTime(17, 0, 0)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(1)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(1)->toDateTimeString(),
            ],
            // 11. Past event — webinar, completed
            [
                'id'              => 'ne_past_telehealth',
                'title'           => 'Telehealth Best Practices 2025 — Recorded Webinar',
                'description'     => 'A recorded session covering HIPAA-compliant telehealth platforms, session documentation, and managing therapeutic boundaries in virtual environments.',
                'location'        => 'Virtual — Recording available',
                'category'        => 'webinar',
                'ceu_credits'     => 3.0,
                'is_free'         => 0,
                'price_cents'     => 4900,
                'rsvp_url'        => null,
                'rsvps_json'      => json_encode([$sarahId => ['status' => 'going', 'at' => $now->copy()->subMonths(2)->toIso8601String()]]),
                'organizer'       => 'APA',
                'status'          => 'approved',
                'role_visibility' => 'all',
                'published'       => 1,
                'starts_at'       => $now->copy()->subMonths(2)->setTime(14, 0, 0)->toDateTimeString(),
                'ends_at'         => $now->copy()->subMonths(2)->setTime(17, 0, 0)->toDateTimeString(),
                'created_at'      => $now->copy()->subMonths(3)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(2)->toDateTimeString(),
            ],
            // 12. Past event — training, completed
            [
                'id'              => 'ne_past_medicare',
                'title'           => 'Medicare Billing for Mental Health — Spring 2025',
                'description'     => 'An in-depth workshop on billing Medicare for outpatient mental health services, including documentation, coding, and audit compliance.',
                'location'        => 'Online',
                'category'        => 'training',
                'ceu_credits'     => 6.0,
                'is_free'         => 0,
                'price_cents'     => 14900,
                'rsvp_url'        => null,
                'rsvps_json'      => json_encode([]),
                'organizer'       => 'CEU Hub',
                'status'          => 'approved',
                'role_visibility' => 'all',
                'published'       => 1,
                'starts_at'       => $now->copy()->subMonths(4)->setTime(9, 0, 0)->toDateTimeString(),
                'ends_at'         => $now->copy()->subMonths(4)->setTime(15, 0, 0)->toDateTimeString(),
                'created_at'      => $now->copy()->subMonths(5)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(4)->toDateTimeString(),
            ],
        ];

        foreach ($events as $ev) {
            $ev['deleted_at'] = null;
            DB::table('news_events')->updateOrInsert(['id' => $ev['id']], $ev);
        }
    }
}
