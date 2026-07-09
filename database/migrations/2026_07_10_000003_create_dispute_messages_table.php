<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('dispute_id', 36)->index();
            $table->char('author_id', 36)->index();
            $table->enum('author_role', ['disputer', 'respondent', 'admin']);
            $table->text('body');
            $table->string('attachment_url', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['dispute_id', 'created_at'], 'ix_dispmsg_dispute_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_messages');
    }
};
