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

        $services = [
            ['id' => 'svc_sarah_individual', 'practitioner_id' => 'p_sarah', 'title' => 'Individual Therapy',            'description' => 'One-on-one talk therapy for individuals working through anxiety, depression, or life transitions. Evidence-based, client-paced.', 'category' => 'therapy',      'price_cents' => 18000,  'price_type' => 'session', 'duration_min' => 50, 'format' => 'both',       'availability' => 'open',    'availability_label' => null,            'status' => 'active',   'is_public' => 0],
            ['id' => 'svc_sarah_emdr',        'practitioner_id' => 'p_sarah', 'title' => 'EMDR Intensive',                 'description' => 'Extended EMDR session for trauma processing. Best suited for clients already established in care.',                                     'category' => 'therapy',      'price_cents' => 30000,  'price_type' => 'session', 'duration_min' => 90, 'format' => 'in_person',  'availability' => 'limited', 'availability_label' => 'Limited Spots', 'status' => 'active',   'is_public' => 0],
            ['id' => 'svc_sarah_crisis',      'practitioner_id' => 'p_sarah', 'title' => 'Crisis Support Session',         'description' => 'Same-day session for clients experiencing an acute mental health crisis.',                                                                  'category' => 'crisis',       'price_cents' => 20000,  'price_type' => 'session', 'duration_min' => 45, 'format' => 'telehealth', 'availability' => 'open',    'availability_label' => null,            'status' => 'inactive', 'is_public' => 0],
            ['id' => 'svc_maria_couples',     'practitioner_id' => 'p_maria', 'title' => 'Couples Therapy',                'description' => 'Structured session for partners working on communication, conflict, or rebuilding trust.',                                                  'category' => 'therapy',      'price_cents' => 22000,  'price_type' => 'session', 'duration_min' => 60, 'format' => 'both',       'availability' => 'open',    'availability_label' => null,            'status' => 'active',   'is_public' => 1],
            ['id' => 'svc_maria_family',      'practitioner_id' => 'p_maria', 'title' => 'Family Systems Session',         'description' => 'Whole-family session addressing dynamics, roles, and communication patterns across generations.',                                            'category' => 'therapy',      'price_cents' => 28000,  'price_type' => 'session', 'duration_min' => 90, 'format' => 'in_person',  'availability' => 'limited', 'availability_label' => 'Limited Spots', 'status' => 'active',   'is_public' => 1],
            ['id' => 'svc_maria_intensive',   'practitioner_id' => 'p_maria', 'title' => 'Weekend Couples Retreat',        'description' => 'Two-day immersive retreat for couples seeking focused, accelerated work outside the weekly session model.',                                'category' => 'intensive',    'price_cents' => 450000, 'price_type' => 'fixed',   'duration_min' => null, 'format' => 'in_person',  'availability' => 'limited', 'availability_label' => 'By Request',    'status' => 'active',   'is_public' => 1],
            ['id' => 'svc_maria_premarital',  'practitioner_id' => 'p_maria', 'title' => 'Premarital Counseling Package',  'description' => 'Six-session package for couples preparing for marriage — communication, finances, expectations, and conflict styles.',                      'category' => 'therapy',      'price_cents' => 95000,  'price_type' => 'fixed',   'duration_min' => null, 'format' => 'both',       'availability' => 'open',    'availability_label' => '6 Sessions',    'status' => 'active',   'is_public' => 1],
            ['id' => 'svc_maria_consult',     'practitioner_id' => 'p_maria', 'title' => 'Free Consultation',              'description' => "Brief introductory call to see if we're a good fit before booking a full session.",                                                        'category' => 'consultation', 'price_cents' => 0,      'price_type' => 'inquiry', 'duration_min' => 20, 'format' => 'telehealth', 'availability' => 'open',    'availability_label' => null,            'status' => 'active',   'is_public' => 1],
        ];

        foreach ($services as $s) {
            $s = array_merge(['deleted_at' => null, 'created_at' => now()->subMonths(4)->toDateTimeString(), 'updated_at' => now()->subMonths(1)->toDateTimeString()], $s);
            DB::table('services')->updateOrInsert(['id' => $s['id']], $s);
        }

        // service_requests columns: id, service_id, practitioner_id, inquirer_id, inquirer_name, inquirer_email, message, status, response_note, responded_at, created_at, updated_at
        // Fixed: requester_id→inquirer_id, note→message
        // status ENUM: new|accepted|declined ('pending'→'new', 'completed'→'accepted')
        // practitioner_id is NOT NULL — must match the service's practitioner
        $requests = [
            [
                'id'              => (string) Str::uuid(),
                'service_id'      => 'svc_maria_couples',
                'practitioner_id' => 'p_maria',
                'inquirer_id'     => 'p_sarah',
                'message'         => 'Looking for a couples therapist for a client referral.',
                'status'          => 'new',
                'created_at'      => $now->copy()->subDays(2)->toDateTimeString(),
                'updated_at'      => $now->copy()->subDays(2)->toDateTimeString(),
            ],
            [
                'id'              => (string) Str::uuid(),
                'service_id'      => 'svc_maria_couples',
                'practitioner_id' => 'p_maria',
                'inquirer_id'     => 'p_david',
                'message'         => null,
                'status'          => 'accepted',
                'created_at'      => $now->copy()->subWeeks(3)->toDateTimeString(),
                'updated_at'      => $now->copy()->subWeeks(2)->toDateTimeString(),
            ],
            [
                'id'              => (string) Str::uuid(),
                'service_id'      => 'svc_maria_intensive',
                'practitioner_id' => 'p_maria',
                'inquirer_id'     => 'p_sarah',
                'message'         => 'Client completed the retreat program.',
                'status'          => 'accepted',
                'created_at'      => $now->copy()->subMonths(2)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(1)->toDateTimeString(),
            ],
        ];
        foreach ($requests as $r) {
            $r = array_merge(['inquirer_name' => null, 'inquirer_email' => null, 'response_note' => null, 'responded_at' => null, 'deleted_at' => null], $r);
            DB::table('service_requests')->insert($r);
        }

        // service_sessions columns: id, service_request_id, service_id, practitioner_id, client_id, status, scheduled_at, completed_at, amount_cents
        // Fixed: removed client_ref, session_date, duration_mins, notes (don't exist)
        // service_request_id is NOT NULL — use a placeholder lookup or null approach:
        // We use the david referral request — we need the inserted ID. Re-insert with fixed ID.
        $serviceRequestId = (string) Str::uuid();
        DB::table('service_requests')->insert([
            'id'              => $serviceRequestId,
            'service_id'      => 'svc_maria_couples',
            'practitioner_id' => 'p_maria',
            'inquirer_id'     => 'p_david',
            'status'          => 'accepted',
            'created_at'      => $now->copy()->subWeeks(2)->toDateTimeString(),
            'updated_at'      => $now->copy()->subWeeks(2)->toDateTimeString(),
        ]);

        DB::table('service_sessions')->insert([
            'id'                 => (string) Str::uuid(),
            'service_request_id' => $serviceRequestId,
            'service_id'         => 'svc_maria_couples',
            'practitioner_id'    => 'p_maria',
            'client_id'          => 'p_david',
            'status'             => 'scheduled',
            'scheduled_at'       => $now->copy()->addDays(5)->toDateTimeString(),
            'completed_at'       => null,
            'amount_cents'       => 22000,
            'created_at'         => $now->copy()->subWeeks(2)->toDateTimeString(),
            'updated_at'         => $now->copy()->subWeeks(2)->toDateTimeString(),
        ]);
    }
}
