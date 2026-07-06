<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('service_sessions', function (Blueprint $table) {
            $table->string('timezone', 64)->default('America/New_York')->after('scheduled_at');
        });
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('preferred_timezone', 64)->nullable()->after('message');
            $table->string('preferred_date', 20)->nullable()->after('preferred_timezone');
            $table->string('preferred_time', 40)->nullable()->after('preferred_date');
        });
    }
    public function down(): void {
        Schema::table('service_sessions', fn($t) => $t->dropColumn('timezone'));
        Schema::table('service_requests', fn($t) => $t->dropColumn(['preferred_timezone','preferred_date','preferred_time']));
    }
};
