<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\PackageOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class AdminPackageService
{
    public function getAll(): Collection
    {
        return PackageOverride::orderBy('tier')->get();
    }

    public function setPrice(User $admin, string $tier, int $monthlyCents, int $annualCents): PackageOverride
    {
        $pkg = PackageOverride::where('tier', $tier)->firstOrFail();
        $pkg->update([
            'price_monthly_cents' => $monthlyCents,
            'price_annual_cents'  => $annualCents,
            'updated_at'          => now(),
        ]);
        $this->audit($admin, 'set_price', $tier, compact('monthlyCents', 'annualCents'));
        return $pkg->fresh();
    }

    public function setFeature(User $admin, string $tier, string $featureKey, bool $enabled): PackageOverride
    {
        $pkg = PackageOverride::where('tier', $tier)->firstOrFail();
        $features = $pkg->feature_flags ?? [];
        $features[$featureKey] = $enabled;
        $pkg->update(['feature_flags' => $features]);
        $this->audit($admin, 'set_feature', $tier, compact('featureKey', 'enabled'));
        return $pkg->fresh();
    }

    public function setLimits(User $admin, string $tier, array $limits): PackageOverride
    {
        $pkg = PackageOverride::where('tier', $tier)->firstOrFail();
        $pkg->update(['limits' => $limits]);
        $this->audit($admin, 'set_limits', $tier, $limits);
        return $pkg->fresh();
    }

    private function audit(User $admin, string $action, string $tier, array $meta): void
    {
        AdminAuditLog::create([
            'id'          => 'aal_' . Str::lower(Str::random(12)),
            'admin_id'    => $admin->id,
            'action'      => $action,
            'target_type' => 'package_override',
            'target_id'   => $tier,
            'meta_json'   => json_encode($meta),
            'created_at'  => now(),
        ]);
    }
}
