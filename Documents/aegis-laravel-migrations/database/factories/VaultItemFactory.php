<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VaultItemStatus;
use App\Enums\VaultZone;
use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VaultItem>
 */
class VaultItemFactory extends Factory
{
    protected $model = VaultItem::class;

    public function definition(): array
    {
        $zone = $this->faker->randomElement([
            VaultZone::Credentials->value,
            VaultZone::Roster->value,
            VaultZone::Documents->value,
            VaultZone::Instructions->value,
        ]);

        return array_merge(
            [
                'id'              => (string) Str::uuid(),
                'practitioner_id' => User::factory()->practitioner(),
                'zone'            => $zone,
                'category'        => $this->categoryForZone($zone),
                'title'           => $this->titleForZone($zone),
                'sub_label'       => $this->faker->optional()->sentence(4),
                'status'          => VaultItemStatus::VaultOnly->value,
                'access_grant'    => null,
                'credential_username'     => null,
                'credential_password_enc' => null,
                'credential_url'          => null,
                'client_name'             => null,
                'client_priority'         => null,
                'file_ref'                => null,
            ],
            $this->zoneSpecificData($zone)
        );
    }

    private function categoryForZone(string $zone): string
    {
        return match ($zone) {
            'credentials'  => $this->faker->randomElement(['ehr', 'billing', 'email', 'cloud_storage']),
            'roster'       => $this->faker->randomElement(['standard', 'high_acuity', 'emergency_contacts']),
            'documents'    => $this->faker->randomElement(['license', 'malpractice', 'baa', 'lease']),
            'instructions' => 'closing_protocol',
        };
    }

    private function titleForZone(string $zone): string
    {
        return match ($zone) {
            'credentials'  => $this->faker->randomElement(['SimplePractice EHR', 'Office Ally Billing', 'Google Workspace', 'Squarespace']),
            'roster'       => 'Client Record — ' . $this->faker->lastName(),
            'documents'    => $this->faker->randomElement(['Texas LPC License', 'Malpractice Policy', 'BAA Copy', 'Office Lease']),
            'instructions' => 'Practice Closure Protocol',
        };
    }

    private function zoneSpecificData(string $zone): array
    {
        return match ($zone) {
            'credentials' => [
                'credential_username'     => $this->faker->safeEmail(),
                'credential_password_enc' => 'enc:AES256:' . Str::random(32),
                'credential_url'          => $this->faker->url(),
            ],
            'roster' => [
                'client_name'     => 'Client ' . $this->faker->lastName(),
                'client_priority' => $this->faker->numberBetween(1, 5),
            ],
            'documents' => [
                'file_ref' => 'vault/' . Str::random(8) . '/' . Str::slug($this->faker->words(3, true)) . '.pdf',
            ],
            'instructions' => [
                'file_ref' => 'vault/' . Str::random(8) . '/closure_instructions.pdf',
            ],
        };
    }

    public function credential(): static
    {
        return $this->state(fn () => [
            'zone' => VaultZone::Credentials->value,
        ] + $this->zoneSpecificData('credentials'));
    }

    public function roster(): static
    {
        return $this->state(fn () => [
            'zone' => VaultZone::Roster->value,
        ] + $this->zoneSpecificData('roster'));
    }

    public function document(): static
    {
        return $this->state(fn () => [
            'zone' => VaultZone::Documents->value,
        ] + $this->zoneSpecificData('documents'));
    }

    public function priority(): static
    {
        return $this->state(fn () => [
            'status'          => VaultItemStatus::Priority->value,
            'client_priority' => 1,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => VaultItemStatus::Active->value,
        ]);
    }
}
