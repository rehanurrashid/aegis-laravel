<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `service_requests` MODIFY COLUMN `status` ENUM('new','countered','accepted','declined','withdrawn') NOT NULL DEFAULT 'new'");
    }

    public function down(): void
    {
        // Revert any countered rows to new before narrowing the enum
        DB::statement("UPDATE `service_requests` SET `status` = 'new' WHERE `status` = 'countered'");
        DB::statement("ALTER TABLE `service_requests` MODIFY COLUMN `status` ENUM('new','accepted','declined','withdrawn') NOT NULL DEFAULT 'new'");
    }
};
