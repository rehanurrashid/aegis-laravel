<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── Services ──────────────────────────────────────────────────────
        $services = [
            // p_sarah — active
            ['id'=>'svc_sarah_sup_ind',  'practitioner_id'=>'p_sarah','title'=>'Individual Clinical Supervision',       'description'=>'Individual supervision for post-graduate therapists seeking licensure (LCSW, LPC, MFT). Focus on case conceptualization, ethics, and clinical skill development.','category'=>'supervision',        'price_cents'=>15000,'price_type'=>'session','duration_min'=>50, 'format'=>'telehealth','availability'=>'open',   'availability_label'=>null,           'status'=>'active', 'is_public'=>1],
            ['id'=>'svc_sarah_sup_grp',  'practitioner_id'=>'p_sarah','title'=>'Group Clinical Supervision',             'description'=>'Bi-weekly group supervision for pre-licensed therapists. Max 6 participants. Peer consultation and professional development.','category'=>'supervision',        'price_cents'=>8000, 'price_type'=>'session','duration_min'=>90, 'format'=>'telehealth','availability'=>'limited','availability_label'=>'3 spots left', 'status'=>'active', 'is_public'=>1],
            ['id'=>'svc_sarah_consult',  'practitioner_id'=>'p_sarah','title'=>'Peer Case Consultation',                 'description'=>'One-time or ongoing peer consultation for licensed clinicians. Complex cases, ethics dilemmas, and treatment-resistant presentations welcome.','category'=>'consultation',       'price_cents'=>20000,'price_type'=>'session','duration_min'=>60, 'format'=>'both',      'availability'=>'open',   'availability_label'=>null,           'status'=>'active', 'is_public'=>1],
            ['id'=>'svc_sarah_training', 'practitioner_id'=>'p_sarah','title'=>'Trauma-Informed DBT Training',           'description'=>'3-session training series covering trauma-informed applications of DBT. Suitable for teams or individual clinicians. CEU eligible.','category'=>'training',           'price_cents'=>45000,'price_type'=>'fixed',  'duration_min'=>null,'format'=>'telehealth','availability'=>'limited','availability_label'=>'By Request',   'status'=>'active', 'is_public'=>1],
            // p_sarah — draft
            ['id'=>'svc_sarah_coaching', 'practitioner_id'=>'p_sarah','title'=>'Practice Growth Coaching',               'description'=>'Strategic coaching for therapists building private practice. Niche development, referral network, and business model.','category'=>'coaching',           'price_cents'=>25000,'price_type'=>'session','duration_min'=>60, 'format'=>'telehealth','availability'=>'open',   'availability_label'=>null,           'status'=>'draft',  'is_public'=>0],
            // p_sarah — paused
            ['id'=>'svc_sarah_cont',     'practitioner_id'=>'p_sarah','title'=>'Practice Continuity Consultation',       'description'=>'Consultation for practitioners setting up or reviewing their continuity plan. Includes document review and recommendations.','category'=>'practice_continuity','price_cents'=>30000,'price_type'=>'session','duration_min'=>90, 'format'=>'telehealth','availability'=>'open',   'availability_label'=>null,           'status'=>'paused', 'is_public'=>0],
            // other providers
            ['id'=>'svc_maria_couples',  'practitioner_id'=>'p_maria','title'=>'Couples Therapy',                        'description'=>'Structured session for partners working on communication, conflict, or rebuilding trust.','category'=>'consultation',       'price_cents'=>22000,'price_type'=>'session','duration_min'=>60, 'format'=>'both',      'availability'=>'open',   'availability_label'=>null,           'status'=>'active', 'is_public'=>1],
            ['id'=>'svc_maria_family',   'practitioner_id'=>'p_maria','title'=>'Family Systems Session',                  'description'=>'Whole-family session addressing dynamics, roles, and communication patterns across generations.','category'=>'consultation',       'price_cents'=>28000,'price_type'=>'session','duration_min'=>90, 'format'=>'in_person', 'availability'=>'limited','availability_label'=>'Limited Spots', 'status'=>'active', 'is_public'=>1],
        ];

        foreach ($services as $s) {
            DB::table('services')->updateOrInsert(['id' => $s['id']], array_merge([
                'deleted_at' => null,
                'created_at' => $now->copy()->subMonths(4)->toDateTimeString(),
                'updated_at' => $now->copy()->subMonths(1)->toDateTimeString(),
            ], $s));
        }

        // ── Service Requests (incoming to p_sarah) ────────────────────────
        $requests = [
            ['id'=>'sreq_marcus_sup',   'service_id'=>'svc_sarah_sup_ind', 'practitioner_id'=>'p_sarah','inquirer_id'=>'cs_marcus','message'=>'Looking for individual supervision as I work toward my LCSW. I have 18 months post-grad and focus on trauma and crisis.','status'=>'new',     'response_note'=>null,                          'responded_at'=>null,                                     'created_at'=>$now->copy()->subDays(1)->toDateTimeString(),   'updated_at'=>$now->copy()->subDays(1)->toDateTimeString()],
            ['id'=>'sreq_linda_cons',   'service_id'=>'svc_sarah_consult', 'practitioner_id'=>'p_sarah','inquirer_id'=>'ss_linda', 'message'=>'Requesting consultation on a complex trauma case with dissociative features.','status'=>'new',     'response_note'=>null,                          'responded_at'=>null,                                     'created_at'=>$now->copy()->subHours(5)->toDateTimeString(),  'updated_at'=>$now->copy()->subHours(5)->toDateTimeString()],
            ['id'=>'sreq_david_acc',    'service_id'=>'svc_sarah_sup_ind', 'practitioner_id'=>'p_sarah','inquirer_id'=>'p_david',  'message'=>'Interested in ongoing supervision for my DBT cases.','status'=>'accepted','response_note'=>null,                          'responded_at'=>$now->copy()->subWeeks(3)->toDateTimeString(),'created_at'=>$now->copy()->subWeeks(4)->toDateTimeString(),  'updated_at'=>$now->copy()->subWeeks(3)->toDateTimeString()],
            ['id'=>'sreq_maria_dec',    'service_id'=>'svc_sarah_sup_grp', 'practitioner_id'=>'p_sarah','inquirer_id'=>'p_maria',  'message'=>null,'status'=>'declined','response_note'=>'Group is currently at capacity.','responded_at'=>$now->copy()->subWeeks(2)->toDateTimeString(),'created_at'=>$now->copy()->subWeeks(3)->toDateTimeString(),'updated_at'=>$now->copy()->subWeeks(2)->toDateTimeString()],
        ];

        foreach ($requests as $r) {
            DB::table('service_requests')->updateOrInsert(['id' => $r['id']], array_merge([
                'inquirer_name' => null, 'inquirer_email' => null, 'deleted_at' => null,
            ], $r));
        }

        // ── Service Sessions (p_sarah as supervisor) ──────────────────────
        $sessions = [
            ['id'=>'ss_david_1','service_request_id'=>'sreq_david_acc','service_id'=>'svc_sarah_sup_ind','practitioner_id'=>'p_sarah','client_id'=>'p_david','status'=>'scheduled', 'scheduled_at'=>$now->copy()->addDays(3)->toDateTimeString(), 'completed_at'=>null,                                             'amount_cents'=>15000,'session_summary'=>null,                                                                   'session_action_items'=>null,                                  'share_notes_with_client'=>0,'cancel_reason'=>null],
            ['id'=>'ss_david_2','service_request_id'=>'sreq_david_acc','service_id'=>'svc_sarah_sup_ind','practitioner_id'=>'p_sarah','client_id'=>'p_david','status'=>'completed', 'scheduled_at'=>$now->copy()->subWeeks(2)->toDateTimeString(),'completed_at'=>$now->copy()->subWeeks(2)->addHours(1)->toDateTimeString(),'amount_cents'=>15000,'session_summary'=>'Reviewed three complex trauma cases. Good progress on affect regulation framing.','session_action_items'=>'Review van der Kolk ch.12 before next session.','share_notes_with_client'=>1,'cancel_reason'=>null],
            ['id'=>'ss_david_3','service_request_id'=>'sreq_david_acc','service_id'=>'svc_sarah_sup_ind','practitioner_id'=>'p_sarah','client_id'=>'p_david','status'=>'completed', 'scheduled_at'=>$now->copy()->subWeeks(4)->toDateTimeString(),'completed_at'=>$now->copy()->subWeeks(4)->addHours(1)->toDateTimeString(),'amount_cents'=>15000,'session_summary'=>null,                                                                   'session_action_items'=>null,                                  'share_notes_with_client'=>0,'cancel_reason'=>null],
            ['id'=>'ss_marc_1', 'service_request_id'=>'sreq_david_acc','service_id'=>'svc_sarah_sup_grp','practitioner_id'=>'p_sarah','client_id'=>'cs_marcus','status'=>'cancelled','scheduled_at'=>$now->copy()->subWeeks(1)->toDateTimeString(),'completed_at'=>null,                                             'amount_cents'=>8000, 'session_summary'=>null,                                                                   'session_action_items'=>null,                                  'share_notes_with_client'=>0,'cancel_reason'=>'Provider unavailable — schedule conflict'],
        ];

        foreach ($sessions as $s) {
            DB::table('service_sessions')->updateOrInsert(['id' => $s['id']], array_merge([
                'deleted_at' => null,
                'created_at' => $now->copy()->subWeeks(3)->toDateTimeString(),
                'updated_at' => $now->copy()->subWeeks(1)->toDateTimeString(),
            ], $s));
        }

        // ── Services Profile meta for p_sarah ────────────────────────────
        $serviceMeta = [
            ['meta_key' => 'service_bio',         'meta_value' => 'I offer clinical supervision, peer consultation, and specialized training to support therapists in building confidence and competence. My approach is collaborative, strengths-based, and rooted in evidence-based practice.'],
            ['meta_key' => 'service_headline',     'meta_value' => 'Board-Approved Clinical Supervisor | Trauma & DBT Specialist'],
            ['meta_key' => 'service_specialties',  'meta_value' => json_encode(['Trauma', 'DBT', 'Complex PTSD', 'Personality Disorders'])],
            ['meta_key' => 'years_experience',     'meta_value' => '14'],
        ];
        foreach ($serviceMeta as $m) {
            $exists = DB::table('user_meta')
                ->where('user_id', 'p_sarah')
                ->where('meta_key', $m['meta_key'])
                ->exists();
            if (!$exists) {
                DB::table('user_meta')->insert([
                    'id'         => 'um_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12)),
                    'user_id'    => 'p_sarah',
                    'meta_key'   => $m['meta_key'],
                    'meta_value' => $m['meta_value'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
