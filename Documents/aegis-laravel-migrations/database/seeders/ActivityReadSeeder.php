<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds activity_event_reads — per-user read receipts for the activity feed.
 *
 * activity_events has NO recipient_id column. Events are addressed by user_id.
 * Strategy: for each demo user, mark the oldest 60% of their own activity events as read.
 */
class ActivityReadSeeder extends Seeder
{
    public function run(): void
    {
        $demoUserIds = [
            'p_sarah', 'p_david', 'p_maria',
            'cs_marcus', 'cs_priya', 'cs_alternate',
            'ss_linda', 'ss_james',
            'bp_acme', 'bp_jamal',
            'admin_root',
        ];

        $now = now();

        foreach ($demoUserIds as $userId) {
            // Pull events belonging to this user (activity_events.user_id), oldest first
            $events = DB::table('activity_events')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'asc')
                ->get(['id', 'created_at']);

            if ($events->isEmpty()) {
                continue;
            }

            $readCount = (int) floor($events->count() * 0.60);
            $toMark    = $events->take($readCount);

            $rows = [];
            foreach ($toMark as $event) {
                $readAt = \Carbon\Carbon::parse($event->created_at)->addMinutes(rand(5, 2880));
                if ($readAt->isAfter($now)) {
                    $readAt = $now;
                }

                $rows[] = [
                    'id'                => (string) Str::uuid(),
                    'user_id'           => $userId,
                    'activity_event_id' => $event->id,
                    'read_at'           => $readAt->toDateTimeString(),
                    'created_at'        => $readAt->toDateTimeString(),
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('activity_event_reads')->insert($chunk);
            }
        }
    }
}
