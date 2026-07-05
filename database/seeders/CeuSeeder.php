<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CeuSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ceu_entries columns: id, practitioner_id, title, provider_name, credit_hours, completed_on, expires_on, certificate_ref
        // Removed: user_id→practitioner_id, provider→provider_name, credits→credit_hours,
        //          completion_date→completed_on, expiry_date→expires_on, category (doesn't exist)
        $entries = [
            [
                'id'              => (string) Str::uuid(),
                'practitioner_id' => 'p_sarah',
                'title'           => 'EMDR Level II Training',
                'provider_name'   => 'EMDR Institute',
                'credit_hours'    => 14.0,
                'completed_on'    => $now->copy()->subMonths(4)->toDateString(),
                'expires_on'      => $now->copy()->addYears(2)->toDateString(),
                'certificate_ref' => null,
                'created_at'      => $now->copy()->subMonths(4)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(4)->toDateTimeString(),
            ],
            [
                'id'              => (string) Str::uuid(),
                'practitioner_id' => 'p_sarah',
                'title'           => 'Ethics in Telehealth Practice',
                'provider_name'   => 'NASW',
                'credit_hours'    => 3.0,
                'completed_on'    => $now->copy()->subMonths(2)->toDateString(),
                'expires_on'      => $now->copy()->addYears(3)->toDateString(),
                'certificate_ref' => null,
                'created_at'      => $now->copy()->subMonths(2)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(2)->toDateTimeString(),
            ],
            [
                'id'              => (string) Str::uuid(),
                'practitioner_id' => 'p_sarah',
                'title'           => 'Trauma-Informed Care Update',
                'provider_name'   => 'APA',
                'credit_hours'    => 6.0,
                'completed_on'    => $now->copy()->subMonths(8)->toDateString(),
                'expires_on'      => $now->copy()->addMonths(4)->toDateString(),
                'certificate_ref' => null,
                'created_at'      => $now->copy()->subMonths(8)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(8)->toDateTimeString(),
            ],
            [
                'id'              => (string) Str::uuid(),
                'practitioner_id' => 'p_maria',
                'title'           => 'Gottman Level III Certification',
                'provider_name'   => 'Gottman Institute',
                'credit_hours'    => 24.0,
                'completed_on'    => $now->copy()->subYears(1)->toDateString(),
                'expires_on'      => $now->copy()->addYears(2)->toDateString(),
                'certificate_ref' => null,
                'created_at'      => $now->copy()->subYears(1)->toDateTimeString(),
                'updated_at'      => $now->copy()->subYears(1)->toDateTimeString(),
            ],
            [
                'id'              => (string) Str::uuid(),
                'practitioner_id' => 'p_david',
                'title'           => 'CBT Fundamentals Refresher',
                'provider_name'   => 'ABCT',
                'credit_hours'    => 6.0,
                'completed_on'    => $now->copy()->subMonths(3)->toDateString(),
                'expires_on'      => $now->copy()->addYears(3)->toDateString(),
                'certificate_ref' => null,
                'created_at'      => $now->copy()->subMonths(3)->toDateTimeString(),
                'updated_at'      => $now->copy()->subMonths(3)->toDateTimeString(),
            ],
        ];

        foreach ($entries as $e) {
            DB::table('ceu_entries')->insert($e);
        }
    }
}
