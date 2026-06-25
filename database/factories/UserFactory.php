<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $role = $this->faker->randomElement([
            UserRole::Practitioner->value,
            UserRole::ContinuitySteward->value,
            UserRole::SupportSteward->value,
            UserRole::BusinessPartner->value,
        ]);

        $displayName = $this->faker->name();
        $initials    = collect(explode(' ', $displayName))
            ->take(2)
            ->map(fn ($w) => strtoupper(substr($w, 0, 1)))
            ->implode('');

        return [
            'id'                      => (string) Str::uuid(),
            'role'                    => $role,
            'display_name'            => $displayName,
            'credentials'             => $this->faker->optional(0.6)->randomElement(['MD', 'LPC', 'LMFT', 'LCSW', 'PhD', 'PsyD']),
            'email'                   => $this->faker->unique()->safeEmail(),
            'phone'                   => $this->faker->optional()->phoneNumber(),
            'location'                => $this->faker->city() . ', ' . $this->faker->stateAbbr(),
            'organization'            => $this->faker->optional()->company(),
            'avatar_initials'         => $initials,
            'title'                   => $this->faker->optional()->jobTitle(),
            'specialty'               => $this->faker->optional()->randomElement(['Trauma & Anxiety', 'Couples Therapy', 'CBT', 'EMDR', 'Family Systems']),
            'bio'                     => $this->faker->optional()->paragraph(),
            'slug'                    => Str::slug($displayName) . '-' . Str::random(6),
            'slug_locked_at'          => null,
            'practitioner_public'     => $role === 'practitioner' ? 1 : 0,
            'cs_public'               => $role === 'continuity_steward' ? $this->faker->boolean(70) : 0,
            'business_partner_public' => $role === 'business_partner' ? 1 : 0,
            'tier'                    => $role === 'practitioner' ? $this->faker->randomElement(['access', 'practice']) : null,
            'services_mode'           => $role === 'practitioner' ? $this->faker->boolean(40) : 0,
            'maat_addon'              => $role === 'practitioner' ? $this->faker->boolean(20) : 0,
            'payment_model'           => null,
            'cs_account_type'         => $role === 'continuity_steward' ? $this->faker->randomElement(['invited', 'business', 'enterprise']) : null,
            'cs_path'                 => null,
            'linked_provider_id'      => null,
            'stripe_connected'        => $this->faker->boolean(30),
            'stripe_account_id'       => null,
            'verified'                => $this->faker->boolean(20),
            'invited_by_id'           => null,
            'about_me'                => null,
            'bp_type'                 => $role === 'business_partner' ? $this->faker->randomElement(['agency', 'freelancer']) : null,
            'bp_business_name'        => $role === 'business_partner' ? $this->faker->company() : null,
            'bp_team_size'            => $role === 'business_partner' ? $this->faker->numberBetween(1, 25) : null,
            'bp_hourly_rate_cents'    => $role === 'business_partner' ? $this->faker->numberBetween(5000, 30000) : null,
            'bp_categories'           => $role === 'business_partner' ? ['billing', 'compliance'] : null,
            'two_factor_enabled'      => $this->faker->boolean(15),
            'w9_status'               => null,
            'locked_at'               => null,
            'locked_reason'           => null,
            'failed_login_count'      => 0,
            'deactivated_at'          => null,
            'last_login_at'           => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function practitioner(): static
    {
        return $this->state(fn () => [
            'role'                => UserRole::Practitioner->value,
            'tier'                => 'practice',
            'practitioner_public' => 1,
        ]);
    }

    public function continuitySteward(): static
    {
        return $this->state(fn () => [
            'role'            => UserRole::ContinuitySteward->value,
            'cs_account_type' => 'business',
            'cs_public'       => 1,
        ]);
    }

    public function supportSteward(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::SupportSteward->value,
        ]);
    }

    public function businessPartner(): static
    {
        return $this->state(fn () => [
            'role'                    => UserRole::BusinessPartner->value,
            'bp_type'                 => 'agency',
            'business_partner_public' => 1,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Admin->value,
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn () => [
            'locked_at'     => now(),
            'locked_reason' => 'Locked by admin (factory)',
        ]);
    }

    public function deactivated(): static
    {
        return $this->state(fn () => [
            'deactivated_at' => now(),
        ]);
    }
}
