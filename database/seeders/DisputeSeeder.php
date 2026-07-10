<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DisputeReason;
use App\Enums\DisputeResolution;
use App\Enums\DisputeStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DisputeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // One resolved dispute — p_sarah disputed a CS invoice from cs_marcus
        // Provides demo content for the "resolved disputes" collapsed section in Finances.
        DB::table('disputes')->updateOrInsert(
            ['id' => 'dis_demo_sarah_01'],
            [
                'id'                    => 'dis_demo_sarah_01',
                'disputer_id'           => 'p_sarah',
                'respondent_id'         => 'cs_marcus',
                'subject_type'          => 'cs_invoice',
                'subject_id'            => 'csinv_marcus_sarah_annual',
                'reason'                => DisputeReason::QualityIssue->value,
                'amount_disputed_cents' => 25000,
                'description'           => 'Incident was not legitimately closed before the invoice was generated. Tasks were marked complete but the vault handoff had not been verified.',
                'status'                => DisputeStatus::Resolved->value,
                'resolution'            => DisputeResolution::NoAction->value,
                'resolution_cents'      => 0,
                'resolution_summary'    => 'Admin reviewed the incident log. Vault handoff was completed before invoice generation. Closure was valid. Dispute dismissed.',
                'resolved_by'           => 'admin_root',
                'opened_at'             => $now->copy()->subDays(21)->toDateTimeString(),
                'respondent_replied_at' => $now->copy()->subDays(19)->toDateTimeString(),
                'resolved_at'           => $now->copy()->subDays(14)->toDateTimeString(),
                'closed_at'             => null,
                'created_at'            => $now->copy()->subDays(21)->toDateTimeString(),
                'updated_at'            => $now->copy()->subDays(14)->toDateTimeString(),
            ]
        );
    }
}
