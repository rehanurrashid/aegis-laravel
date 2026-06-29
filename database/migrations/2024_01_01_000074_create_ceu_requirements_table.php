<?php
// Domain 7 — ceu_requirements — UC-PRV-162
// Tracks practitioner CEU obligations per jurisdiction.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ceu_requirements', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('user_id', 36)->index();

            $table->string('jurisdiction', 191);            // e.g., "California — BBS (LMFT)"
            $table->unsignedSmallInteger('total_hours');
            $table->enum('cycle', ['annual', 'biannual'])->default('annual');
            $table->date('due_date')->nullable();
            $table->text('required_types')->nullable();      // e.g., "Ethics (6), Suicide Risk, Cultural Competency"

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['user_id', 'due_date'], 'ix_ceu_req_user_due');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ceu_requirements');
    }
};
