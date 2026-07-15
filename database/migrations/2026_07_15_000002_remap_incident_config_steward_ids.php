<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remap authorized_cs_ids / authorized_ss_ids in plan_incident_configs
     * from plan_steward.id values to stable user.id values.
     *
     * Any ID that already looks like a user ID (starts with 'u_' or matches a
     * known demo user slug such as 'cs_marcus') is passed through unchanged.
     * Only 'ps_' prefixed IDs are resolved via the plan_stewards table.
     */
    public function up(): void
    {
        $resolveIds = function (array $ids): array {
            return collect($ids)
                ->map(function (string $id) {
                    if (str_starts_with($id, 'ps_')) {
                        $userId = DB::table('plan_stewards')->where('id', $id)->value('steward_id');
                        return $userId ?? null; // drop if orphaned
                    }
                    // Already a user ID (u_* prefix or legacy demo slug like cs_marcus)
                    return $id;
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        };

        $configs = DB::table('plan_incident_configs')->get();

        foreach ($configs as $config) {
            $csIds = json_decode($config->authorized_cs_ids ?? '[]', true) ?: [];
            $ssIds = json_decode($config->authorized_ss_ids ?? '[]', true) ?: [];

            DB::table('plan_incident_configs')->where('id', $config->id)->update([
                'authorized_cs_ids' => json_encode($resolveIds($csIds)),
                'authorized_ss_ids' => json_encode($resolveIds($ssIds)),
            ]);
        }
    }

    public function down(): void
    {
        // Non-reversible data migration — no rollback
    }
};
