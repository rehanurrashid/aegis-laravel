<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vault_items', function (Blueprint $table) {
            if (!Schema::hasColumn('vault_items', 'description'))   $table->text('description')->nullable()->after('sub_label');
            if (!Schema::hasColumn('vault_items', 'file_name'))     $table->string('file_name', 255)->nullable()->after('description');
            if (!Schema::hasColumn('vault_items', 'file_size'))     $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
            if (!Schema::hasColumn('vault_items', 'mime_type'))     $table->string('mime_type', 100)->nullable()->after('file_size');
            if (!Schema::hasColumn('vault_items', 's3_key'))        $table->string('s3_key', 500)->nullable()->after('mime_type');
            if (!Schema::hasColumn('vault_items', 'client_location')) $table->string('client_location', 191)->nullable()->after('client_name');
            if (!Schema::hasColumn('vault_items', 'client_phone'))  $table->string('client_phone', 30)->nullable()->after('client_location');
            if (!Schema::hasColumn('vault_items', 'client_email'))  $table->string('client_email', 191)->nullable()->after('client_phone');
            if (!Schema::hasColumn('vault_items', 'client_service')) $table->string('client_service', 191)->nullable()->after('client_email');
            if (!Schema::hasColumn('vault_items', 'client_notes'))  $table->text('client_notes')->nullable()->after('client_service');
            if (!Schema::hasColumn('vault_items', 'issued_at'))     $table->date('issued_at')->nullable()->after('client_notes');
            if (!Schema::hasColumn('vault_items', 'expires_at'))    $table->date('expires_at')->nullable()->after('issued_at');
            if (!Schema::hasColumn('vault_items', 'tags'))          $table->json('tags')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('vault_items', function (Blueprint $table) {
            $table->dropColumn(['description','file_name','file_size','mime_type','s3_key',
                'client_location','client_phone','client_email','client_service','client_notes',
                'issued_at','expires_at','tags']);
        });
    }
};
