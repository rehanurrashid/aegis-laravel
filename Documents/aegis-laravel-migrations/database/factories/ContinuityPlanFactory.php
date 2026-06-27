<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PlanStatus;
use App\Models\ContinuityPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContinuityPlan>
 */
class ContinuityPlanFactory extends Factory
{
    protected $model = ContinuityPlan::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            PlanStatus::Draft->value,
            PlanStatus::PendingReview->value,
            PlanStatus::Active->value,
        ]);

        $signedAt   = $status === 'active' ? $this->faker->dateTimeBetween('-1 year', '-1 month') : null;
        $expiresAt  = $signedAt ? (clone $signedAt)->modify('+1 year') : null;
        $reviewDate = $signedAt ? (clone $signedAt)->modify('+335 days') : null;

        return [
            'id'                     => (string) Str::uuid(),
            'practitioner_id'        => User::factory()->practitioner(),
            'status'                 => $status,
            'plan_version'           => 1,
            'signed_at'              => $signedAt,
            'signature_name'         => $signedAt ? $this->faker->name() : null,
            'signature_title'        => $signedAt ? 'Practitioner' : null,
            'signature_ip'           => $signedAt ? $this->faker->ipv4() : null,
            'expires_at'             => $expiresAt,
            'annual_review_date'     => $reviewDate,
            'last_review_at'         => null,
            'annual_review_notes'    => null,
            'vault_attested_at'      => $signedAt ? $this->faker->dateTimeBetween($signedAt, 'now') : null,
            'vault_attestation_note' => $signedAt ? 'Vault contents verified and current.' : null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status'        => PlanStatus::Draft->value,
            'signed_at'     => null,
            'expires_at'    => null,
        ]);
    }

    public function active(): static
    {
        $signedAt = now()->subMonths(6);

        return $this->state(fn () => [
            'status'             => PlanStatus::Active->value,
            'signed_at'          => $signedAt,
            'signature_name'     => $this->faker->name(),
            'signature_title'    => 'Practitioner',
            'signature_ip'       => $this->faker->ipv4(),
            'expires_at'         => (clone $signedAt)->addYear(),
            'annual_review_date' => (clone $signedAt)->addDays(335),
            'vault_attested_at'  => now()->subMonths(5),
        ]);
    }

    public function annualReviewDue(): static
    {
        return $this->active()->state(fn () => [
            'status'             => PlanStatus::AnnualReviewDue->value,
            'annual_review_date' => now()->addDays(10),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status'     => PlanStatus::Expired->value,
            'signed_at'  => now()->subYears(2),
            'expires_at' => now()->subDays(30),
        ]);
    }
}
