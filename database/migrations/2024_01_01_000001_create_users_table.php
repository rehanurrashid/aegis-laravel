<?php
// Domain 1 — users — UC-PRV-001..004,010,016,017,019; UC-CS-001..003; UC-SS-001; UC-XP-031; UC-ADM-020..029
// Merged: includes password + remember_token (formerly migration 000073 closure-pass).

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('role', ['practitioner', 'continuity_steward', 'support_steward', 'business_partner', 'admin'])->default('practitioner')->index();
            $table->string('display_name', 191);
            $table->string('credentials', 191)->nullable();
            $table->string('email', 191)->unique();
            $table->string('password', 255)->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('location', 191)->nullable();
            $table->string('organization', 191)->nullable();
            $table->string('avatar_initials', 4)->nullable();
            $table->string('title', 191)->nullable();
            $table->string('specialty', 191)->nullable();
            $table->text('bio')->nullable();
            $table->string('slug', 191)->nullable()->unique();
            $table->timestamp('slug_locked_at')->nullable();
            $table->tinyInteger('practitioner_public')->default(1)->index();
            $table->tinyInteger('cs_public')->default(0)->index();
            $table->tinyInteger('business_partner_public')->default(1)->index();
            $table->enum('tier', ['access', 'practice'])->nullable()->index();
            $table->tinyInteger('services_mode')->default(0)->index();
            $table->tinyInteger('maat_addon')->default(0)->index();
            $table->string('payment_model', 40)->nullable();
            $table->enum('cs_account_type', ['invited', 'business', 'enterprise'])->nullable()->index();
            $table->string('cs_path', 20)->nullable();
            $table->char('linked_provider_id', 36)->nullable()->index();
            $table->tinyInteger('stripe_connected')->default(0)->index();
            $table->string('stripe_account_id', 64)->nullable();
            $table->tinyInteger('verified')->default(0)->index();
            $table->char('invited_by_id', 36)->nullable()->index();
            $table->text('about_me')->nullable();
            $table->enum('bp_type', ['agency', 'freelancer'])->nullable()->index();
            $table->string('bp_business_name', 191)->nullable();
            $table->integer('bp_team_size')->nullable();
            $table->integer('bp_hourly_rate_cents')->nullable();
            $table->json('bp_categories')->nullable();
            $table->tinyInteger('two_factor_enabled')->default(0)->index();
            $table->string('w9_status', 20)->nullable();
            $table->timestamp('locked_at')->nullable()->index();
            $table->string('locked_reason', 255)->nullable();
            $table->integer('failed_login_count')->default(0);
            $table->timestamp('deactivated_at')->nullable()->index();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['role', 'created_at'], 'ix_users_role_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
