<?php
// Extends bp_jobs + bp_proposals to support the full "Support & Services" provider UI
// (job-postings.php parity): post-job wizard fields, applicant pipeline stages, notes.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bp_jobs', function (Blueprint $table) {
            $table->string('job_type', 20)->nullable()->after('category');
            $table->string('experience_level', 20)->nullable()->after('job_type');
            $table->string('partner_type_pref', 20)->nullable()->after('experience_level');
            $table->json('tags')->nullable()->after('description');
            $table->text('certifications')->nullable()->after('partner_type_pref');
            $table->boolean('requires_hipaa')->default(false)->after('certifications');
            $table->boolean('requires_nda')->default(false)->after('requires_hipaa');
            $table->boolean('requires_baa')->default(false)->after('requires_nda');
            $table->date('application_deadline')->nullable()->after('requires_baa');
            $table->unsignedInteger('max_applicants')->default(0)->after('application_deadline');
            $table->string('payment_method', 40)->nullable()->after('max_applicants');
            $table->string('billing_frequency', 40)->nullable()->after('payment_method');
            $table->string('perks', 255)->nullable()->after('billing_frequency');
            $table->boolean('is_featured')->default(false)->after('is_urgent');
            $table->text('internal_notes')->nullable()->after('perks');
            $table->date('start_date')->nullable()->after('internal_notes');
        });

        Schema::table('bp_proposals', function (Blueprint $table) {
            $table->string('pipeline_stage', 20)->default('new')->after('status')->index();
            $table->text('decline_reason')->nullable()->after('responded_at');
            $table->text('internal_notes')->nullable()->after('decline_reason');
            $table->string('interview_type', 20)->nullable()->after('internal_notes');
            $table->timestamp('interview_at')->nullable()->after('interview_type');
        });
    }

    public function down(): void
    {
        Schema::table('bp_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'job_type', 'experience_level', 'partner_type_pref', 'tags', 'certifications',
                'requires_hipaa', 'requires_nda', 'requires_baa', 'application_deadline',
                'max_applicants', 'payment_method', 'billing_frequency', 'perks',
                'is_featured', 'internal_notes', 'start_date',
            ]);
        });
        Schema::table('bp_proposals', function (Blueprint $table) {
            $table->dropColumn(['pipeline_stage', 'decline_reason', 'internal_notes', 'interview_type', 'interview_at']);
        });
    }
};
