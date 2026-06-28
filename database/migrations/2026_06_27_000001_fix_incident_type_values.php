<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix seeder data — `incident_type` values written before the IncidentType enum
 * was finalised. Maps legacy strings to canonical enum backing values.
 *
 * Legacy value            → Canonical value
 * short_term_incapacitation → incapacitation
 * missing_person            → missing
 * extended_leave            → extended_absence
 */
return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'short_term_incapacitation' => 'incapacitation',
            'missing_person'            => 'missing',
            'extended_leave'            => 'extended_absence',
        ];

        foreach ($map as $old => $new) {
            DB::table('critical_incidents')
                ->where('incident_type', $old)
                ->update(['incident_type' => $new]);

            DB::table('plan_incident_configs')
                ->where('incident_type', $old)
                ->update(['incident_type' => $new]);
        }
    }

    public function down(): void
    {
        $map = [
            'incapacitation' => 'short_term_incapacitation',
            'missing'        => 'missing_person',
            'extended_absence' => 'extended_leave',
        ];

        foreach ($map as $canonical => $legacy) {
            DB::table('critical_incidents')
                ->where('incident_type', $canonical)
                ->update(['incident_type' => $legacy]);

            DB::table('plan_incident_configs')
                ->where('incident_type', $canonical)
                ->update(['incident_type' => $legacy]);
        }
    }
};
