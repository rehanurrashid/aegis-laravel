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
            $table->integer('duration_min')->nullable()->after('price_type');
            $table->enum('format', ['telehealth', 'in_person', 'both'])->nullable()->after('duration_min');
            $table->enum('availability', ['open', 'limited'])->default('open')->after('format');
            $table->string('availability_label', 60)->nullable()->after('availability');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['duration_min', 'format', 'availability', 'availability_label']);
        });
    }
};
