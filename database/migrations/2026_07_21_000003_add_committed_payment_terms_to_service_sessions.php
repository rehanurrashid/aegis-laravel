<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('service_sessions', 'payment_structure')) {
                $table->enum('payment_structure', ['full_upfront', 'split', 'full_on_completion'])
                    ->default('split')
                    ->after('payment_status')
                    ->comment('Committed payment structure for this session');
            }
            if (!Schema::hasColumn('service_sessions', 'upfront_percentage')) {
                $table->tinyInteger('upfront_percentage')->unsigned()->default(30)
                    ->after('payment_structure');
            }
            if (!Schema::hasColumn('service_sessions', 'upfront_cents')) {
                $table->unsignedInteger('upfront_cents')->default(0)
                    ->after('upfront_percentage')
                    ->comment('Absolute upfront amount in cents (computed at bookSession time)');
            }
            if (!Schema::hasColumn('service_sessions', 'completion_cents')) {
                $table->unsignedInteger('completion_cents')->default(0)
                    ->after('upfront_cents')
                    ->comment('Remaining amount due at completion');
            }
            if (!Schema::hasColumn('service_sessions', 'terms_note')) {
                $table->text('terms_note')->nullable()
                    ->after('completion_cents');
            }
            if (!Schema::hasColumn('service_sessions', 'terms_source')) {
                $table->enum('terms_source', ['provider_default', 'client_proposed', 'provider_countered'])
                    ->default('provider_default')
                    ->after('terms_note');
            }
            if (!Schema::hasColumn('service_sessions', 'terms_agreed_at')) {
                $table->timestamp('terms_agreed_at')->nullable()
                    ->after('terms_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_sessions', function (Blueprint $table) {
            foreach ([
                'payment_structure', 'upfront_percentage', 'upfront_cents',
                'completion_cents', 'terms_note', 'terms_source', 'terms_agreed_at',
            ] as $col) {
                if (Schema::hasColumn('service_sessions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
