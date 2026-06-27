<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BpJobStatus;
use App\Models\BpJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BpJob>
 */
class BpJobFactory extends Factory
{
    protected $model = BpJob::class;

    public function definition(): array
    {
        $budgetMin = $this->faker->numberBetween(50000, 200000);
        $budgetMax = $budgetMin + $this->faker->numberBetween(50000, 300000);
        $postedAt  = $this->faker->dateTimeBetween('-60 days', 'now');

        return [
            'id'                => (string) Str::uuid(),
            'created_by_id'     => User::factory()->practitioner(),
            'title'             => $this->faker->randomElement([
                'Billing & Revenue Cycle Management',
                'HIPAA Compliance Consultant',
                'Practice Marketing Lead',
                'Insurance Credentialing Specialist',
                'Bookkeeping & Tax Preparation',
            ]),
            'description'       => $this->faker->paragraphs(3, true),
            'budget_type'       => $this->faker->randomElement(['fixed', 'hourly', 'retainer']),
            'budget_min_cents'  => $budgetMin,
            'budget_max_cents'  => $budgetMax,
            'engagement_type'   => $this->faker->randomElement(['one_off', 'ongoing', 'retainer']),
            'urgency'           => $this->faker->randomElement(['low', 'normal', 'high']),
            'status'            => $this->faker->randomElement([
                BpJobStatus::Draft->value,
                BpJobStatus::Open->value,
                BpJobStatus::Paused->value,
            ]),
            'tags'              => $this->faker->randomElements(['billing', 'compliance', 'marketing', 'credentialing', 'bookkeeping'], 2),
            'requirements'      => [
                'experience_years_min' => $this->faker->numberBetween(1, 10),
                'remote_ok'            => true,
            ],
            'posted_at'         => $postedAt,
            'closes_at'         => $this->faker->dateTimeBetween($postedAt, '+90 days'),
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'status'    => BpJobStatus::Open->value,
            'posted_at' => now()->subDays(rand(1, 10)),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status'    => BpJobStatus::Draft->value,
            'posted_at' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => BpJobStatus::Closed->value,
        ]);
    }

    public function filled(): static
    {
        return $this->state(fn () => [
            'status' => BpJobStatus::Filled->value,
        ]);
    }
}
