<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE complaints MODIFY COLUMN category ENUM(
            'support_ticket','feedback','complaint','bug','feature_request','billing','other'
        ) NOT NULL DEFAULT 'support_ticket'");

        DB::statement("ALTER TABLE complaints MODIFY COLUMN submission_channel ENUM(
            'ticket','in_app','email','fab','feedback_button','contextual_questionnaire','free_form'
        ) NOT NULL DEFAULT 'ticket'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE complaints MODIFY COLUMN category ENUM(
            'support_ticket','feedback','complaint'
        ) NOT NULL DEFAULT 'support_ticket'");

        DB::statement("ALTER TABLE complaints MODIFY COLUMN submission_channel ENUM(
            'ticket','feedback_button','contextual_questionnaire','free_form'
        ) NOT NULL DEFAULT 'ticket'");
    }
};
