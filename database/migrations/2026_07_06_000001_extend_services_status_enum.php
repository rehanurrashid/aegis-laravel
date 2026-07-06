<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE services MODIFY COLUMN status ENUM('active','inactive','draft','paused','archived') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE services MODIFY COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
    }
};
