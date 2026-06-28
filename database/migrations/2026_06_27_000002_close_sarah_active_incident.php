<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Demo data fix — set inc_sarah_active to 'closed' so p_sarah's
 * dashboard loads without the IncidentBanner firing during testing.
 * The banner is correct behaviour; this just makes the seed state
 * match a "normal" dashboard view for development.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('critical_incidents')
            ->where('id', 'inc_sarah_active')
            ->update([
                'status'    => 'closed',
                'closed_at' => now()->subDay(),
            ]);
    }

    public function down(): void
    {
        DB::table('critical_incidents')
            ->where('id', 'inc_sarah_active')
            ->update([
                'status'    => 'active',
                'closed_at' => null,
            ]);
    }
};
