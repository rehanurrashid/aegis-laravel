<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Backfills notification-preference UserMeta rows for users created before the
 * notify_* gate set was expanded. Idempotent: only inserts keys a user is missing,
 * defaulting to '1' (on) so existing behaviour is preserved. Mirrors the seed in
 * AuthService::register().
 */
return new class extends Migration
{
    /** Canonical notify_* gate set consumed by the email layer. */
    private array $keys = [
        'notify_email', 'notify_incident', 'notify_message', 'notify_task',
        'notify_assignment', 'notify_attestation', 'notify_plan_change',
        'notify_plan_review', 'notify_role_change', 'notify_payment',
        'notify_proposal', 'notify_agreement', 'notify_summary',
        'notify_account', 'notify_plan', 'notify_referral', 'notify_steward', 'notify_vault',
    ];

    public function up(): void
    {
        $now = now();

        DB::table('users')->orderBy('id')->chunkById(500, function ($users) use ($now) {
            $userIds = collect($users)->pluck('id')->all();

            // Existing (user_id, meta_key) pairs for these users, for the keys we manage.
            $existing = DB::table('user_meta')
                ->whereIn('user_id', $userIds)
                ->whereIn('meta_key', $this->keys)
                ->get(['user_id', 'meta_key'])
                ->groupBy('user_id')
                ->map(fn ($rows) => $rows->pluck('meta_key')->all());

            $rows = [];
            foreach ($users as $user) {
                $have = $existing[$user->id] ?? [];
                foreach ($this->keys as $key) {
                    if (in_array($key, $have, true)) {
                        continue;
                    }
                    $rows[] = [
                        'id'         => 'um_' . Str::lower(Str::random(12)),
                        'user_id'    => $user->id,
                        'meta_key'   => $key,
                        'meta_value' => '1',
                        'meta_type'  => 'boolean',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($rows) {
                foreach (array_chunk($rows, 500) as $batch) {
                    DB::table('user_meta')->insert($batch);
                }
            }
        });
    }

    public function down(): void
    {
        // Only remove the gate keys that were not part of the original seed set,
        // to avoid deleting preferences that predate this migration.
        DB::table('user_meta')
            ->whereIn('meta_key', ['notify_account', 'notify_plan', 'notify_referral', 'notify_steward', 'notify_vault'])
            ->delete();
    }
};
