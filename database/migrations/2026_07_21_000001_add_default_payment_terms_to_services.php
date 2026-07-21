<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'default_payment_structure')) {
                $table->enum('default_payment_structure', ['full_upfront', 'split', 'full_on_completion'])
                    ->default('split')
                    ->after('price_type')
                    ->comment('Default payment structure for sessions booked on this service');
            }
            if (!Schema::hasColumn('services', 'default_upfront_percentage')) {
                $table->tinyInteger('default_upfront_percentage')->unsigned()->default(30)
                    ->after('default_payment_structure')
                    ->comment('Upfront % when structure=split (1–99)');
            }
            if (!Schema::hasColumn('services', 'default_terms_note')) {
                $table->text('default_terms_note')->nullable()
                    ->after('default_upfront_percentage')
                    ->comment('Optional provider note on payment terms shown to client at request time');
            }
            if (!Schema::hasColumn('services', 'allow_completion_only')) {
                $table->boolean('allow_completion_only')->default(false)
                    ->after('default_terms_note')
                    ->comment('When true, clients may propose full_on_completion terms');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            foreach (['default_payment_structure', 'default_upfront_percentage', 'default_terms_note', 'allow_completion_only'] as $col) {
                if (Schema::hasColumn('services', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
