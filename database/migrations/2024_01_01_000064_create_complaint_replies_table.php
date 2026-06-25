<?php
// Domain 12 — complaint_replies — UC-ADM-051,053,054; UC-XP-015

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('complaint_id', 36)->index();
            $table->char('author_id', 36)->index();
            $table->text('body');
            $table->tinyInteger('is_internal')->default(0)->index();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['complaint_id', 'created_at'], 'ix_reply_complaint_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_replies');
    }
};
