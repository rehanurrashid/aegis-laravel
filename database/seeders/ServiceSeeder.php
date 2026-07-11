<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Detect extra columns added by later migrations — seeder works with or without them
        $sessHasNotes    = Schema::hasColumn('service_sessions', 'session_summary');
        $sessHasTimezone = Schema::hasColumn('service_sessions', 'timezone');
        $sessHasCancel   = Schema::hasColumn('service_sessions', 'cancel_reason');
        $reqHasTimezone  = Schema::hasColumn('service_requests', 'preferred_timezone');

        // ── Services ──────────────────────────────────────────────────────
        $services = [
            ['id'=>'svc_sarah_sup_ind',  'practitioner_id'=>'p_sarah', 'title'=>'Individual Clinical Supervision',   'description'=>'Individual supervision for post-graduate therapists seeking licensure (LCSW, LPC, MFT). Case conceptualization, ethics, clinical skill development.', 'category'=>'supervision',        'price_cents'=>15000, 'price_type'=>'session', 'duration_min'=>50,   'format'=>'telehealth', 'availability'=>'open',    'availability_label'=>null,            'status'=>'active', 'is_public'=>1],
            ['id'=>'svc_sarah_sup_grp',  'practitioner_id'=>'p_sarah', 'title'=>'Group Clinical Supervision',         'description'=>'Bi-weekly group supervision for pre-licensed therapists. Max 6 participants. Peer consultation and professional development.',                      'category'=>'supervision',        'price_cents'=>8000,  'price_type'=>'session', 'duration_min'=>90,   'format'=>'telehealth', 'availability'=>'limited', 'availability_label'=>'3 spots left',  'status'=>'active', 'is_public'=>1],
            ['id'=>'svc_sarah_consult',  'practitioner_id'=>'p_sarah', 'title'=>'Peer Case Consultation',             'description'=>'One-time or ongoing peer consultation for licensed clinicians. Complex cases, ethics dilemmas, and treatment-resistant presentations.',               'category'=>'consultation',       'price_cents'=>20000, 'price_type'=>'session', 'duration_min'=>60,   'format'=>'both',       'availability'=>'open',    'availability_label'=>null,            'status'=>'active', 'is_public'=>1],
            ['id'=>'svc_sarah_training', 'practitioner_id'=>'p_sarah', 'title'=>'Trauma-Informed DBT Training',       'description'=>'3-session training covering trauma-informed DBT applications. CEU eligible.',                                                                        'category'=>'training',           'price_cents'=>45000, 'price_type'=>'fixed',   'duration_min'=>null, 'format'=>'telehealth', 'availability'=>'limited', 'availability_label'=>'By Request',    'status'=>'active', 'is_public'=>1],
            ['id'=>'svc_sarah_coaching', 'practitioner_id'=>'p_sarah', 'title'=>'Practice Growth Coaching',           'description'=>'Strategic coaching for therapists building private practice.',                                                                                       'category'=>'coaching',           'price_cents'=>25000, 'price_type'=>'session', 'duration_min'=>60,   'format'=>'telehealth', 'availability'=>'open',    'availability_label'=>null,            'status'=>'draft',  'is_public'=>0],
            ['id'=>'svc_sarah_cont',     'practitioner_id'=>'p_sarah', 'title'=>'Practice Continuity Consultation',   'description'=>'Consultation for practitioners setting up or reviewing their continuity plan.',                                                                      'category'=>'practice_continuity','price_cents'=>30000, 'price_type'=>'session', 'duration_min'=>90,   'format'=>'telehealth', 'availability'=>'open',    'availability_label'=>null,            'status'=>'paused', 'is_public'=>0],
            ['id'=>'svc_maria_couples',  'practitioner_id'=>'p_maria', 'title'=>'Couples Therapy',                    'description'=>'Structured session for partners working on communication, conflict, or rebuilding trust.',                                                           'category'=>'consultation',       'price_cents'=>22000, 'price_type'=>'session', 'duration_min'=>60,   'format'=>'both',       'availability'=>'open',    'availability_label'=>null,            'status'=>'active', 'is_public'=>1],
            ['id'=>'svc_maria_family',   'practitioner_id'=>'p_maria', 'title'=>'Family Systems Session',              'description'=>'Whole-family session addressing dynamics, roles, and communication patterns.',                                                                       'category'=>'consultation',       'price_cents'=>28000, 'price_type'=>'session', 'duration_min'=>90,   'format'=>'in_person',  'availability'=>'limited', 'availability_label'=>'Limited Spots', 'status'=>'active', 'is_public'=>1],
        ];

        foreach ($services as $s) {
            DB::table('services')->updateOrInsert(['id' => $s['id']], array_merge([
                'deleted_at' => null,
                'created_at' => $now->copy()->subMonths(4)->toDateTimeString(),
                'updated_at' => $now->copy()->subMonths(1)->toDateTimeString(),
            ], $s));
        }

        // ── Service Requests ──────────────────────────────────────────────
        $reqBase = ['inquirer_name' => null, 'inquirer_email' => null, 'deleted_at' => null];
        if ($reqHasTimezone) {
            $reqBase = array_merge($reqBase, ['preferred_timezone' => null, 'preferred_date' => null, 'preferred_time' => null]);
        }

        $requests = [
            // 2 pending (new) — visible in Pending Requests tab
            ['id'=>'sreq_linda_cons',  'service_id'=>'svc_sarah_consult', 'practitioner_id'=>'p_sarah', 'inquirer_id'=>'ss_linda',  'message'=>'Requesting consultation on a complex trauma case with dissociative features. Client has DID and I want a second perspective on treatment approach.', 'status'=>'new',      'response_note'=>null, 'responded_at'=>null, 'preferred_date'=>$now->copy()->addDays(7)->toDateString(),  'preferred_time'=>'14:00', 'preferred_timezone'=>'America/New_York', 'created_at'=>$now->copy()->subHours(5)->toDateTimeString(),  'updated_at'=>$now->copy()->subHours(5)->toDateTimeString()],
            ['id'=>'sreq_marcus_sup',  'service_id'=>'svc_sarah_sup_ind', 'practitioner_id'=>'p_sarah', 'inquirer_id'=>'cs_marcus', 'message'=>'Looking for individual supervision as I work toward my LCSW. 18 months post-grad, focus on trauma and crisis.', 'status'=>'new',      'response_note'=>null, 'responded_at'=>null, 'preferred_date'=>$now->copy()->addDays(10)->toDateString(), 'preferred_time'=>'10:00', 'preferred_timezone'=>'America/Chicago',  'created_at'=>$now->copy()->subDays(1)->toDateTimeString(),   'updated_at'=>$now->copy()->subDays(1)->toDateTimeString()],
            // 2 accepted — have sessions in bookings tab
            ['id'=>'sreq_david_acc',   'service_id'=>'svc_sarah_sup_ind', 'practitioner_id'=>'p_sarah', 'inquirer_id'=>'p_david',   'message'=>'Interested in ongoing supervision for my DBT cases.',                                    'status'=>'accepted', 'response_note'=>null, 'responded_at'=>$now->copy()->subWeeks(3)->toDateTimeString(), 'created_at'=>$now->copy()->subWeeks(4)->toDateTimeString(),  'updated_at'=>$now->copy()->subWeeks(3)->toDateTimeString()],
            ['id'=>'sreq_david_cons',  'service_id'=>'svc_sarah_consult', 'practitioner_id'=>'p_sarah', 'inquirer_id'=>'p_david',   'message'=>'Need peer consultation on a treatment-resistant case.',                                   'status'=>'accepted', 'response_note'=>null, 'responded_at'=>$now->copy()->subWeeks(1)->toDateTimeString(), 'created_at'=>$now->copy()->subWeeks(2)->toDateTimeString(),  'updated_at'=>$now->copy()->subWeeks(1)->toDateTimeString()],
            // 1 declined — visible in activity/history
            ['id'=>'sreq_maria_dec',   'service_id'=>'svc_sarah_sup_grp', 'practitioner_id'=>'p_sarah', 'inquirer_id'=>'p_maria',   'message'=>null,                                                                                      'status'=>'declined', 'response_note'=>'Group is currently at capacity.', 'responded_at'=>$now->copy()->subWeeks(2)->toDateTimeString(), 'created_at'=>$now->copy()->subWeeks(3)->toDateTimeString(), 'updated_at'=>$now->copy()->subWeeks(2)->toDateTimeString()],
        ];

        foreach ($requests as $r) {
            DB::table('service_requests')->updateOrInsert(['id' => $r['id']], array_merge($reqBase, $r));
        }

        // ── Service Sessions ──────────────────────────────────────────────
        // Structured with _meta for optional columns — assembled below per detected schema
        $sessions = [
            // ── July 2026 (current month) — 4 sessions, $50K completed revenue ──
            [
                'id'=>'ss_july_1', 'service_request_id'=>'sreq_david_acc',  'service_id'=>'svc_sarah_sup_ind', 'practitioner_id'=>'p_sarah', 'client_id'=>'p_david',
                'status'=>'completed', 'scheduled_at'=>$now->copy()->subDays(5)->setTime(10,0)->toDateTimeString(), 'completed_at'=>$now->copy()->subDays(5)->setTime(11,0)->toDateTimeString(), 'amount_cents'=>15000,
                '_tz'=>'America/New_York', '_summary'=>'Reviewed DBT skills application with a complex trauma client. Discussed emotion regulation strategies.', '_items'=>'Bring two case examples next session.', '_share'=>1, '_cancel'=>null,
            ],
            [
                'id'=>'ss_july_2', 'service_request_id'=>'sreq_david_cons', 'service_id'=>'svc_sarah_consult', 'practitioner_id'=>'p_sarah', 'client_id'=>'p_david',
                'status'=>'completed', 'scheduled_at'=>$now->copy()->subDays(2)->setTime(14,0)->toDateTimeString(), 'completed_at'=>$now->copy()->subDays(2)->setTime(15,0)->toDateTimeString(), 'amount_cents'=>20000,
                '_tz'=>'America/New_York', '_summary'=>'Peer consultation on treatment-resistant case. Discussed EMDR integration options and phased approach.', '_items'=>null, '_share'=>0, '_cancel'=>null,
            ],
            [
                'id'=>'ss_july_3', 'service_request_id'=>'sreq_david_acc',  'service_id'=>'svc_sarah_sup_ind', 'practitioner_id'=>'p_sarah', 'client_id'=>'p_david',
                'status'=>'scheduled', 'scheduled_at'=>$now->copy()->addDays(3)->setTime(10,0)->toDateTimeString(), 'completed_at'=>null, 'amount_cents'=>15000,
                '_tz'=>'America/New_York', '_summary'=>null, '_items'=>null, '_share'=>0, '_cancel'=>null,
            ],
            [
                'id'=>'ss_july_4', 'service_request_id'=>'sreq_david_acc',  'service_id'=>'svc_sarah_sup_ind', 'practitioner_id'=>'p_sarah', 'client_id'=>'p_david',
                'status'=>'scheduled', 'scheduled_at'=>$now->copy()->addDays(10)->setTime(14,0)->toDateTimeString(), 'completed_at'=>null, 'amount_cents'=>15000,
                '_tz'=>'America/Chicago', '_summary'=>null, '_items'=>null, '_share'=>0, '_cancel'=>null,
            ],
            // ── Previous months ──
            [
                'id'=>'ss_prev_1', 'service_request_id'=>'sreq_david_acc',  'service_id'=>'svc_sarah_sup_ind', 'practitioner_id'=>'p_sarah', 'client_id'=>'p_david',
                'status'=>'completed', 'scheduled_at'=>$now->copy()->subWeeks(5)->setTime(10,0)->toDateTimeString(), 'completed_at'=>$now->copy()->subWeeks(5)->setTime(11,0)->toDateTimeString(), 'amount_cents'=>15000,
                '_tz'=>'America/New_York', '_summary'=>'Reviewed three complex trauma cases. Good progress on affect regulation framing.', '_items'=>'Review van der Kolk ch.12 before next session.', '_share'=>1, '_cancel'=>null,
            ],
            [
                'id'=>'ss_prev_2', 'service_request_id'=>'sreq_david_acc',  'service_id'=>'svc_sarah_sup_ind', 'practitioner_id'=>'p_sarah', 'client_id'=>'p_david',
                'status'=>'completed', 'scheduled_at'=>$now->copy()->subWeeks(7)->setTime(10,0)->toDateTimeString(), 'completed_at'=>$now->copy()->subWeeks(7)->setTime(11,0)->toDateTimeString(), 'amount_cents'=>15000,
                '_tz'=>'America/New_York', '_summary'=>null, '_items'=>null, '_share'=>0, '_cancel'=>null,
            ],
            [
                'id'=>'ss_prev_3', 'service_request_id'=>'sreq_david_acc',  'service_id'=>'svc_sarah_sup_grp', 'practitioner_id'=>'p_sarah', 'client_id'=>'cs_marcus',
                'status'=>'cancelled', 'scheduled_at'=>$now->copy()->subWeeks(3)->setTime(14,0)->toDateTimeString(), 'completed_at'=>null, 'amount_cents'=>8000,
                '_tz'=>'America/New_York', '_summary'=>null, '_items'=>null, '_share'=>0, '_cancel'=>'Provider unavailable — schedule conflict',
            ],
        ];

        foreach ($sessions as $raw) {
            $tz     = $raw['_tz'];
            $sum    = $raw['_summary'];
            $items  = $raw['_items'];
            $share  = $raw['_share'];
            $cancel = $raw['_cancel'];
            unset($raw['_tz'], $raw['_summary'], $raw['_items'], $raw['_share'], $raw['_cancel']);

            $row = array_merge([
                'deleted_at' => null,
                'created_at' => $now->copy()->subWeeks(2)->toDateTimeString(),
                'updated_at' => $now->copy()->toDateTimeString(),
            ], $raw);

            if ($sessHasTimezone) $row['timezone']                  = $tz;
            if ($sessHasNotes)    $row['session_summary']           = $sum;
            if ($sessHasNotes)    $row['session_action_items']      = $items;
            if ($sessHasNotes)    $row['share_notes_with_client']   = $share;
            if ($sessHasCancel)   $row['cancel_reason']             = $cancel;

            // Wave 1 payment columns
            $sessHasPayStatus = Schema::hasColumn('service_sessions', 'payment_status');
            $sessHasDepCols   = Schema::hasColumn('service_sessions', 'deposit_cents');
            if ($sessHasPayStatus) {
                $row['payment_status'] = match($raw['status']) {
                    'completed'  => 'paid',
                    'cancelled'  => 'unpaid',
                    'scheduled'  => 'unpaid',
                    default      => 'unpaid',
                };
            }
            if ($sessHasDepCols) {
                $amt = (int) ($raw['amount_cents'] ?? 0);
                $dep = (int) floor($amt * 0.30);
                $bal = $amt - $dep;
                $row['original_amount_cents']   = $amt;
                $row['negotiated_amount_cents']  = null;
                $row['total_refunded_cents']     = 0;
                if ($raw['status'] === 'completed') {
                    $row['deposit_cents']       = $dep;
                    $row['deposit_charge_id']   = 'pi_demo_dep_' . substr($raw['id'], -8);
                    $row['deposit_paid_at']     = $raw['completed_at'] ?? $now->copy()->subDays(1)->toDateTimeString();
                    $row['balance_cents']       = $bal;
                    $row['balance_charge_id']   = 'pi_demo_bal_' . substr($raw['id'], -8);
                    $row['balance_paid_at']     = $raw['completed_at'] ?? $now->copy()->subDays(1)->toDateTimeString();
                } else {
                    $row['deposit_cents']       = 0;
                    $row['deposit_charge_id']   = null;
                    $row['deposit_paid_at']     = null;
                    $row['balance_cents']       = 0;
                    $row['balance_charge_id']   = null;
                    $row['balance_paid_at']     = null;
                }
            }

            DB::table('service_sessions')->updateOrInsert(['id' => $row['id']], $row);
        }

        // ── Outgoing requests FROM Sarah TO other providers ─────────────
        $outgoing = [
            // 1. Pending — Sarah requested Maria's Family Systems Session
            [
                'id'              => 'sreq_sarah_out_1',
                'service_id'      => 'svc_maria_family',
                'practitioner_id' => 'p_maria',
                'inquirer_id'     => 'p_sarah',
                'message'         => 'I have a client who would benefit from family systems work. Looking to refer and coordinate care.',
                'status'          => 'new',
                'response_note'   => null,
                'responded_at'    => null,
                'created_at'      => $now->copy()->subDays(3)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(3)->toDateTimeString(),
            ],
            // 2. Accepted — Sarah requested Maria's Couples Therapy (has a session booked)
            [
                'id'              => 'sreq_sarah_out_2',
                'service_id'      => 'svc_maria_couples',
                'practitioner_id' => 'p_maria',
                'inquirer_id'     => 'p_sarah',
                'message'         => 'Looking to book a couples session for personal professional development around attachment dynamics.',
                'status'          => 'accepted',
                'response_note'   => 'Happy to work with you — I have availability next week.',
                'responded_at'    => $now->copy()->subDays(5)->toDateTimeString(),
                'created_at'      => $now->copy()->subDays(8)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(5)->toDateTimeString(),
            ],
            // 3. Declined — Sarah requested Maria's Group Supervision (Maria at capacity)
            [
                'id'              => 'sreq_sarah_out_3',
                'service_id'      => 'svc_maria_family',
                'practitioner_id' => 'p_maria',
                'inquirer_id'     => 'p_sarah',
                'message'         => 'Interested in a family systems consultation for an ongoing case.',
                'status'          => 'declined',
                'response_note'   => 'Currently at capacity for new consultations — please try again next month.',
                'responded_at'    => $now->copy()->subWeeks(2)->toDateTimeString(),
                'created_at'      => $now->copy()->subWeeks(3)->toDateTimeString(),
                'updated_at'      => $now->copy()->subWeeks(2)->toDateTimeString(),
            ],
        ];

        foreach ($outgoing as $r) {
            $r = array_merge(['inquirer_name' => null, 'inquirer_email' => null, 'deleted_at' => null], $r);
            DB::table('service_requests')->updateOrInsert(['id' => $r['id']], $r);
        }

        // ── Session where Sarah is the CLIENT (from sreq_sarah_out_2) ────
        $clientSessionRow = [
            'id'                 => 'ss_sarah_client_1',
            'service_request_id' => 'sreq_sarah_out_2',
            'service_id'         => 'svc_maria_couples',
            'practitioner_id'    => 'p_maria',
            'client_id'          => 'p_sarah',
            'status'             => 'scheduled',
            'scheduled_at'       => $now->copy()->addDays(4)->setTime(10, 0)->toDateTimeString(),
            'completed_at'       => null,
            'amount_cents'       => 22000,
            'deleted_at'         => null,
            'created_at'         => $now->copy()->subDays(5)->toDateTimeString(),
            'updated_at'         => $now->copy()->subDays(5)->toDateTimeString(),
        ];

        if ($sessHasTimezone) $clientSessionRow['timezone']              = 'America/Chicago';
        if ($sessHasNotes)    $clientSessionRow['session_summary']       = null;
        if ($sessHasNotes)    $clientSessionRow['session_action_items']  = null;
        if ($sessHasNotes)    $clientSessionRow['share_notes_with_client'] = 0;
        if ($sessHasCancel)   $clientSessionRow['cancel_reason']         = null;

        // ── Wave 1 payment columns ────────────────────────────────────────────
        $sessHasPaymentStatus = Schema::hasColumn('service_sessions', 'payment_status');
        $sessHasDepositCols   = Schema::hasColumn('service_sessions', 'deposit_cents');

        if ($sessHasPaymentStatus) $clientSessionRow['payment_status']          = 'unpaid';
        if ($sessHasDepositCols) {
            $clientSessionRow['original_amount_cents']    = 22000;
            $clientSessionRow['negotiated_amount_cents']  = null;
            $clientSessionRow['deposit_cents']            = 0;
            $clientSessionRow['deposit_charge_id']        = null;
            $clientSessionRow['deposit_paid_at']          = null;
            $clientSessionRow['balance_cents']            = 0;
            $clientSessionRow['balance_charge_id']        = null;
            $clientSessionRow['balance_paid_at']          = null;
            $clientSessionRow['total_refunded_cents']     = 0;
        }

        DB::table('service_sessions')->updateOrInsert(['id' => 'ss_sarah_client_1'], $clientSessionRow);

        // ── p_david payment method (needed for completeSession to record payment_method_id) ──
        DB::table('practitioner_payment_methods')->updateOrInsert(
            ['id' => 'ppm_david_visa'],
            [
                'id'              => 'ppm_david_visa',
                'practitioner_id' => 'p_david',
                'label'           => 'Visa ending 4444',
                'brand'           => 'visa',
                'last4'           => '4444',
                'stripe_pm_id'    => 'pm_demo_david_visa',
                'is_default'      => 1,
                'created_at'      => $now->copy()->subMonths(4)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(4)->toDateTimeString(),
            ]
        );

        // ── PractitionerPayment rows for completed sessions ──────────────
        // These represent payments david (client) made to sarah (practitioner) for completed sessions.
        // In production these are created by ServiceService::completeSession(); seeded here for demo history.
        $hasPpTransfer = Schema::hasColumn('practitioner_payments', 'stripe_transfer_id');

        $completedPayments = [
            [
                'id'                   => 'pp_ss_july_1',
                'session_id'           => 'ss_july_1',
                'practitioner_id'      => 'p_sarah',
                'payment_method_id'    => 'ppm_david_visa',
                'kind'                 => 'service_session',
                'amount_cents'         => 15000,
                'currency'             => 'USD',
                'status'               => 'paid',
                'payment_method_label' => 'Visa ending 4444',
                'stripe_charge_id'     => 'ch_demo_july_1',
                'paid_at'              => $now->copy()->subDays(5)->setTime(11, 0)->toDateTimeString(),
                'created_at'           => $now->copy()->subDays(5)->setTime(11, 0)->toDateTimeString(),
                'updated_at'           => $now->copy()->subDays(5)->setTime(11, 0)->toDateTimeString(),
            ],
            [
                'id'                   => 'pp_ss_july_2',
                'session_id'           => 'ss_july_2',
                'practitioner_id'      => 'p_sarah',
                'payment_method_id'    => 'ppm_david_visa',
                'kind'                 => 'service_session',
                'amount_cents'         => 20000,
                'currency'             => 'USD',
                'status'               => 'paid',
                'payment_method_label' => 'Visa ending 4444',
                'stripe_charge_id'     => 'ch_demo_july_2',
                'paid_at'              => $now->copy()->subDays(2)->setTime(15, 0)->toDateTimeString(),
                'created_at'           => $now->copy()->subDays(2)->setTime(15, 0)->toDateTimeString(),
                'updated_at'           => $now->copy()->subDays(2)->setTime(15, 0)->toDateTimeString(),
            ],
            [
                'id'                   => 'pp_ss_prev_1',
                'session_id'           => 'ss_prev_1',
                'practitioner_id'      => 'p_sarah',
                'payment_method_id'    => 'ppm_david_visa',
                'kind'                 => 'service_session',
                'amount_cents'         => 15000,
                'currency'             => 'USD',
                'status'               => 'paid',
                'payment_method_label' => 'Visa ending 4444',
                'stripe_charge_id'     => 'ch_demo_prev_1',
                'paid_at'              => $now->copy()->subWeeks(5)->setTime(11, 0)->toDateTimeString(),
                'created_at'           => $now->copy()->subWeeks(5)->setTime(11, 0)->toDateTimeString(),
                'updated_at'           => $now->copy()->subWeeks(5)->setTime(11, 0)->toDateTimeString(),
            ],
            [
                'id'                   => 'pp_ss_prev_2',
                'session_id'           => 'ss_prev_2',
                'practitioner_id'      => 'p_sarah',
                'payment_method_id'    => 'ppm_david_visa',
                'kind'                 => 'service_session',
                'amount_cents'         => 15000,
                'currency'             => 'USD',
                'status'               => 'paid',
                'payment_method_label' => 'Visa ending 4444',
                'stripe_charge_id'     => 'ch_demo_prev_2',
                'paid_at'              => $now->copy()->subWeeks(7)->setTime(11, 0)->toDateTimeString(),
                'created_at'           => $now->copy()->subWeeks(7)->setTime(11, 0)->toDateTimeString(),
                'updated_at'           => $now->copy()->subWeeks(7)->setTime(11, 0)->toDateTimeString(),
            ],
        ];

        foreach ($completedPayments as $pp) {
            $row = $pp;
            unset($row['session_id']); // not a real column — just for our reference
            if ($hasPpTransfer) {
                $row['stripe_transfer_id'] = 'tr_demo_' . substr($pp['id'], -6);
            }
            DB::table('practitioner_payments')->updateOrInsert(['id' => $pp['id']], $row);
        }

        // ── Services Profile meta for p_sarah (updateOrInsert to fix corrupted rows) ──
        $metas = [
            ['service_bio',         'I offer clinical supervision, peer consultation, and specialized training to support therapists in building confidence and competence. My approach is collaborative, strengths-based, and rooted in evidence-based practice.', 'string'],
            ['service_headline',    'Board-Approved Clinical Supervisor | Trauma & DBT Specialist', 'string'],
            ['service_specialties', json_encode(['Trauma', 'DBT', 'Complex PTSD', 'Personality Disorders']), 'json'],
            ['years_experience',    '14', 'string'],
        ];

        foreach ($metas as [$key, $val, $type]) {
            DB::table('user_meta')->updateOrInsert(
                ['user_id' => 'p_sarah', 'meta_key' => $key],
                [
                    'id'         => DB::table('user_meta')->where('user_id', 'p_sarah')->where('meta_key', $key)->value('id') ?? 'um_' . Str::lower(Str::random(12)),
                    'meta_value' => $val,
                    'meta_type'  => $type,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
