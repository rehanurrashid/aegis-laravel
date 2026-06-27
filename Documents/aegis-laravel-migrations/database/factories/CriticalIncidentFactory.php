<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivitySeverity;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Models\ContinuityPlan;
use App\Models\CriticalIncident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CriticalIncident>
 */
class CriticalIncidentFactory extends Factory
{
    protected $model = CriticalIncident::class;

    public function definition(): array
    {
        $status     = $this->faker->randomElement([
            IncidentStatus::Reported->value,
            IncidentStatus::Verified->value,
            IncidentStatus::Active->value,
            IncidentStatus::Closed->value,
        ]);
        $reportedAt = $this->faker->dateTimeBetween('-30 days', '-1 day');
        $verifiedAt = in_array($status, ['verified', 'active', 'closed'], true)
            ? $this->faker->dateTimeBetween($reportedAt, 'now')
            : null;
        $activatedAt = in_array($status, ['active', 'closed'], true) && $verifiedAt
            ? $this->faker->dateTimeBetween($verifiedAt, 'now')
            : null;
        $closedAt = $status === 'closed' && $activatedAt
            ? $this->faker->dateTimeBetween($activatedAt, 'now')
            : null;

        return [
            'id'              => (string) Str::uuid(),
            'plan_id'         => ContinuityPlan::factory()->active(),
            'practitioner_id' => User::factory()->practitioner(),
            'reported_by_id'  => User::factory()->supportSteward(),
            'incident_type'   => $this->faker->randomElement([
                IncidentType::Death->value,
                IncidentType::Incapacitation->value,
                IncidentType::ExtendedAbsence->value,
            ]),
            'status'        => $status,
            'severity'      => $this->faker->randomElement([
                ActivitySeverity::Warning->value,
                ActivitySeverity::Critical->value,
            ]),
            'reported_at'    => $reportedAt,
            'verified_at'    => $verifiedAt,
            'verified_by_id' => $verifiedAt ? User::factory()->continuitySteward() : null,
            'activated_at'   => $activatedAt,
            'closed_at'      => $closedAt,
            'summary'        => $this->faker->paragraph(2),
        ];
    }

    public function reported(): static
    {
        return $this->state(fn () => [
            'status'         => IncidentStatus::Reported->value,
            'verified_at'    => null,
            'verified_by_id' => null,
            'activated_at'   => null,
            'closed_at'      => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(function () {
            $reportedAt  = now()->subDays(3);
            $verifiedAt  = (clone $reportedAt)->addHours(4);
            $activatedAt = (clone $verifiedAt)->addHours(2);

            return [
                'status'       => IncidentStatus::Active->value,
                'severity'     => ActivitySeverity::Critical->value,
                'reported_at'  => $reportedAt,
                'verified_at'  => $verifiedAt,
                'activated_at' => $activatedAt,
                'closed_at'    => null,
            ];
        });
    }

    public function closed(): static
    {
        return $this->state(function () {
            $reportedAt  = now()->subMonths(2);
            $verifiedAt  = (clone $reportedAt)->addHours(4);
            $activatedAt = (clone $verifiedAt)->addHours(2);
            $closedAt    = (clone $activatedAt)->addDays(21);

            return [
                'status'       => IncidentStatus::Closed->value,
                'reported_at'  => $reportedAt,
                'verified_at'  => $verifiedAt,
                'activated_at' => $activatedAt,
                'closed_at'    => $closedAt,
            ];
        });
    }
}
