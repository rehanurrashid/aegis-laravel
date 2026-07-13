<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE vault_items MODIFY zone ENUM('standard','emergency','credentials','roster') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vault_items MODIFY zone ENUM('credentials','roster','documents','instructions') NOT NULL");
    }
};
