<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Enums\SubmissionChannel;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    public function definition(): array
    {
        $status     = $this->faker->randomElement([
            ComplaintStatus::Open->value,
            ComplaintStatus::InProgress->value,
            ComplaintStatus::Resolved->value,
            ComplaintStatus::Closed->value,
        ]);
        $createdAt  = $this->faker->dateTimeBetween('-90 days', 'now');
        $resolvedAt = in_array($status, ['resolved', 'closed'], true)
            ? $this->faker->dateTimeBetween($createdAt, 'now')
            : null;
        $closedAt   = $status === 'closed' && $resolvedAt
            ? $this->faker->dateTimeBetween($resolvedAt, 'now')
            : null;

        return [
            'id'                 => (string) Str::uuid(),
            'submitted_by_id'    => User::factory(),
            'assigned_to_id'     => null,
            'category'           => $this->faker->randomElement([
                ComplaintCategory::SupportTicket->value,
                ComplaintCategory::Feedback->value,
                ComplaintCategory::Complaint->value,
            ]),
            'status'             => $status,
            'priority'           => $this->faker->randomElement([
                ComplaintPriority::Low->value,
                ComplaintPriority::Normal->value,
                ComplaintPriority::High->value,
                ComplaintPriority::Urgent->value,
            ]),
            'submission_channel' => $this->faker->randomElement([
                SubmissionChannel::FeedbackButton->value,
                SubmissionChannel::Questionnaire->value,
                SubmissionChannel::FreeForm->value,
                SubmissionChannel::HelpTicket->value,
            ]),
            'subject'            => $this->faker->sentence(6),
            'body'               => $this->faker->paragraphs(2, true),
            'resolved_at'        => $resolvedAt,
            'closed_at'          => $closedAt,
        ];
    }

    public function supportTicket(): static
    {
        return $this->state(fn () => [
            'category'           => ComplaintCategory::SupportTicket->value,
            'submission_channel' => SubmissionChannel::HelpTicket->value,
        ]);
    }

    public function feedback(): static
    {
        return $this->state(fn () => [
            'category'           => ComplaintCategory::Feedback->value,
            'submission_channel' => SubmissionChannel::FeedbackButton->value,
        ]);
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'status'      => ComplaintStatus::Open->value,
            'resolved_at' => null,
            'closed_at'   => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status'         => ComplaintStatus::InProgress->value,
            'assigned_to_id' => User::factory()->admin(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status'      => ComplaintStatus::Resolved->value,
            'resolved_at' => now()->subDays(2),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn () => [
            'priority' => ComplaintPriority::Urgent->value,
        ]);
    }
}
