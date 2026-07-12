<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 7 — add reminder_sent_at to bp_milestones.
 *
 * Tracks when the "auto-release reminder" email was last sent to the provider
 * for a submitted milestone. Prevents duplicate reminder emails from firing
 * on every daily cron run.
 *
 * Logic in MilestoneReviewReminderJob:
 *   - milestone.status = 'submitted'
 *   - milestone.auto_release_at is within next MILESTONE_REVIEW_REMINDER_HOURS hours
 *   - milestone.reminder_sent_at is NULL (never sent) or was sent > 24h ago
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bp_milestones', function (Blueprint $table) {
            if (!Schema::hasColumn('bp_milestones', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('auto_release_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bp_milestones', function (Blueprint $table) {
            if (Schema::hasColumn('bp_milestones', 'reminder_sent_at')) {
                $table->dropColumn('reminder_sent_at');
            }
        });
    }
};
