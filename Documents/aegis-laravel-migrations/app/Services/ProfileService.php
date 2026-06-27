<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Support\Str;

class ProfileService
{
    public function updateBasic(User $user, array $data): User
    {
        $allowed = ['display_name', 'phone', 'title', 'organization', 'location', 'bio', 'about_me', 'avatar_initials'];
        $user->update(array_intersect_key($data, array_flip($allowed)));
        return $user->fresh();
    }

    public function updateCredentials(User $user, array $data): User
    {
        $allowed = ['credentials', 'verified'];
        $user->update(array_intersect_key($data, array_flip($allowed)));
        return $user->fresh();
    }

    public function updateSpecialties(User $user, array $specialties): User
    {
        $user->update(['specialty' => is_array($specialties) ? json_encode($specialties) : $specialties]);
        return $user->fresh();
    }

    public function updateServices(User $user, array $services): User
    {
        $meta = $user->profile_meta ? (json_decode($user->profile_meta, true) ?: []) : [];
        $meta['services'] = $services;
        $user->update(['profile_meta' => json_encode($meta)]);
        return $user->fresh();
    }

    public function updateApproaches(User $user, array $approaches): User
    {
        $meta = $user->profile_meta ? (json_decode($user->profile_meta, true) ?: []) : [];
        $meta['approaches'] = $approaches;
        $user->update(['profile_meta' => json_encode($meta)]);
        return $user->fresh();
    }

    public function updateFees(User $user, array $fees): User
    {
        $meta = $user->profile_meta ? (json_decode($user->profile_meta, true) ?: []) : [];
        $meta['fees'] = $fees;
        $user->update(['profile_meta' => json_encode($meta)]);
        return $user->fresh();
    }

    public function updateAvailability(User $user, array $availability): User
    {
        $user->update([
            'network_hours' => isset($availability['hours']) ? json_encode($availability['hours']) : $user->network_hours,
            'network_accepting' => $availability['accepting'] ?? $user->network_accepting,
            'network_telehealth' => $availability['telehealth'] ?? $user->network_telehealth,
        ]);
        return $user->fresh();
    }

    public function updatePrivacy(User $user, array $privacy): User
    {
        $allowed = ['practitioner_public', 'cs_public', 'business_partner_public'];
        $user->update(array_intersect_key($privacy, array_flip($allowed)));
        return $user->fresh();
    }

    public function updateNetworkPreferences(User $user, array $prefs): User
    {
        $allowed = ['network_accepting', 'network_telehealth', 'network_insurance', 'network_languages', 'network_format'];
        $update = array_intersect_key($prefs, array_flip($allowed));
        foreach (['network_insurance', 'network_languages', 'network_format'] as $jsonKey) {
            if (isset($update[$jsonKey]) && is_array($update[$jsonKey])) {
                $update[$jsonKey] = json_encode($update[$jsonKey]);
            }
        }
        $user->update($update);
        return $user->fresh();
    }

    public function setMeta(string $userId, string $key, string $value, string $type = 'string'): UserMeta
    {
        return UserMeta::updateOrCreate(
            ['user_id' => $userId, 'meta_key' => $key],
            ['meta_value' => $value, 'meta_type' => $type]
        );
    }

    public function getMeta(string $userId, string $key, mixed $default = null): mixed
    {
        $row = UserMeta::where('user_id', $userId)->where('meta_key', $key)->first();
        if (!$row) return $default;

        return match ($row->meta_type) {
            'bool' => in_array((string) $row->meta_value, ['1', 'true', 'on', 'yes'], true),
            'int'  => (int) $row->meta_value,
            'json' => json_decode($row->meta_value, true),
            default=> $row->meta_value,
        };
    }

    public function getPublicProfile(string $slug): ?User
    {
        return User::where('slug', $slug)
            ->whereNull('deactivated_at')
            ->first();
    }
}
