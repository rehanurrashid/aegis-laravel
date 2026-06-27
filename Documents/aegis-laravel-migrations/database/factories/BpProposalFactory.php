<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\BpJob;
use App\Models\BpProposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BpProposal>
 */
class BpProposalFactory extends Factory
{
    protected $model = BpProposal::class;

    public function definition(): array
    {
        $submittedAt = $this->faker->dateTimeBetween('-30 days', 'now');

        return [
            'id'                   => (string) Str::uuid(),
            'job_id'               => BpJob::factory()->open(),
            'bp_id'                => User::factory()->businessPartner(),
            'cover_letter'         => $this->faker->paragraphs(2, true),
            'proposed_rate_type'   => $this->faker->randomElement(['fixed', 'hourly', 'retainer']),
            'proposed_rate_cents'  => $this->faker->numberBetween(50000, 500000),
            'estimated_hours'      => $this->faker->optional()->numberBetween(5, 200),
            'estimated_completion' => $this->faker->dateTimeBetween('now', '+90 days'),
            'status'               => $this->faker->randomElement([
                ProposalStatus::Pending->value,
                ProposalStatus::UnderReview->value,
                ProposalStatus::Accepted->value,
                ProposalStatus::Declined->value,
            ]),
            'attachments'   => null,
            'submitted_at'  => $submittedAt,
            'withdrawn_at'  => null,
            'assigned_member_id' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ProposalStatus::Pending->value,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status' => ProposalStatus::Accepted->value,
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn () => [
            'status' => ProposalStatus::Declined->value,
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn () => [
            'status'       => ProposalStatus::Withdrawn->value,
            'withdrawn_at' => now(),
        ]);
    }
}
