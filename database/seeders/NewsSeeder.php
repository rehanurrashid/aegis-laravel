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
        $sarahId  = 'p_sarah';
        $davidId  = 'p_david';
        $mariaId  = 'p_maria';
        $marcusId = 'cs_marcus';
        $adminId  = 'admin_root';

        // ── news_posts ────────────────────────────────────────────────────────
        $posts = [
            // 1. PLATFORM — pinned welcome (admin)
            [
                'id' => 'np_platform_welcome', 'author_id' => $adminId,
                'post_type' => 'platform', 'role_visibility' => 'all', 'audience' => 'all',
                'published' => 1, 'pinned' => 1,
                'title' => 'Welcome to Aegis — Your Practice Continuity Command Center',
                'body' => "We're thrilled to launch Aegis, the first platform built specifically for mental health practice continuity.\n\nThis feed is your community hub — share insights, ask questions, post resources, and stay informed. Admin updates will always be pinned at the top.\n\nTo get started: complete your continuity plan, designate your steward, and fill your vault. Questions? Use the Support tab anytime.",
                'tags' => json_encode(['Announcement', 'Platform', 'Welcome']),
                'links' => json_encode([['label' => 'Complete your continuity plan', 'url' => 'https://aegis.devlet.tech/provider/continuity-plan']]),
                'media' => null, 'poll_question' => null, 'poll_options' => null, 'poll_closes_at' => null,
                'published_at' => $now->copy()->subMonths(6)->toDateTimeString(),
                'created_at'   => $now->copy()->subMonths(6)->toDateTimeString(),
                'updated_at'   => $now->copy()->subMonths(6)->toDateTimeString(),
            ],
            // 2. PLATFORM — feature update
            [
                'id' => 'np_platform_update', 'author_id' => $adminId,
                'post_type' => 'platform', 'role_visibility' => 'all', 'audience' => 'all',
                'published' => 1, 'pinned' => 0,
                'title' => 'Platform Update: Vault Search, CEU Tracker & Faster Load Times',
                'body' => "This week's release brings three major improvements:\n\n• **Vault Search** — full-text search across all uploaded documents\n• **CEU Tracker** — log credits, track deadlines, export your transcript\n• **Performance** — 40% faster page loads on mobile\n\nAs always, your feedback shapes our roadmap. Use the Support tab to submit requests.",
                'tags' => json_encode(['Platform', 'Updates', 'NewFeature']),
                'links' => json_encode([['label' => 'Read full release notes', 'url' => 'https://aegis.devlet.tech/changelog']]),
                'media' => null, 'poll_question' => null, 'poll_options' => null, 'poll_closes_at' => null,
                'published_at' => $now->copy()->subDays(4)->toDateTimeString(),
                'created_at'   => $now->copy()->subDays(4)->toDateTimeString(),
                'updated_at'   => $now->copy()->subDays(4)->toDateTimeString(),
            ],
            // 3. PROVIDER — long post with image
            [
                'id' => 'np_sarah_supervision', 'author_id' => $sarahId,
                'post_type' => 'provider', 'role_visibility' => 'all', 'audience' => 'all',
                'published' => 1, 'pinned' => 0,
                'title' => 'Tips for Trauma-Informed Supervision During Clinical Transitions',
                'body' => "After 12 years in the field, I've learned that the most overlooked element of clinical transitions is how they affect supervisee wellbeing.\n\nWhen a practitioner is suddenly unavailable, the ripple effects on supervisees can be profound — especially those working with trauma populations. Here are three strategies I've found invaluable:\n\n1. Establish a clear chain of supervisory coverage in your continuity plan.\n2. Brief your supervisees annually on what happens during a continuity event.\n3. Designate a peer supervisor they can reach immediately.\n\nI'd love to hear what others are doing. Has anyone built a supervisee briefing template they're willing to share?",
                'tags' => json_encode(['Supervision', 'TraumaInformedCare', 'ClinicalTransitions']),
                'links' => null,
                'media' => json_encode([
                    ['type' => 'image', 'url' => 'https://images.unsplash.com/photo-1573497491765-dccce02b29df?w=800&q=80', 'thumb' => 'https://images.unsplash.com/photo-1573497491765-dccce02b29df?w=400&q=60', 'name' => 'Clinical supervision session'],
                ]),
                'poll_question' => null, 'poll_options' => null, 'poll_closes_at' => null,
                'published_at' => $now->copy()->subDays(12)->toDateTimeString(),
                'created_at'   => $now->copy()->subDays(12)->toDateTimeString(),
                'updated_at'   => $now->copy()->subDays(10)->toDateTimeString(),
            ],
            // 4. POLL — active with votes
            [
                'id' => 'np_poll_challenges', 'author_id' => $adminId,
                'post_type' => 'poll', 'role_visibility' => 'all', 'audience' => 'all',
                'published' => 1, 'pinned' => 0,
                'title' => 'Poll: What is your biggest continuity planning challenge?',
                'body' => 'We want to understand the barriers preventing practitioners from maintaining active continuity plans. Your response helps us prioritize platform improvements.',
                'tags' => json_encode(['ContinuityPlanning', 'PracticeResilience']),
                'links' => null, 'media' => null,
                'poll_question' => 'What is your biggest continuity planning challenge?',
                'poll_options' => json_encode([
                    ['key' => 'time_constraints', 'label' => 'Not enough time to complete the plan'],
                    ['key' => 'cost_concerns',    'label' => 'Cost concerns'],
                    ['key' => 'finding_steward',  'label' => 'Difficulty finding a qualified steward'],
                    ['key' => 'tech_complexity',  'label' => 'Platform complexity'],
                ]),
                'poll_closes_at' => $now->copy()->addDays(14)->toDateTimeString(),
                'published_at' => $now->copy()->subDays(5)->toDateTimeString(),
                'created_at'   => $now->copy()->subDays(5)->toDateTimeString(),
                'updated_at'   => $now->copy()->subDays(5)->toDateTimeString(),
            ],
            // 5. QUESTION — community question
            [
                'id' => 'np_question_malpractice', 'author_id' => $davidId,
                'post_type' => 'question', 'role_visibility' => 'all', 'audience' => 'all',
                'published' => 1, 'pinned' => 0,
                'title' => 'Does your malpractice policy cover continuity events?',
                'body' => "I've been reviewing my malpractice coverage and realized I'm unclear on whether my policy covers liability during a continuity activation — specifically when my CS is seeing clients on my behalf.\n\nHas anyone navigated this? Would love to know what questions to ask my insurer. I'm with HPSO currently.",
                'tags' => json_encode(['Malpractice', 'Insurance', 'RiskManagement']),
                'links' => null, 'media' => null,
                'poll_question' => null, 'poll_options' => null, 'poll_closes_at' => null,
                'published_at' => $now->copy()->subDays(3)->toDateTimeString(),
                'created_at'   => $now->copy()->subDays(3)->toDateTimeString(),
                'updated_at'   => $now->copy()->subDays(3)->toDateTimeString(),
            ],
            // 6. RESOURCE — with link and image
            [
                'id' => 'np_resource_checklist', 'author_id' => $marcusId,
                'post_type' => 'resource', 'role_visibility' => 'all', 'audience' => 'all',
                'published' => 1, 'pinned' => 0,
                'title' => 'Checklist: Activating a Continuity Plan — Step by Step',
                'body' => "Based on my experience responding to practitioner incidents, I've developed a step-by-step activation checklist that covers everything from client notification to vault access.\n\nIt's broken into three phases:\n• Phase 1: Immediate (0–24 hrs) — vault access, client contact, licensing board notification\n• Phase 2: Short-term (1–7 days) — referral coordination, insurance notification\n• Phase 3: Recovery (7–30 days) — records transfer, practice wind-down or handoff\n\nFeel free to download and adapt it for your practice.",
                'tags' => json_encode(['ContinuityPlanning', 'Resources', 'Checklist', 'Activation']),
                'links' => json_encode([
                    ['label' => 'Download Activation Checklist (PDF)', 'url' => 'https://aegis.devlet.tech/resources/activation-checklist.pdf'],
                    ['label' => 'NASW Continuity Standards Reference', 'url' => 'https://www.socialworkers.org'],
                ]),
                'media' => json_encode([
                    ['type' => 'image', 'url' => 'https://images.unsplash.com/photo-1484480974693-6ca0a78fb36b?w=800&q=80', 'thumb' => 'https://images.unsplash.com/photo-1484480974693-6ca0a78fb36b?w=400&q=60', 'name' => 'Continuity checklist preview'],
                ]),
                'poll_question' => null, 'poll_options' => null, 'poll_closes_at' => null,
                'published_at' => $now->copy()->subDays(18)->toDateTimeString(),
                'created_at'   => $now->copy()->subDays(18)->toDateTimeString(),
                'updated_at'   => $now->copy()->subDays(18)->toDateTimeString(),
            ],
            // 7. MILESTONE — achievement
            [
                'id' => 'np_milestone_100', 'author_id' => $adminId,
                'post_type' => 'milestone', 'role_visibility' => 'all', 'audience' => 'all',
                'published' => 1, 'pinned' => 0,
                'title' => '🎉 Aegis Reaches 100 Verified Practitioners',
                'body' => "We are proud to announce that Aegis has reached 100 verified practitioners across California, Texas, and New York.\n\nThis milestone reflects the growing recognition that practice continuity is not optional — it's a professional and ethical responsibility.\n\nThank you to every practitioner who joined early and trusted us with something this important.",
                'tags' => json_encode(['Milestone', 'Community', 'Growth']),
                'links' => null,
                'media' => json_encode([
                    ['type' => 'image', 'url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&q=80', 'thumb' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=400&q=60', 'name' => 'Team celebrating milestone'],
                ]),
                'poll_question' => null, 'poll_options' => null, 'poll_closes_at' => null,
                'published_at' => $now->copy()->subDays(7)->toDateTimeString(),
                'created_at'   => $now->copy()->subDays(7)->toDateTimeString(),
                'updated_at'   => $now->copy()->subDays(7)->toDateTimeString(),
            ],
            // 8. EVENT ANNOUNCEMENT post (different from news_events — just a post type)
            [
                'id' => 'np_event_announce', 'author_id' => $adminId,
                'post_type' => 'event', 'role_visibility' => 'all', 'audience' => 'all',
                'published' => 1, 'pinned' => 0,
                'title' => 'Live Q&A: Ethics in Telehealth — This Thursday',
                'body' => "Join us Thursday, July 10 at 10:00 AM PT for a live 90-minute session with NASW faculty.\n\nTopics covered:\n• Ethical boundaries in remote therapy settings\n• HIPAA-compliant video platform selection\n• Crisis protocol in telehealth\n• Documentation requirements\n\n1.5 CEUs awarded. Free for all Aegis members.",
                'tags' => json_encode(['Telehealth', 'Ethics', 'CEU', 'LiveEvent']),
                'links' => json_encode([['label' => 'Register Free on Zoom', 'url' => 'https://zoom.us/j/ethics-live']]),
                'media' => null, 'poll_question' => null, 'poll_options' => null, 'poll_closes_at' => null,
                'published_at' => $now->copy()->subDays(2)->toDateTimeString(),
                'created_at'   => $now->copy()->subDays(2)->toDateTimeString(),
                'updated_at'   => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            // 9. POLL — providers only, best practice
            [
                'id' => 'np_poll_supervision', 'author_id' => $sarahId,
                'post_type' => 'poll', 'role_visibility' => 'practitioner', 'audience' => 'providers',
                'published' => 1, 'pinned' => 0,
                'title' => 'Quick Poll: How often do you review your continuity plan?',
                'body' => 'Curious where practitioners stand on plan review frequency. There\'s no wrong answer!',
                'tags' => json_encode(['ContinuityPlanning', 'BestPractices']),
                'links' => null, 'media' => null,
                'poll_question' => 'How often do you review your continuity plan?',
                'poll_options' => json_encode([
                    ['key' => 'annually',   'label' => 'Annually'],
                    ['key' => 'biannual',   'label' => 'Every 6 months'],
                    ['key' => 'quarterly',  'label' => 'Quarterly'],
                    ['key' => 'never',      'label' => "Honestly, I haven't yet"],
                ]),
                'poll_closes_at' => $now->copy()->addDays(7)->toDateTimeString(),
                'published_at' => $now->copy()->subDays(1)->toDateTimeString(),
                'created_at'   => $now->copy()->subDays(1)->toDateTimeString(),
                'updated_at'   => $now->copy()->subDays(1)->toDateTimeString(),
            ],
            // 10. QUESTION — with multiple images
            [
                'id' => 'np_question_telehealth', 'author_id' => $mariaId,
                'post_type' => 'question', 'role_visibility' => 'all', 'audience' => 'all',
                'published' => 1, 'pinned' => 0,
                'title' => 'Managing Professional Boundaries in Telehealth',
                'body' => "The shift to telehealth has created new boundary challenges that in-person practice didn't present.\n\nHere are the key boundary areas I discuss with every supervisee:\n• Session environment (home office vs. bedroom)\n• Appearance and professional presentation\n• Technical interruptions and their management\n• Emergency protocols when client is remote\n\nI'd love to hear how others are handling these in their practices — especially those working with high-acuity clients.",
                'tags' => json_encode(['Telehealth', 'Boundaries', 'BestPractices', 'Supervision']),
                'links' => null,
                'media' => json_encode([
                    ['type' => 'image', 'url' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=800&q=80', 'thumb' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=60', 'name' => 'Remote therapy setup'],
                    ['type' => 'image', 'url' => 'https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?w=800&q=80', 'thumb' => 'https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?w=400&q=60', 'name' => 'Technology in therapy'],
                ]),
                'poll_question' => null, 'poll_options' => null, 'poll_closes_at' => null,
                'published_at' => $now->copy()->subDays(25)->toDateTimeString(),
                'created_at'   => $now->copy()->subDays(25)->toDateTimeString(),
                'updated_at'   => $now->copy()->subDays(25)->toDateTimeString(),
            ],
            // 11. RESOURCE — stewards only
            [
                'id' => 'np_resource_cs_guide', 'author_id' => $marcusId,
                'post_type' => 'resource', 'role_visibility' => 'continuity_steward', 'audience' => 'stewards',
                'published' => 1, 'pinned' => 0,
                'title' => 'CS Field Guide: First 72 Hours of an Activation',
                'body' => "A field guide for continuity stewards responding to an unexpected practitioner absence.\n\nThe first 72 hours are the most critical. This guide walks through exactly what to do, in order, with templates and scripts for each step.\n\nKeep this bookmarked — you'll want it fast if you ever need it.",
                'tags' => json_encode(['ContinuityPlanning', 'StewardGuide', 'Activation', 'Resources']),
                'links' => json_encode([
                    ['label' => 'CS Field Guide PDF', 'url' => 'https://aegis.devlet.tech/resources/cs-field-guide.pdf'],
                    ['label' => 'Client Notification Script Templates', 'url' => 'https://aegis.devlet.tech/resources/notification-scripts.docx'],
                ]),
                'media' => null, 'poll_question' => null, 'poll_options' => null, 'poll_closes_at' => null,
                'published_at' => $now->copy()->subDays(20)->toDateTimeString(),
                'created_at'   => $now->copy()->subDays(20)->toDateTimeString(),
                'updated_at'   => $now->copy()->subDays(20)->toDateTimeString(),
            ],
            // 12. MILESTONE — personal
            [
                'id' => 'np_milestone_sarah', 'author_id' => $sarahId,
                'post_type' => 'milestone', 'role_visibility' => 'all', 'audience' => 'all',
                'published' => 1, 'pinned' => 0,
                'title' => '10 Years Licensed — Reflecting on Practice Sustainability',
                'body' => "This week marks my 10th anniversary as a licensed clinical social worker. I've been reflecting on what I wish I knew at year one.\n\nTop of the list: practice continuity planning. I didn't have a plan until year 7. That gap was a risk I didn't fully understand.\n\nIf you're in your early years — please don't wait. A basic plan takes 2 hours. The peace of mind is worth every minute.",
                'tags' => json_encode(['Milestone', 'PracticeWisdom', 'ContinuityPlanning']),
                'links' => null, 'media' => null,
                'poll_question' => null, 'poll_options' => null, 'poll_closes_at' => null,
                'published_at' => $now->copy()->subDays(9)->toDateTimeString(),
                'created_at'   => $now->copy()->subDays(9)->toDateTimeString(),
                'updated_at'   => $now->copy()->subDays(9)->toDateTimeString(),
            ],
        ];

        foreach ($posts as $p) {
            $p['deleted_at'] = null;
            DB::table('news_posts')->updateOrInsert(['id' => $p['id']], $p);
        }

        // ── comments ─────────────────────────────────────────────────────────
        DB::table('news_comments')->whereIn('post_id', array_column($posts, 'id'))->delete();

        $comments = [
            ['post_id' => 'np_sarah_supervision',   'author_id' => $davidId,  'body' => 'Really valuable insights. The point about supervisee wellbeing resonates — I had two supervisees in crisis during a continuity event last year. The parallel process was intense.'],
            ['post_id' => 'np_sarah_supervision',   'author_id' => $mariaId,  'body' => 'Thank you for sharing this. I\'ve bookmarked it for our next group supervision session.'],
            ['post_id' => 'np_sarah_supervision',   'author_id' => $marcusId, 'body' => 'As a CS, I see the impact of this gap constantly. Practitioners who\'ve briefed their supervisees make activation so much smoother.'],
            ['post_id' => 'np_question_malpractice','author_id' => $marcusId, 'body' => 'Great question. Most malpractice policies DO cover this if the CS is properly designated in the plan. Call your carrier, mention "continuity of care coverage" and "designated successor." HPSO specifically has a provision for this.'],
            ['post_id' => 'np_question_malpractice','author_id' => $sarahId,  'body' => 'I asked HPSO exactly this question six months ago. They confirmed coverage for designated stewards. The key word in your policy is "covered activities undertaken by licensed designees." Worth a 15-min call.'],
            ['post_id' => 'np_resource_checklist',  'author_id' => $sarahId,  'body' => 'Saving this immediately. The Phase 1 / Phase 2 / Phase 3 breakdown is exactly what I was looking for.'],
            ['post_id' => 'np_resource_checklist',  'author_id' => $mariaId,  'body' => 'Marcus this is outstanding. Is it okay if I adapt this for our group practice?'],
            ['post_id' => 'np_poll_challenges',     'author_id' => $davidId,  'body' => 'Voted time constraints — between clinical hours, documentation, and CE requirements, finding time to review the plan keeps slipping.'],
            ['post_id' => 'np_milestone_100',       'author_id' => $sarahId,  'body' => 'Congratulations to the whole team! Proud to be one of the early practitioners.'],
            ['post_id' => 'np_question_telehealth', 'author_id' => $davidId,  'body' => "Great list Maria. I'd add: session start/end rituals are even more important virtually — they help hold the therapeutic frame when the physical container isn't there."],
            ['post_id' => 'np_question_telehealth', 'author_id' => $sarahId,  'body' => 'The emergency protocol piece is underrated. I built a simple "crisis card" my clients keep visible during sessions — location, nearest ER, local crisis line. Changed everything for me.'],
        ];

        foreach ($comments as $c) {
            DB::table('news_comments')->insert([
                'id'         => 'nc_' . Str::lower(Str::random(12)),
                'post_id'    => $c['post_id'],
                'author_id'  => $c['author_id'],
                'body'       => $c['body'],
                'created_at' => $now->copy()->subHours(rand(1, 240))->toDateTimeString(),
                'updated_at' => $now->copy()->subHours(rand(1, 240))->toDateTimeString(),
                'deleted_at' => null,
            ]);
        }

        // ── reactions ─────────────────────────────────────────────────────────
        DB::table('news_reactions')->whereIn('post_id', array_column($posts, 'id'))->delete();

        $reactions = [
            ['post_id' => 'np_platform_welcome',   'user_id' => $sarahId,  'reaction' => 'like'],
            ['post_id' => 'np_platform_welcome',   'user_id' => $davidId,  'reaction' => 'like'],
            ['post_id' => 'np_platform_welcome',   'user_id' => $mariaId,  'reaction' => 'like'],
            ['post_id' => 'np_sarah_supervision',  'user_id' => $davidId,  'reaction' => 'like'],
            ['post_id' => 'np_sarah_supervision',  'user_id' => $mariaId,  'reaction' => 'like'],
            ['post_id' => 'np_sarah_supervision',  'user_id' => $marcusId, 'reaction' => 'like'],
            ['post_id' => 'np_sarah_supervision',  'user_id' => $davidId,  'reaction' => 'save'],
            ['post_id' => 'np_resource_checklist', 'user_id' => $sarahId,  'reaction' => 'like'],
            ['post_id' => 'np_resource_checklist', 'user_id' => $mariaId,  'reaction' => 'like'],
            ['post_id' => 'np_resource_checklist', 'user_id' => $sarahId,  'reaction' => 'save'],
            ['post_id' => 'np_milestone_100',      'user_id' => $sarahId,  'reaction' => 'like'],
            ['post_id' => 'np_milestone_100',      'user_id' => $davidId,  'reaction' => 'like'],
            ['post_id' => 'np_milestone_sarah',    'user_id' => $davidId,  'reaction' => 'like'],
            ['post_id' => 'np_milestone_sarah',    'user_id' => $marcusId, 'reaction' => 'like'],
            ['post_id' => 'np_question_telehealth','user_id' => $sarahId,  'reaction' => 'like'],
            ['post_id' => 'np_event_announce',     'user_id' => $sarahId,  'reaction' => 'save'],
            ['post_id' => 'np_platform_update',    'user_id' => $sarahId,  'reaction' => 'like'],
            ['post_id' => 'np_platform_update',    'user_id' => $davidId,  'reaction' => 'like'],
        ];

        foreach ($reactions as $r) {
            try {
                DB::table('news_reactions')->insert([
                    'id' => 'nr_' . Str::lower(Str::random(12)),
                    'post_id' => $r['post_id'], 'user_id' => $r['user_id'],
                    'reaction' => $r['reaction'],
                    'created_at' => $now->copy()->subHours(rand(1, 200))->toDateTimeString(),
                ]);
            } catch (\Throwable) {} // skip duplicates
        }

        // ── poll votes ────────────────────────────────────────────────────────
        DB::table('news_poll_votes')->whereIn('post_id', ['np_poll_challenges', 'np_poll_supervision'])->delete();
        $votes = [
            ['post_id' => 'np_poll_challenges', 'user_id' => $sarahId,  'option_key' => 'time_constraints'],
            ['post_id' => 'np_poll_challenges', 'user_id' => $davidId,  'option_key' => 'cost_concerns'],
            ['post_id' => 'np_poll_challenges', 'user_id' => $mariaId,  'option_key' => 'finding_steward'],
            ['post_id' => 'np_poll_supervision','user_id' => $marcusId, 'option_key' => 'annually'],
        ];
        foreach ($votes as $v) {
            DB::table('news_poll_votes')->insert([
                'id' => 'nv_' . Str::lower(Str::random(12)),
                'post_id' => $v['post_id'], 'user_id' => $v['user_id'],
                'option_key' => $v['option_key'],
                'created_at' => $now->copy()->subDays(rand(1, 5))->toDateTimeString(),
            ]);
        }

        // ── trending topics ───────────────────────────────────────────────────
        DB::table('news_trending_topics')->truncate();
        foreach ([
            ['#ContinuityPlanning', 42], ['#TraumaInformedCare', 31],
            ['#PracticeResilience', 28], ['#Telehealth', 24], ['#ClinicalSupervision', 19],
        ] as [$topic, $score]) {
            DB::table('news_trending_topics')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(), 'topic' => $topic, 'score' => $score,
                'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString(),
            ]);
        }

        // ── news_library_items ────────────────────────────────────────────────
        $library = [
            ['id' => 'nl_licensing_guide',  'title' => 'State-by-State Licensing Board Notification Requirements', 'category' => 'compliance', 'file_ref' => 'library/licensing_board_guide.pdf',        'role_visibility' => 'all',               'sort_order' => 1],
            ['id' => 'nl_hipaa_templates',  'title' => 'HIPAA-Compliant Client Notification Templates',            'category' => 'templates',  'file_ref' => 'library/hipaa_notification_templates.docx', 'role_visibility' => 'practitioner',      'sort_order' => 2],
            ['id' => 'nl_cs_checklist',     'title' => 'CS Incident Activation Checklist',                         'category' => 'operations', 'file_ref' => 'library/cs_activation_checklist.pdf',      'role_visibility' => 'continuity_steward','sort_order' => 3],
        ];
        foreach ($library as $l) {
            DB::table('news_library_items')->updateOrInsert(['id' => $l['id']], array_merge($l, [
                'published' => 1, 'url' => null,
                'created_at' => $now->copy()->subMonths(3)->toDateTimeString(),
                'updated_at' => $now->copy()->subMonths(3)->toDateTimeString(),
                'deleted_at' => null,
            ]));
        }

        // ── news_events ───────────────────────────────────────────────────────
        $events = [
            ['id' => 'ne_webinar_continuity', 'title' => 'Aegis Platform Webinar — Getting Started', 'description' => 'Monthly onboarding webinar. Ideal for newly licensed practitioners.', 'location' => 'Virtual — Zoom', 'category' => 'webinar', 'ceu_credits' => 1.5, 'is_free' => 1, 'price_cents' => 0, 'rsvp_url' => 'https://zoom.us/j/aegis-demo', 'rsvps_json' => json_encode([$sarahId => ['status' => 'going', 'at' => $now->copy()->subDays(3)->toIso8601String()]]), 'organizer' => "Aegis / MA'AT Practice Firm", 'status' => 'approved', 'role_visibility' => 'all', 'published' => 1, 'starts_at' => $now->copy()->addDays(7)->setTime(14, 0)->toDateTimeString(), 'ends_at' => $now->copy()->addDays(7)->setTime(15, 30)->toDateTimeString(), 'created_at' => $now->copy()->subDays(10)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(3)->toDateTimeString()],
            ['id' => 'ne_today_ethics', 'title' => 'Ethics in Telehealth — Live Q&A with NASW Faculty', 'description' => 'A live 90-minute session covering ethical boundaries in remote therapy, HIPAA considerations, and crisis protocol. Q&A with licensed NASW faculty.', 'location' => 'Virtual — Zoom', 'category' => 'webinar', 'ceu_credits' => 1.5, 'is_free' => 1, 'price_cents' => 0, 'rsvp_url' => null, 'rsvps_json' => json_encode([]), 'organizer' => 'NASW California', 'status' => 'approved', 'role_visibility' => 'all', 'published' => 1, 'starts_at' => $now->copy()->setTime(10, 0)->toDateTimeString(), 'ends_at' => $now->copy()->setTime(11, 30)->toDateTimeString(), 'created_at' => $now->copy()->subDays(5)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(1)->toDateTimeString()],
            ['id' => 'ne_trauma_workshop', 'title' => 'Trauma-Informed Supervision Workshop', 'description' => 'An intensive day-long workshop for licensed clinical supervisors. 6 CEU credits awarded.', 'location' => 'Los Angeles, CA — Marriott Downtown', 'category' => 'workshop', 'ceu_credits' => 6.0, 'is_free' => 0, 'price_cents' => 19900, 'rsvp_url' => null, 'rsvps_json' => json_encode([$sarahId => ['status' => 'going', 'at' => $now->copy()->subDays(5)->toIso8601String()]]), 'organizer' => 'SoCal Trauma Collective', 'status' => 'approved', 'role_visibility' => 'all', 'published' => 1, 'starts_at' => $now->copy()->addDays(1)->setTime(9, 0)->toDateTimeString(), 'ends_at' => $now->copy()->addDays(1)->setTime(17, 0)->toDateTimeString(), 'created_at' => $now->copy()->subDays(20)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(5)->toDateTimeString()],
            ['id' => 'ne_summit_2026', 'title' => 'Integrative Mental Health Summit 2026', 'description' => 'A three-day conference for psychologists, LMFTs, LCSWs, and LPCCs.', 'location' => 'San Francisco, CA — Moscone Center', 'category' => 'conference', 'ceu_credits' => 12.0, 'is_free' => 0, 'price_cents' => 49900, 'rsvp_url' => null, 'rsvps_json' => json_encode([]), 'organizer' => 'CAMFT', 'status' => 'approved', 'role_visibility' => 'all', 'published' => 1, 'starts_at' => $now->copy()->addDays(5)->setTime(8, 30)->toDateTimeString(), 'ends_at' => $now->copy()->addDays(7)->setTime(17, 0)->toDateTimeString(), 'created_at' => $now->copy()->subMonths(2)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(1)->toDateTimeString()],
            ['id' => 'ne_networking_bay', 'title' => 'Aegis Practitioner Networking — Bay Area Chapter', 'description' => 'An informal evening mixer for Aegis members in the Bay Area.', 'location' => 'San Francisco, CA', 'category' => 'networking', 'ceu_credits' => 0, 'is_free' => 1, 'price_cents' => 0, 'rsvp_url' => null, 'rsvps_json' => json_encode([]), 'organizer' => 'Aegis Community', 'status' => 'approved', 'role_visibility' => 'practitioner', 'published' => 1, 'starts_at' => $now->copy()->addDays(14)->setTime(18, 30)->toDateTimeString(), 'ends_at' => $now->copy()->addDays(14)->setTime(21, 0)->toDateTimeString(), 'created_at' => $now->copy()->subDays(7)->toDateTimeString(), 'updated_at' => $now->copy()->subDays(7)->toDateTimeString()],
        ];

        foreach ($events as $ev) {
            $ev['deleted_at'] = null;
            DB::table('news_events')->updateOrInsert(['id' => $ev['id']], $ev);
        }
    }
}
