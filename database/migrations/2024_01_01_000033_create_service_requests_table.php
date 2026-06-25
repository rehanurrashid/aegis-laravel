<?php
// Domain 7 — service_requests — UC-PRV-124; EMAIL T58/T59

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('service_id', 36)->index();
            $table->char('practitioner_id', 36)->index();
            $table->char('inquirer_id', 36)->nullable()->index();
            $table->string('inquirer_name', 191)->nullable();
            $table->string('inquirer_email', 191)->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'accepted', 'declined'])->default('new')->index();
            $table->string('response_note', 255)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['practitioner_id', 'status'], 'ix_svcreq_pract_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
