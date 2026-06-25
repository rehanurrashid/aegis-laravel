<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // admin_audit_log columns: id, admin_id, action, linkable_type, linkable_id, target_user_id, meta_json, created_at
        $auditRows = [
            ['action' => 'lock_user',         'linkable_type' => 'user',            'linkable_id' => 'p_locked',              'target_user_id' => 'p_locked',      'meta_json' => json_encode(['reason' => 'Multiple failed login attempts', 'failed_count' => 5]),                                              'created_at' => $now->copy()->subDays(2)->toDateTimeString()],
            ['action' => 'assign_complaint',  'linkable_type' => 'complaint',       'linkable_id' => 'comp_sarah_in_progress','target_user_id' => 'p_sarah',       'meta_json' => json_encode(['assigned_to' => 'admin_root', 'priority_set_to' => 'high']),                                                   'created_at' => $now->copy()->subDays(5)->addHours(1)->toDateTimeString()],
            ['action' => 'reply_complaint',   'linkable_type' => 'complaint',       'linkable_id' => 'comp_sarah_in_progress','target_user_id' => 'p_sarah',       'meta_json' => json_encode(['reply_type' => 'public']),                                                                                     'created_at' => $now->copy()->subDays(4)->toDateTimeString()],
            ['action' => 'reply_complaint',   'linkable_type' => 'complaint',       'linkable_id' => 'comp_sarah_in_progress','target_user_id' => 'p_sarah',       'meta_json' => json_encode(['reply_type' => 'internal']),                                                                                   'created_at' => $now->copy()->subDays(4)->addHours(1)->toDateTimeString()],
            ['action' => 'resolve_complaint', 'linkable_type' => 'complaint',       'linkable_id' => 'comp_jamal_resolved',   'target_user_id' => 'bp_jamal',      'meta_json' => json_encode(['resolution_summary' => 'Explained Stripe payout timeline. No platform issue.']),                              'created_at' => $now->copy()->subDays(3)->toDateTimeString()],
            ['action' => 'deactivate_user',   'linkable_type' => 'user',            'linkable_id' => 'p_deactivated',         'target_user_id' => 'p_deactivated', 'meta_json' => json_encode(['reason' => 'User requested account deactivation']),                                                            'created_at' => $now->copy()->subDays(30)->toDateTimeString()],
            ['action' => 'change_user_role',  'linkable_type' => 'user',            'linkable_id' => 'admin_root',            'target_user_id' => 'admin_root',    'meta_json' => json_encode(['role_assigned' => 'role_admin', 'previous_role' => null]),                                                     'created_at' => $now->copy()->subYears(2)->toDateTimeString()],
            ['action' => 'publish_help_article','linkable_type'=> 'help_article',   'linkable_id' => null,                    'target_user_id' => null,             'meta_json' => json_encode(['title' => 'How Aegis Payments and Payouts Work', 'category' => 'billing']),                                   'created_at' => $now->copy()->subMonths(3)->toDateTimeString()],
            ['action' => 'override_package',  'linkable_type' => 'package_override','linkable_id' => null,                    'target_user_id' => null,             'meta_json' => json_encode(['tier' => 'access', 'feature_flag' => 'advanced_vault', 'enabled' => false]),                                 'created_at' => $now->copy()->subMonths(2)->toDateTimeString()],
            ['action' => 'refund_payment',    'linkable_type' => 'practitioner_payment','linkable_id' => null,                'target_user_id' => 'p_david',       'meta_json' => json_encode(['amount_cents' => 25000, 'reason' => 'Service not rendered — voided invoice', 'stripe_refund_id' => 're_demo_001']),'created_at' => $now->copy()->subMonths(1)->subDays(5)->toDateTimeString()],
        ];

        foreach ($auditRows as $row) {
            DB::table('admin_audit_log')->insert(array_merge($row, [
                'id'       => (string) Str::uuid(),
                'admin_id' => 'admin_root',
            ]));
        }

        // stripe_webhook_events columns: id, stripe_event_id, event_type, payload_json, processed, received_at, processed_at
        // Fixed: payload→payload_json; updated_at removed (doesn't exist)
        $webhooks = [
            ['stripe_event_id' => 'evt_demo_pi_succeeded_001',   'event_type' => 'payment_intent.succeeded',      'payload_json' => json_encode(['amount' => 90000,  'currency' => 'usd', 'metadata' => ['aegis_type' => 'cs_invoice']]),    'processed' => 1, 'processed_at' => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString(), 'received_at' => $now->copy()->subMonths(5)->subDays(20)->toDateTimeString()],
            ['stripe_event_id' => 'evt_demo_transfer_001',       'event_type' => 'transfer.created',              'payload_json' => json_encode(['amount' => 85500,  'currency' => 'usd', 'destination' => 'acct_demo_marcus']),              'processed' => 1, 'processed_at' => $now->copy()->subMonths(5)->subDays(18)->toDateTimeString(), 'received_at' => $now->copy()->subMonths(5)->subDays(18)->toDateTimeString()],
            ['stripe_event_id' => 'evt_demo_pi_succeeded_002',   'event_type' => 'payment_intent.succeeded',      'payload_json' => json_encode(['amount' => 145000, 'currency' => 'usd', 'metadata' => ['aegis_type' => 'bp_invoice']]),    'processed' => 1, 'processed_at' => $now->copy()->subMonths(1)->subDays(14)->toDateTimeString(), 'received_at' => $now->copy()->subMonths(1)->subDays(14)->toDateTimeString()],
            ['stripe_event_id' => 'evt_demo_pi_failed_001',      'event_type' => 'payment_intent.payment_failed', 'payload_json' => json_encode(['amount' => 48000,  'currency' => 'usd', 'error' => ['code' => 'card_declined']]),          'processed' => 1, 'processed_at' => $now->copy()->subDays(10)->toDateTimeString(),              'received_at' => $now->copy()->subDays(10)->toDateTimeString()],
            ['stripe_event_id' => 'evt_demo_account_updated_001','event_type' => 'account.updated',               'payload_json' => json_encode(['id' => 'acct_demo_sarah', 'charges_enabled' => true, 'payouts_enabled' => true]),          'processed' => 0, 'processed_at' => null,                                                      'received_at' => $now->copy()->subDays(1)->toDateTimeString()],
        ];

        foreach ($webhooks as $w) {
            DB::table('stripe_webhook_events')->updateOrInsert(
                ['stripe_event_id' => $w['stripe_event_id']],
                array_merge($w, ['id' => (string) Str::uuid()])
            );
        }
    }
}
