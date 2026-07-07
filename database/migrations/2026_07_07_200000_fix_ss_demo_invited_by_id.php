<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Set invited_by_id for demo SS users so their public profiles are accessible
 * to their linked practitioner.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ss_linda → p_sarah
        DB::table('users')
            ->where('id', 'ss_linda')
            ->whereNull('invited_by_id')
            ->update(['invited_by_id' => 'p_sarah']);

        // ss_james → p_david
        DB::table('users')
            ->where('id', 'ss_james')
            ->whereNull('invited_by_id')
            ->update(['invited_by_id' => 'p_david']);
    }

    public function down(): void
    {
        DB::table('users')
            ->whereIn('id', ['ss_linda', 'ss_james'])
            ->update(['invited_by_id' => null]);
    }
};
