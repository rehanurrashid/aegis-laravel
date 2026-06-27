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

        // news_events columns: id, title, description, location, starts_at, ends_at, role_visibility, published, created_at, updated_at, deleted_at
        // Fixed: author_id, event_date, event_time, location_type, location_url removed (don't exist)
        // starts_at replaces event_date; location is a plain string
        DB::table('news_events')->insert([
            'id'              => (string) Str::uuid(),
            'title'           => 'Aegis Platform Webinar — Getting Started with Continuity Planning',
            'description'     => 'Join our monthly onboarding webinar to learn how to set up your continuity plan, add stewards, and configure your vault. Join via Zoom: https://zoom.us/j/demo',
            'location'        => 'Virtual — Zoom',
            'starts_at'       => $now->copy()->addDays(14)->setTime(14, 0, 0)->toDateTimeString(),
            'ends_at'         => $now->copy()->addDays(14)->setTime(15, 30, 0)->toDateTimeString(),
            'role_visibility' => 'all',
            'published'       => 1,
            'created_at'      => $now->copy()->subDays(5)->toDateTimeString(),
            'updated_at'      => $now->copy()->subDays(5)->toDateTimeString(),
        ]);
    }
}
