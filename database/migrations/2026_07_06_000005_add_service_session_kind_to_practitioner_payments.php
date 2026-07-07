<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `practitioner_payments` MODIFY COLUMN `kind` ENUM('subscription','maat_addon','cs_fee','bp_invoice','refund','service_session') NOT NULL DEFAULT 'subscription'");
    }

    public function down(): void
    {
        DB::statement("UPDATE `practitioner_payments` SET `kind` = 'refund' WHERE `kind` = 'service_session'");
        DB::statement("ALTER TABLE `practitioner_payments` MODIFY COLUMN `kind` ENUM('subscription','maat_addon','cs_fee','bp_invoice','refund') NOT NULL DEFAULT 'subscription'");
    }
};
