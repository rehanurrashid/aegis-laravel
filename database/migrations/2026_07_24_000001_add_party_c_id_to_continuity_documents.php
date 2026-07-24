<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('continuity_documents', function (Blueprint $table) {
            $table->string('party_c_id', 36)->nullable()->after('party_b_id');
        });
    }

    public function down(): void
    {
        Schema::table('continuity_documents', function (Blueprint $table) {
            $table->dropColumn('party_c_id');
        });
    }
};
