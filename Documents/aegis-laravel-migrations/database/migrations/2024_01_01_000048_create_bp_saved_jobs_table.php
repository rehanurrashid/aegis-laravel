<?php
// Domain 8 — bp_saved_jobs — UC-BP save_job

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_saved_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('bp_id', 36)->index();
            $table->char('job_id', 36)->index();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->unique(['bp_id', 'job_id'], 'uq_saved_job');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_saved_jobs');
    }
};
