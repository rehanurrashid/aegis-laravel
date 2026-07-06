<?php
// Data-only migration moved to ServiceSeeder (runs after UserSeeder).
// Kept as a no-op so the migration record exists and rollback is clean.
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void {}
    public function down(): void {}
};
