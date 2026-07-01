<?php

// Adds 'support' as a dedicated event_type enum value so ticket/feedback
// activity entries are filterable via ?event_type=support in Activity.vue.
// Previously support events landed under 'system' (the catch-all fallback).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE activity_events MODIFY event_type ENUM(
                'message','task','document','incident','vault','compliance','attestation',
                'payment','account','system','support','referral','news','event',
                'practitioner_unresponsive_flagged','job_postings'
            ) NOT NULL");
        }

        // Backfill: re-stamp existing support-module rows to the new event_type
        DB::table('activity_events')
            ->where('module', 'support')
            ->update(['event_type' => 'support']);
    }

    public function down(): void
    {
        // Move support rows back to system before shrinking the enum
        DB::table('activity_events')
            ->where('event_type', 'support')
            ->update(['event_type' => 'system']);

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE activity_events MODIFY event_type ENUM(
                'message','task','document','incident','vault','compliance','attestation',
                'payment','account','system','referral','news','event',
                'practitioner_unresponsive_flagged','job_postings'
            ) NOT NULL");
        }
    }
};
