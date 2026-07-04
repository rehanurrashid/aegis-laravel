<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * bp_engagement_requests — persists hire/quote/consultation requests submitted
 * from the public business profile page before a formal BpJob/contract exists.
 *
 * Lifecycle:  pending → accepted (BP created a BpJob from it) | declined | expired
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_engagement_requests', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('bp_id', 36)->index();         // the business partner being requested
            $table->char('practitioner_id', 36)->index(); // the practitioner making the request
            $table->string('type', 30);                  // 'hire' | 'quote' | 'consultation'
            $table->string('engagement_type', 80)->nullable(); // Fixed-Scope, Hourly, Retainer (hire only)
            $table->date('start_date')->nullable();
            $table->string('duration', 100)->nullable();
            $table->string('budget', 100)->nullable();
            $table->string('payment_terms', 80)->nullable();
            $table->text('notes')->nullable();
            $table->string('service', 100)->nullable();  // quote: service requested
            $table->string('size', 100)->nullable();     // quote: practice size
            $table->string('timeline', 100)->nullable(); // quote: timeline
            $table->boolean('urgent')->default(false);
            $table->string('meeting_type', 80)->nullable(); // consultation: Video/Phone/etc.
            $table->string('preferred_time', 50)->nullable();
            $table->string('meeting_duration', 50)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->text('agenda')->nullable();
            $table->boolean('include_nda')->default(false);
            $table->boolean('require_baa')->default(false);
            $table->boolean('auto_contract')->default(false);
            $table->string('status', 30)->default('pending'); // pending | accepted | declined | expired
            $table->text('response_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('bp_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('practitioner_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_engagement_requests');
    }
};
