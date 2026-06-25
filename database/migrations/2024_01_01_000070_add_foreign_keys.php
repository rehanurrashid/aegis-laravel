<?php
// All foreign key constraints across all 69 tables — add after all tables exist
// Section A of AEGIS_DATABASE_SCHEMA.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // users self-referential FKs (added after table exists)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('linked_provider_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('invited_by_id')->references('id')->on('users')->onDelete('set null');
        });

        // Domain 13 — role_permissions
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });

        // Domain 1 — user_meta, user_roles, user_sessions, user_preferences, password_reset_tokens, mfa_tokens
        Schema::table('user_meta', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('user_roles', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('mfa_tokens', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Domain 2 — continuity_plans and children
        Schema::table('continuity_plans', function (Blueprint $table) {
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::table('plan_meta', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('continuity_plans')->onDelete('cascade');
        });
        Schema::table('plan_stewards', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('continuity_plans')->onDelete('cascade');
            $table->foreign('steward_id')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::table('plan_tasks', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('continuity_plans')->onDelete('cascade');
            $table->foreign('steward_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('plan_incident_configs', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('continuity_plans')->onDelete('cascade');
        });

        // Domain 3 — critical incidents
        Schema::table('critical_incidents', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('continuity_plans')->onDelete('restrict');
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('reported_by_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('verified_by_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('incident_meta', function (Blueprint $table) {
            $table->foreign('incident_id')->references('id')->on('critical_incidents')->onDelete('cascade');
        });
        Schema::table('incident_tasks', function (Blueprint $table) {
            $table->foreign('incident_id')->references('id')->on('critical_incidents')->onDelete('cascade');
            $table->foreign('assigned_to_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('incident_updates', function (Blueprint $table) {
            $table->foreign('incident_id')->references('id')->on('critical_incidents')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('set null');
        });

        // Domain 4 — vault and documents
        Schema::table('vault_items', function (Blueprint $table) {
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::table('vault_item_meta', function (Blueprint $table) {
            $table->foreign('vault_item_id')->references('id')->on('vault_items')->onDelete('cascade');
        });
        Schema::table('vault_access_log', function (Blueprint $table) {
            $table->foreign('vault_item_id')->references('id')->on('vault_items')->onDelete('set null');
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('continuity_documents', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('continuity_plans')->onDelete('set null');
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('amends_document_id')->references('id')->on('continuity_documents')->onDelete('set null');
            $table->foreign('holder_steward_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('document_signatures', function (Blueprint $table) {
            $table->foreign('document_id')->references('id')->on('continuity_documents')->onDelete('cascade');
            $table->foreign('signer_id')->references('id')->on('users')->onDelete('restrict');
        });

        // Domain 5 — network and referrals
        Schema::table('network_connections', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('connected_user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('network_requests', function (Blueprint $table) {
            $table->foreign('requester_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('shadow_connections', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shadow_user_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('referrals', function (Blueprint $table) {
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::table('referral_meta', function (Blueprint $table) {
            $table->foreign('referral_id')->references('id')->on('referrals')->onDelete('cascade');
        });

        // Domain 6 — messaging and activity
        Schema::table('message_threads', function (Blueprint $table) {
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('thread_id')->references('id')->on('message_threads')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('activity_events', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('scoped_provider_id')->references('id')->on('users')->onDelete('set null');
        });

        // Domain 7 — services and CEUs
        Schema::table('services', function (Blueprint $table) {
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('inquirer_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('service_sessions', function (Blueprint $table) {
            $table->foreign('service_request_id')->references('id')->on('service_requests')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('client_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('ceu_entries', function (Blueprint $table) {
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Domain 8 — Business Partner
        Schema::table('bp_jobs', function (Blueprint $table) {
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::table('bp_proposals', function (Blueprint $table) {
            $table->foreign('job_id')->references('id')->on('bp_jobs')->onDelete('cascade');
            $table->foreign('bp_id')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::table('bp_contracts', function (Blueprint $table) {
            $table->foreign('job_id')->references('id')->on('bp_jobs')->onDelete('set null');
            $table->foreign('proposal_id')->references('id')->on('bp_proposals')->onDelete('set null');
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('bp_id')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::table('contract_meta', function (Blueprint $table) {
            $table->foreign('contract_id')->references('id')->on('bp_contracts')->onDelete('cascade');
        });
        Schema::table('bp_milestones', function (Blueprint $table) {
            $table->foreign('contract_id')->references('id')->on('bp_contracts')->onDelete('cascade');
            $table->foreign('assigned_member_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('bp_invoices', function (Blueprint $table) {
            $table->foreign('bp_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('contract_id')->references('id')->on('bp_contracts')->onDelete('set null');
        });
        Schema::table('bp_invoice_line_items', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('bp_invoices')->onDelete('cascade');
        });
        Schema::table('bp_invoice_payments', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('bp_invoices')->onDelete('cascade');
            $table->foreign('payer_id')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::table('bp_payouts', function (Blueprint $table) {
            $table->foreign('bp_id')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::table('bp_tax_documents', function (Blueprint $table) {
            $table->foreign('bp_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('bp_team_members', function (Blueprint $table) {
            $table->foreign('agency_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('bp_team_invitations', function (Blueprint $table) {
            $table->foreign('agency_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('bp_saved_jobs', function (Blueprint $table) {
            $table->foreign('bp_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('job_id')->references('id')->on('bp_jobs')->onDelete('cascade');
        });

        // Domain 9 — CS/Practitioner Finances
        Schema::table('cs_invoices', function (Blueprint $table) {
            $table->foreign('cs_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::table('cs_payouts', function (Blueprint $table) {
            $table->foreign('cs_id')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::table('practitioner_payment_methods', function (Blueprint $table) {
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('practitioner_payments', function (Blueprint $table) {
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('payment_method_id')->references('id')->on('practitioner_payment_methods')->onDelete('set null');
        });

        // Domain 10 — Support Steward
        Schema::table('ss_provider_checkins', function (Blueprint $table) {
            $table->foreign('ss_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
        });
        Schema::table('ss_provider_notes', function (Blueprint $table) {
            $table->foreign('ss_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('restrict');
        });

        // Domain 11 — News & Content
        Schema::table('news_posts', function (Blueprint $table) {
            $table->foreign('author_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('news_comments', function (Blueprint $table) {
            $table->foreign('post_id')->references('id')->on('news_posts')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('news_reactions', function (Blueprint $table) {
            $table->foreign('post_id')->references('id')->on('news_posts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('news_poll_votes', function (Blueprint $table) {
            $table->foreign('post_id')->references('id')->on('news_posts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Domain 12 — Support & Feedback
        Schema::table('complaints', function (Blueprint $table) {
            $table->foreign('submitter_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('complaint_meta', function (Blueprint $table) {
            $table->foreign('complaint_id')->references('id')->on('complaints')->onDelete('cascade');
        });
        Schema::table('complaint_replies', function (Blueprint $table) {
            $table->foreign('complaint_id')->references('id')->on('complaints')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('users')->onDelete('restrict');
        });

        // Domain 13 — Admin
        Schema::table('admin_audit_log', function (Blueprint $table) {
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('target_user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Domain 14 — Profile & Authorization
        Schema::table('profile_edit_authorizations', function (Blueprint $table) {
            $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('authorized_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Drop in reverse dependency order
        Schema::table('profile_edit_authorizations', fn($t) => $t->dropForeign(['practitioner_id', 'authorized_user_id']));
        Schema::table('admin_audit_log', fn($t) => $t->dropForeign(['admin_id', 'target_user_id']));
        Schema::table('complaint_replies', fn($t) => $t->dropForeign(['complaint_id', 'author_id']));
        Schema::table('complaint_meta', fn($t) => $t->dropForeign(['complaint_id']));
        Schema::table('complaints', fn($t) => $t->dropForeign(['submitter_id', 'assigned_to']));
        Schema::table('news_poll_votes', fn($t) => $t->dropForeign(['post_id', 'user_id']));
        Schema::table('news_reactions', fn($t) => $t->dropForeign(['post_id', 'user_id']));
        Schema::table('news_comments', fn($t) => $t->dropForeign(['post_id', 'author_id']));
        Schema::table('news_posts', fn($t) => $t->dropForeign(['author_id']));
        Schema::table('ss_provider_notes', fn($t) => $t->dropForeign(['ss_id', 'practitioner_id']));
        Schema::table('ss_provider_checkins', fn($t) => $t->dropForeign(['ss_id', 'practitioner_id']));
        Schema::table('practitioner_payments', fn($t) => $t->dropForeign(['practitioner_id', 'payment_method_id']));
        Schema::table('practitioner_payment_methods', fn($t) => $t->dropForeign(['practitioner_id']));
        Schema::table('cs_payouts', fn($t) => $t->dropForeign(['cs_id']));
        Schema::table('cs_invoices', fn($t) => $t->dropForeign(['cs_id', 'practitioner_id']));
        Schema::table('bp_saved_jobs', fn($t) => $t->dropForeign(['bp_id', 'job_id']));
        Schema::table('bp_team_invitations', fn($t) => $t->dropForeign(['agency_id']));
        Schema::table('bp_team_members', fn($t) => $t->dropForeign(['agency_id', 'member_id']));
        Schema::table('bp_tax_documents', fn($t) => $t->dropForeign(['bp_id']));
        Schema::table('bp_payouts', fn($t) => $t->dropForeign(['bp_id']));
        Schema::table('bp_invoice_payments', fn($t) => $t->dropForeign(['invoice_id', 'payer_id']));
        Schema::table('bp_invoice_line_items', fn($t) => $t->dropForeign(['invoice_id']));
        Schema::table('bp_invoices', fn($t) => $t->dropForeign(['bp_id', 'practitioner_id', 'contract_id']));
        Schema::table('bp_milestones', fn($t) => $t->dropForeign(['contract_id', 'assigned_member_id']));
        Schema::table('contract_meta', fn($t) => $t->dropForeign(['contract_id']));
        Schema::table('bp_contracts', fn($t) => $t->dropForeign(['job_id', 'proposal_id', 'practitioner_id', 'bp_id']));
        Schema::table('bp_proposals', fn($t) => $t->dropForeign(['job_id', 'bp_id']));
        Schema::table('bp_jobs', fn($t) => $t->dropForeign(['practitioner_id']));
        Schema::table('ceu_entries', fn($t) => $t->dropForeign(['practitioner_id']));
        Schema::table('service_sessions', fn($t) => $t->dropForeign(['service_request_id', 'service_id', 'practitioner_id', 'client_id']));
        Schema::table('service_requests', fn($t) => $t->dropForeign(['service_id', 'practitioner_id', 'inquirer_id']));
        Schema::table('services', fn($t) => $t->dropForeign(['practitioner_id']));
        Schema::table('activity_events', fn($t) => $t->dropForeign(['user_id', 'scoped_provider_id']));
        Schema::table('messages', fn($t) => $t->dropForeign(['thread_id', 'sender_id', 'recipient_id']));
        Schema::table('message_threads', fn($t) => $t->dropForeign(['created_by_id']));
        Schema::table('referral_meta', fn($t) => $t->dropForeign(['referral_id']));
        Schema::table('referrals', fn($t) => $t->dropForeign(['sender_id', 'recipient_id']));
        Schema::table('shadow_connections', fn($t) => $t->dropForeign(['user_id', 'shadow_user_id']));
        Schema::table('network_requests', fn($t) => $t->dropForeign(['requester_id', 'recipient_id']));
        Schema::table('network_connections', fn($t) => $t->dropForeign(['user_id', 'connected_user_id']));
        Schema::table('document_signatures', fn($t) => $t->dropForeign(['document_id', 'signer_id']));
        Schema::table('continuity_documents', fn($t) => $t->dropForeign(['plan_id', 'practitioner_id', 'amends_document_id', 'holder_steward_id']));
        Schema::table('vault_access_log', fn($t) => $t->dropForeign(['vault_item_id', 'practitioner_id', 'actor_id', 'recipient_id']));
        Schema::table('vault_item_meta', fn($t) => $t->dropForeign(['vault_item_id']));
        Schema::table('vault_items', fn($t) => $t->dropForeign(['practitioner_id']));
        Schema::table('incident_updates', fn($t) => $t->dropForeign(['incident_id', 'actor_id']));
        Schema::table('incident_tasks', fn($t) => $t->dropForeign(['incident_id', 'assigned_to_id']));
        Schema::table('incident_meta', fn($t) => $t->dropForeign(['incident_id']));
        Schema::table('critical_incidents', fn($t) => $t->dropForeign(['plan_id', 'practitioner_id', 'reported_by_id', 'verified_by_id']));
        Schema::table('plan_incident_configs', fn($t) => $t->dropForeign(['plan_id']));
        Schema::table('plan_tasks', fn($t) => $t->dropForeign(['plan_id', 'steward_id']));
        Schema::table('plan_stewards', fn($t) => $t->dropForeign(['plan_id', 'steward_id']));
        Schema::table('plan_meta', fn($t) => $t->dropForeign(['plan_id']));
        Schema::table('continuity_plans', fn($t) => $t->dropForeign(['practitioner_id']));
        Schema::table('mfa_tokens', fn($t) => $t->dropForeign(['user_id']));
        Schema::table('password_reset_tokens', fn($t) => $t->dropForeign(['user_id']));
        Schema::table('user_preferences', fn($t) => $t->dropForeign(['user_id']));
        Schema::table('user_sessions', fn($t) => $t->dropForeign(['user_id']));
        Schema::table('user_roles', fn($t) => $t->dropForeign(['user_id']));
        Schema::table('user_meta', fn($t) => $t->dropForeign(['user_id']));
        Schema::table('role_permissions', fn($t) => $t->dropForeign(['role_id']));
        Schema::table('users', fn($t) => $t->dropForeign(['linked_provider_id', 'invited_by_id']));
    }
};
