<?php
// Adds 'job_postings' as a real event_type enum value so links can filter
// directly via ?event_type=job_postings (previously job-posting events were
// only distinguishable via the separate 'module' column, mapped to the
// generic 'task' event_type).

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
                'payment','account','system','referral','news','event',
                'practitioner_unresponsive_flagged','job_postings'
            ) NOT NULL");
        }
        // sqlite/pgsql: enum is enforced at the app layer only (no native ALTER
        // needed) — the column already accepts any string value at the DB level.

        // Backfill existing job-posting events (module='job_postings', currently
        // stored with the generic event_type='task') to the precise value.
        DB::table('activity_events')
            ->where('module', 'job_postings')
            ->update(['event_type' => 'job_postings']);
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        DB::table('activity_events')
            ->where('event_type', 'job_postings')
            ->update(['event_type' => 'task']);

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE activity_events MODIFY event_type ENUM(
                'message','task','document','incident','vault','compliance','attestation',
                'payment','account','system','referral','news','event',
                'practitioner_unresponsive_flagged'
            ) NOT NULL");
        }
    }
};
