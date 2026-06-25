<?php
// Domain 9 — practitioner_payment_methods — UC-PRV-141

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practitioner_payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('practitioner_id', 36)->index();
            $table->string('label', 64)->nullable();
            $table->string('brand', 32)->nullable();
            $table->string('last4', 4)->nullable();
            $table->string('stripe_pm_id', 64)->nullable();
            $table->tinyInteger('is_default')->default(0)->index();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioner_payment_methods');
    }
};
