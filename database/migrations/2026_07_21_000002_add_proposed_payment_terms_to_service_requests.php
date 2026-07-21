<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('service_requests', 'proposed_payment_structure')) {
                $table->enum('proposed_payment_structure', ['full_upfront', 'split', 'full_on_completion'])
                    ->default('split')
                    ->after('responded_at')
                    ->comment('Payment structure proposed by client at request time');
            }
            if (!Schema::hasColumn('service_requests', 'proposed_upfront_percentage')) {
                $table->tinyInteger('proposed_upfront_percentage')->unsigned()->default(30)
                    ->after('proposed_payment_structure');
            }
            if (!Schema::hasColumn('service_requests', 'proposed_terms_note')) {
                $table->text('proposed_terms_note')->nullable()
                    ->after('proposed_upfront_percentage');
            }
            if (!Schema::hasColumn('service_requests', 'terms_source')) {
                $table->enum('terms_source', ['provider_default', 'client_proposed'])
                    ->default('provider_default')
                    ->after('proposed_terms_note')
                    ->comment('Whether client accepted provider defaults or proposed their own');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            foreach (['proposed_payment_structure', 'proposed_upfront_percentage', 'proposed_terms_note', 'terms_source'] as $col) {
                if (Schema::hasColumn('service_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
