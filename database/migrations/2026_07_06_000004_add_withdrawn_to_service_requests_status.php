<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `service_requests` MODIFY COLUMN `status` ENUM('new','accepted','declined','withdrawn') NOT NULL DEFAULT 'new'");
    }

    public function down(): void
    {
        // Convert any withdrawn rows back to declined before narrowing the enum
        DB::statement("UPDATE `service_requests` SET `status` = 'declined' WHERE `status` = 'withdrawn'");
        DB::statement("ALTER TABLE `service_requests` MODIFY COLUMN `status` ENUM('new','accepted','declined') NOT NULL DEFAULT 'new'");
    }
};
