<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [];

        // ── Helper ────────────────────────────────────────────────────────────
        $act = function (
            string $userId,
            string $portal,
            string $eventType,
            string $title,
            string $severity = 'info',
            ?string $description = null,
            ?string $module = null,
            ?string $action = null,
            ?string $linkableType = null,
            ?string $linkableId = null,
            ?string $scopedProviderId = null,
            ?string $readAt = null,
            ?string $createdAt = null,
        ) use (&$rows): void {
            $rows[] = [
                'id'                 => (string) Str::uuid(),
                'user_id'            => $userId,
                'portal'             => $portal,
                'event_type'         => $eventType,
                'severity'           => $severity,
                'module'             => $module,
                'action'             => $action,
                'title'              => $title,
                'description'        => $description,
                'linkable_type'      => $linkableType,
                'linkable_id'        => $linkableId,
                'scoped_provider_id' => $scopedProviderId,
                'read_at'            => $readAt,
                'created_at'         => $createdAt ?? now()->toDateTimeString(),
            ];
        };

        $read  = now()->subHours(1)->toDateTimeString();
        $unread = null;


        // ══════════════════════════════════════════════════════════════════════
        // p_sarah — Provider portal
        // ══════════════════════════════════════════════════════════════════════

        // Plan signed fan-out → practitioner confirmation
        $act('p_sarah', 'provider', 'document',
            'Your Continuity Plan has been signed',
            'info',
            'Your Continuity Plan is now countersigned and active. Marcus Williams (CS) has countersigned.',
            'continuity_plan', 'plan_signed',
            'ContinuityPlan', 'plan_sarah',
            null,
            $read,
            now()->subMonths(6)->toDateTimeString()
        );

        // Vault attested
        $act('p_sarah', 'provider', 'vault',
            'Vault attestation confirmed',
            'info',
            'Your vault has been reviewed and attested by Marcus Williams. All items verified.',
            'vault', 'attested',
            'ContinuityPlan', 'plan_sarah',
            null,
            $read,
            now()->subMonths(5)->toDateTimeString()
        );

        // Active incident — CRITICAL, unread
        $act('p_sarah', 'provider', 'incident',
            'Critical Incident activated on your plan',
            'critical',
            'A Critical Incident (Short-Term Incapacitation) has been activated. Marcus Williams is coordinating. Your clients are being contacted.',
            'incident', 'activated',
            'CriticalIncident', 'inc_sarah_active',
            null,
            $unread,
            now()->subDays(2)->toDateTimeString()
        );

        // CS invoice sent
        $act('p_sarah', 'provider', 'payment',
            'Invoice received from Marcus Williams',
            'info',
            'Invoice #CS-INV-0001 for $250.00 has been sent. Due in 30 days.',
            'invoices', 'invoice_received',
            'CsInvoice', 'cs_inv_marcus_paid',
            null,
            $read,
            now()->subDays(20)->toDateTimeString()
        );

        // Plan annual review overdue warning
        $act('p_sarah', 'provider', 'compliance',
            'Annual Plan Review is overdue',
            'warning',
            'Your Continuity Plan annual review was due 15 days ago. Please schedule a review with your CS.',
            'continuity_plan', 'review_overdue',
            'ContinuityPlan', 'plan_sarah',
            null,
            $unread,
            now()->subDays(15)->toDateTimeString()
        );

        // Network connection accepted
        $act('p_sarah', 'provider', 'system',
            'Dr. David Chen accepted your network request',
            'info',
            'You are now connected with David Chen in your Integrative Network.',
            'network', 'connection_accepted',
            null, null,
            null,
            $read,
            now()->subDays(45)->toDateTimeString()
        );

        // Support ticket reply
        $act('p_sarah', 'provider', 'message',
            'Support ticket update — reply from Aegis Team',
            'info',
            'Your support ticket (#COMP-0002) has received a new reply from the Aegis support team.',
            'support', 'ticket_reply',
            'Complaint', 'comp_sarah_inprogress',
            null,
            $unread,
            now()->subDays(3)->toDateTimeString()
        );

        // Referral accepted
        $act('p_sarah', 'provider', 'referral',
            'Referral accepted by Dr. Maria Santos',
            'info',
            'Maria Santos accepted your referral for client care continuation.',
            'referrals', 'referral_accepted',
            'Referral', 'ref_sarah_to_maria',
            null,
            $read,
            now()->subDays(10)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // p_david — Provider portal
        // ══════════════════════════════════════════════════════════════════════

        // Incident reported on his plan — WARNING, unread
        $act('p_david', 'provider', 'incident',
            'An incident has been reported on your plan',
            'warning',
            'Support Steward James Carter has reported a Critical Incident (Missing Person). A CS will be assigned to verify.',
            'incident', 'reported',
            'CriticalIncident', 'inc_david_reported',
            null,
            $unread,
            now()->subHours(6)->toDateTimeString()
        );

        // Plan still in draft reminder
        $act('p_david', 'provider', 'compliance',
            'Your Continuity Plan is still in draft',
            'warning',
            'Your plan has not been signed. Assign a Continuity Steward to complete your setup.',
            'continuity_plan', 'draft_reminder',
            'ContinuityPlan', 'plan_david',
            null,
            $read,
            now()->subDays(7)->toDateTimeString()
        );

        // CS request incoming
        $act('p_david', 'provider', 'system',
            'Marcus Williams has requested to serve as your CS',
            'info',
            'Marcus Williams has sent a Continuity Steward request. Review and accept to activate your plan.',
            'stewards', 'cs_request_incoming',
            'ContinuityPlan', 'plan_david',
            null,
            $unread,
            now()->subDays(2)->toDateTimeString()
        );

        // Support ticket opened acknowledgement
        $act('p_david', 'provider', 'message',
            'Support ticket submitted — #COMP-0001',
            'info',
            'Your support ticket has been received. Our team will respond within 24 hours.',
            'support', 'ticket_created',
            'Complaint', 'comp_david_open',
            null,
            $read,
            now()->subDays(1)->toDateTimeString()
        );

        // News announcement
        $act('p_david', 'provider', 'news',
            'Platform Announcement: New Vault Features Available',
            'info',
            'Aegis has released enhanced vault features including zone-level access controls.',
            'news', 'announcement',
            null, null,
            null,
            $unread,
            now()->subDays(5)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // p_maria — Provider portal
        // ══════════════════════════════════════════════════════════════════════

        // BP contract started
        $act('p_maria', 'provider', 'payment',
            'Contract activated with Acme Health Services',
            'info',
            'Your contract with Acme Health Services is now active. Milestone 1 is due in 14 days.',
            'contracts', 'contract_activated',
            'BpContract', 'contract_acme_maria',
            null,
            $read,
            now()->subDays(30)->toDateTimeString()
        );

        // BP invoice paid
        $act('p_maria', 'provider', 'payment',
            'Invoice #BP-INV-0001 has been paid',
            'info',
            'Acme Health Services has paid Invoice #BP-INV-0001 ($1,500.00). Payment confirmed.',
            'invoices', 'invoice_paid',
            'BpInvoice', 'bp_inv_acme_paid',
            null,
            $read,
            now()->subDays(25)->toDateTimeString()
        );

        // Plan annual review due
        $act('p_maria', 'provider', 'compliance',
            'Annual Plan Review due — action required',
            'warning',
            'Your Continuity Plan annual review is overdue. Contact your CS to schedule a review session.',
            'continuity_plan', 'review_overdue',
            'ContinuityPlan', 'plan_maria',
            null,
            $unread,
            now()->subDays(8)->toDateTimeString()
        );

        // New service inquiry
        $act('p_maria', 'provider', 'message',
            'New service inquiry received',
            'info',
            'A client has submitted an inquiry for your IBS Consultation service.',
            'services', 'inquiry_received',
            null, null,
            null,
            $unread,
            now()->subDays(2)->toDateTimeString()
        );

        // Network referral sent
        $act('p_maria', 'provider', 'referral',
            'Referral sent to Dr. Sarah Johnson',
            'info',
            'You have sent a client referral to Sarah Johnson. Awaiting acceptance.',
            'referrals', 'referral_sent',
            'Referral', 'ref_maria_to_sarah',
            null,
            $read,
            now()->subDays(12)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // cs_marcus — Continuity Steward portal (fan-out from p_sarah events)
        // ══════════════════════════════════════════════════════════════════════

        // Plan signed fan-out → CS countersign confirmation
        $act('cs_marcus', 'continuity_steward', 'document',
            'You countersigned Sarah Johnson\'s Continuity Plan',
            'info',
            'Countersignature recorded. The plan is now active and Sarah Johnson has been notified.',
            'continuity_plan', 'plan_countersigned',
            'ContinuityPlan', 'plan_sarah',
            'p_sarah',
            $read,
            now()->subMonths(6)->toDateTimeString()
        );

        // Incident activated — CRITICAL, unread for CS
        $act('cs_marcus', 'continuity_steward', 'incident',
            'CRITICAL: Incident activated — Sarah Johnson',
            'critical',
            'Critical Incident inc_sarah_active is now ACTIVE. Vault access has been granted. Complete all assigned tasks immediately.',
            'incident', 'activated',
            'CriticalIncident', 'inc_sarah_active',
            'p_sarah',
            $unread,
            now()->subDays(2)->toDateTimeString()
        );

        // Incident task completed
        $act('cs_marcus', 'continuity_steward', 'task',
            'Incident task completed — Client notification sent',
            'info',
            'Task "Notify active clients" has been marked complete for incident inc_sarah_active.',
            'incident', 'task_complete',
            'CriticalIncident', 'inc_sarah_active',
            'p_sarah',
            $read,
            now()->subDays(1)->toDateTimeString()
        );

        // Vault attestation logged
        $act('cs_marcus', 'continuity_steward', 'vault',
            'Vault attestation completed — Sarah Johnson',
            'info',
            'You have completed vault attestation for Sarah Johnson\'s plan. All 8 items reviewed.',
            'vault', 'attested',
            'ContinuityPlan', 'plan_sarah',
            'p_sarah',
            $read,
            now()->subMonths(5)->toDateTimeString()
        );

        // CS request to p_david pending
        $act('cs_marcus', 'continuity_steward', 'system',
            'CS request sent to David Chen — awaiting acceptance',
            'info',
            'You have sent a Continuity Steward request to David Chen. You will be notified when accepted.',
            'stewards', 'cs_request_sent',
            'ContinuityPlan', 'plan_david',
            'p_david',
            $read,
            now()->subDays(2)->toDateTimeString()
        );

        // Invoice paid notification
        $act('cs_marcus', 'continuity_steward', 'payment',
            'Invoice #CS-INV-0001 paid by Sarah Johnson',
            'info',
            'Sarah Johnson has paid Invoice #CS-INV-0001 ($250.00). Payment received.',
            'invoices', 'invoice_paid',
            'CsInvoice', 'cs_inv_marcus_paid',
            'p_sarah',
            $read,
            now()->subDays(18)->toDateTimeString()
        );

        // Annual review overdue for both plans
        $act('cs_marcus', 'continuity_steward', 'compliance',
            'Annual review overdue — Sarah Johnson and Maria Santos',
            'warning',
            '2 practitioners in your portfolio have overdue annual plan reviews. Contact them to schedule.',
            'continuity_plan', 'review_overdue',
            null, null,
            null,
            $unread,
            now()->subDays(5)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // cs_priya — Continuity Steward portal
        // ══════════════════════════════════════════════════════════════════════

        // Added as alternate CS for sarah
        $act('cs_priya', 'continuity_steward', 'document',
            'You have been added as Alternate CS for Sarah Johnson',
            'info',
            'Sarah Johnson\'s primary CS Marcus Williams has added you as an Alternate Continuity Steward.',
            'stewards', 'steward_added',
            'ContinuityPlan', 'plan_sarah',
            'p_sarah',
            $read,
            now()->subMonths(5)->toDateTimeString()
        );

        // Incident fan-out to alternate CS
        $act('cs_priya', 'continuity_steward', 'incident',
            'Active incident on Sarah Johnson\'s plan',
            'warning',
            'A Critical Incident is active on Sarah Johnson\'s plan. Primary CS Marcus Williams is coordinating. You may be called to assist.',
            'incident', 'activated',
            'CriticalIncident', 'inc_sarah_active',
            'p_sarah',
            $unread,
            now()->subDays(2)->toDateTimeString()
        );

        // Vault attestation due for maria
        $act('cs_priya', 'continuity_steward', 'vault',
            'Vault attestation required — Maria Santos',
            'warning',
            'Maria Santos\'s annual review is overdue. Vault attestation must be completed.',
            'vault', 'attestation_due',
            'ContinuityPlan', 'plan_maria',
            'p_maria',
            $unread,
            now()->subDays(8)->toDateTimeString()
        );

        // Task assigned on maria review
        $act('cs_priya', 'continuity_steward', 'task',
            'Plan review task assigned — Maria Santos',
            'info',
            'You have been assigned a plan review task for Maria Santos. Due in 7 days.',
            'tasks', 'task_assigned',
            'ContinuityPlan', 'plan_maria',
            'p_maria',
            $read,
            now()->subDays(9)->toDateTimeString()
        );

        // CS complaint closed
        $act('cs_priya', 'continuity_steward', 'message',
            'Support ticket closed — #COMP-0004',
            'info',
            'Your submitted support ticket has been closed by the Aegis team.',
            'support', 'ticket_closed',
            'Complaint', 'comp_marcus_closed',
            null,
            $read,
            now()->subDays(30)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // cs_alternate (James Wilson) — Continuity Steward portal (invited, not yet active)
        // ══════════════════════════════════════════════════════════════════════

        // Invitation received
        $act('cs_alternate', 'continuity_steward', 'system',
            'You have been invited as CS for Sarah Johnson',
            'info',
            'Sarah Johnson has invited you to serve as an Alternate Continuity Steward. Review and accept the invitation.',
            'stewards', 'invitation_received',
            'ContinuityPlan', 'plan_sarah',
            'p_sarah',
            $unread,
            now()->subDays(5)->toDateTimeString()
        );

        // Account activation reminder
        $act('cs_alternate', 'continuity_steward', 'account',
            'Complete your CS profile to accept invitations',
            'warning',
            'Your Continuity Steward profile is incomplete. Add your credentials and coverage areas to accept stewardship invitations.',
            'account', 'profile_incomplete',
            null, null,
            null,
            $unread,
            now()->subDays(4)->toDateTimeString()
        );

        // Welcome / onboarding
        $act('cs_alternate', 'continuity_steward', 'system',
            'Welcome to Aegis — Getting Started',
            'info',
            'Your Aegis account is active. Complete your profile and start accepting Continuity Steward invitations.',
            'system', 'welcome',
            null, null,
            null,
            $read,
            now()->subDays(10)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // ss_linda — Support Steward portal
        // ══════════════════════════════════════════════════════════════════════

        // Plan signed fan-out → SS
        $act('ss_linda', 'support_steward', 'document',
            'Sarah Johnson\'s Continuity Plan is now active',
            'info',
            'The Continuity Plan you support for Sarah Johnson has been signed and is now active.',
            'continuity_plan', 'plan_signed',
            'ContinuityPlan', 'plan_sarah',
            'p_sarah',
            $read,
            now()->subMonths(6)->toDateTimeString()
        );

        // Vault attestation fan-out → SS
        $act('ss_linda', 'support_steward', 'vault',
            'Vault attestation completed for Sarah Johnson',
            'info',
            'The vault for Sarah Johnson\'s plan has been attested. You may now review scoped items per your access level.',
            'vault', 'attested',
            'ContinuityPlan', 'plan_sarah',
            'p_sarah',
            $read,
            now()->subMonths(5)->toDateTimeString()
        );

        // Incident reported by linda (she triggered this)
        $act('ss_linda', 'support_steward', 'incident',
            'Incident report submitted — Sarah Johnson',
            'info',
            'Your incident report for Sarah Johnson (Short-Term Incapacitation) has been submitted and is pending CS verification.',
            'incident', 'reported',
            'CriticalIncident', 'inc_sarah_active',
            'p_sarah',
            $read,
            now()->subDays(3)->toDateTimeString()
        );

        // Incident activated — CRITICAL, unread
        $act('ss_linda', 'support_steward', 'incident',
            'CRITICAL: Incident activated — Sarah Johnson',
            'critical',
            'The incident you reported for Sarah Johnson has been verified and activated. Tasks have been assigned. Please complete your assigned items.',
            'incident', 'activated',
            'CriticalIncident', 'inc_sarah_active',
            'p_sarah',
            $unread,
            now()->subDays(2)->toDateTimeString()
        );

        // Practitioner unresponsive flag
        $act('ss_linda', 'support_steward', 'practitioner_unresponsive_flagged',
            'Check-in concern — Sarah Johnson unresponsive',
            'warning',
            'Sarah Johnson missed a scheduled check-in. Status marked as "concern". A follow-up check-in has been scheduled.',
            'checkins', 'concern_flagged',
            null, null,
            'p_sarah',
            $read,
            now()->subDays(4)->toDateTimeString()
        );

        // SS incident task assigned
        $act('ss_linda', 'support_steward', 'task',
            'Incident task assigned — Contact emergency contacts',
            'info',
            'You have been assigned: "Contact emergency contacts" for incident inc_sarah_active. Please complete within 24 hours.',
            'incident', 'task_assigned',
            'CriticalIncident', 'inc_sarah_active',
            'p_sarah',
            $unread,
            now()->subDays(2)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // ss_james — Support Steward portal
        // ══════════════════════════════════════════════════════════════════════

        // Incident reported for david
        $act('ss_james', 'support_steward', 'incident',
            'Incident report submitted — David Chen',
            'info',
            'Your incident report for David Chen (Missing Person) has been submitted. Awaiting CS assignment and verification.',
            'incident', 'reported',
            'CriticalIncident', 'inc_david_reported',
            'p_david',
            $read,
            now()->subHours(6)->toDateTimeString()
        );

        // Check-in concern logged
        $act('ss_james', 'support_steward', 'practitioner_unresponsive_flagged',
            'David Chen is unreachable — check-in failed',
            'warning',
            'David Chen has not responded to 3 consecutive check-ins. Status: Unreachable. Incident report filed.',
            'checkins', 'unreachable_flagged',
            null, null,
            'p_david',
            $read,
            now()->subHours(8)->toDateTimeString()
        );

        // Maria plan active — ss_james supports maria
        $act('ss_james', 'support_steward', 'document',
            'Maria Santos\'s Continuity Plan is active',
            'info',
            'You have been added as a Support Steward for Maria Santos. Her plan is active.',
            'continuity_plan', 'steward_added',
            'ContinuityPlan', 'plan_maria',
            'p_maria',
            $read,
            now()->subMonths(3)->toDateTimeString()
        );

        // Vault access available for maria during review
        $act('ss_james', 'support_steward', 'vault',
            'Annual review pending — vault attestation needed',
            'warning',
            'Maria Santos\'s plan is due for annual review. Please coordinate with CS Priya Raman.',
            'vault', 'review_needed',
            'ContinuityPlan', 'plan_maria',
            'p_maria',
            $unread,
            now()->subDays(8)->toDateTimeString()
        );

        // Task from plan review
        $act('ss_james', 'support_steward', 'task',
            'Plan review task assigned — Maria Santos',
            'info',
            'You have been assigned a task for Maria Santos\'s annual plan review.',
            'tasks', 'task_assigned',
            'ContinuityPlan', 'plan_maria',
            'p_maria',
            $unread,
            now()->subDays(7)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // bp_acme — Business Partner portal
        // ══════════════════════════════════════════════════════════════════════

        // Proposal accepted fan-out
        $act('bp_acme', 'business_partner', 'document',
            'Proposal accepted — Maria Santos',
            'info',
            'Maria Santos has accepted your proposal for Clinical Admin Support. A contract has been generated.',
            'proposals', 'proposal_accepted',
            'BpProposal', 'prop_acme_accepted',
            'p_maria',
            $read,
            now()->subDays(31)->toDateTimeString()
        );

        // Contract activated
        $act('bp_acme', 'business_partner', 'document',
            'Contract activated — Maria Santos',
            'info',
            'Contract with Maria Santos is now active. First milestone is due in 14 days.',
            'contracts', 'contract_activated',
            'BpContract', 'contract_acme_maria',
            'p_maria',
            $read,
            now()->subDays(30)->toDateTimeString()
        );

        // Invoice paid
        $act('bp_acme', 'business_partner', 'payment',
            'Invoice #BP-INV-0001 paid — $1,500.00',
            'info',
            'Maria Santos has paid Invoice #BP-INV-0001. Payment of $1,500.00 has been deposited.',
            'invoices', 'invoice_paid',
            'BpInvoice', 'bp_inv_acme_paid',
            'p_maria',
            $read,
            now()->subDays(25)->toDateTimeString()
        );

        // Milestone overdue — WARNING
        $act('bp_acme', 'business_partner', 'payment',
            'Milestone overdue — Contract with Maria Santos',
            'warning',
            'Milestone "Phase 2 deliverable" is 5 days overdue. Submit or request an extension.',
            'contracts', 'milestone_overdue',
            'BpContract', 'contract_acme_maria',
            'p_maria',
            $unread,
            now()->subDays(5)->toDateTimeString()
        );

        // New job application received
        $act('bp_acme', 'business_partner', 'message',
            'New proposal received — Open position',
            'info',
            'A practitioner has submitted a proposal for your open position. Review in your jobs dashboard.',
            'jobs', 'proposal_received',
            'BpJob', 'job_acme_open1',
            null,
            $unread,
            now()->subDays(3)->toDateTimeString()
        );

        // Team member added
        $act('bp_acme', 'business_partner', 'account',
            'Team member added — Tanya Brooks',
            'info',
            'Tanya Brooks has been added to your Nexus Consulting team as Specialist.',
            'team', 'member_added',
            null, null,
            null,
            $read,
            now()->subDays(14)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // bp_jamal — Business Partner portal
        // ══════════════════════════════════════════════════════════════════════

        // Proposal pending confirmation
        $act('bp_jamal', 'business_partner', 'document',
            'Proposal submitted — Sarah Johnson',
            'info',
            'Your proposal for Sarah Johnson has been submitted and is pending review.',
            'proposals', 'proposal_submitted',
            'BpProposal', 'prop_jamal_pending',
            'p_sarah',
            $read,
            now()->subDays(5)->toDateTimeString()
        );

        // Proposal declined
        $act('bp_jamal', 'business_partner', 'document',
            'Proposal declined — previous position',
            'info',
            'A proposal you submitted has been declined. You may withdraw or submit a revised proposal.',
            'proposals', 'proposal_declined',
            'BpProposal', 'prop_jamal_declined',
            null,
            $read,
            now()->subDays(15)->toDateTimeString()
        );

        // Support ticket resolved
        $act('bp_jamal', 'business_partner', 'message',
            'Support ticket resolved — #COMP-0003',
            'info',
            'Your support ticket has been resolved. Review the resolution and close if satisfied.',
            'support', 'ticket_resolved',
            'Complaint', 'comp_jamal_resolved',
            null,
            $unread,
            now()->subDays(7)->toDateTimeString()
        );

        // Invoice overdue
        $act('bp_jamal', 'business_partner', 'payment',
            'Invoice #BP-INV-0004 is overdue',
            'warning',
            'Invoice #BP-INV-0004 ($750.00) is 10 days overdue. Contact your client or submit a payment reminder.',
            'invoices', 'invoice_overdue',
            'BpInvoice', 'bp_inv_jamal_overdue',
            null,
            $unread,
            now()->subDays(10)->toDateTimeString()
        );

        // Profile approved
        $act('bp_jamal', 'business_partner', 'account',
            'Your BP profile has been approved',
            'info',
            'Your Business Partner profile has been reviewed and approved. You can now browse and apply for open positions.',
            'account', 'profile_approved',
            null, null,
            null,
            $read,
            now()->subDays(60)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // admin_root — Admin portal
        // ══════════════════════════════════════════════════════════════════════

        // User locked action confirmation
        $act('admin_root', 'admin', 'account',
            'User account locked — Alex Reed',
            'info',
            'You have locked the account of Alex Reed (p_locked). Reason: Multiple failed login attempts.',
            'users', 'user_locked',
            null, null,
            null,
            $read,
            now()->subDays(2)->toDateTimeString()
        );

        // Complaint assigned
        $act('admin_root', 'admin', 'message',
            'Support ticket assigned to you — #COMP-0002',
            'info',
            'Support ticket #COMP-0002 from Sarah Johnson has been assigned to you.',
            'support', 'ticket_assigned',
            'Complaint', 'comp_sarah_inprogress',
            null,
            $read,
            now()->subDays(4)->toDateTimeString()
        );

        // Complaint resolved
        $act('admin_root', 'admin', 'message',
            'Support ticket resolved — #COMP-0003',
            'info',
            'You have resolved support ticket #COMP-0003 submitted by Jamal Torres.',
            'support', 'ticket_resolved',
            'Complaint', 'comp_jamal_resolved',
            null,
            $read,
            now()->subDays(7)->toDateTimeString()
        );

        // Stripe webhook failed payment alert
        $act('admin_root', 'admin', 'payment',
            'Stripe webhook: payment_intent.payment_failed',
            'warning',
            'A payment from p_sarah has failed. Invoice #CS-INV-0002 ($500.00). Review payment logs.',
            'payments', 'payment_failed',
            null, null,
            null,
            $unread,
            now()->subDays(5)->toDateTimeString()
        );

        // System: new user registered
        $act('admin_root', 'admin', 'system',
            'New user registered — Jordan Lee',
            'info',
            'Jordan Lee (p_access_only) has completed registration. Role: Practitioner, Tier: Access.',
            'users', 'user_registered',
            null, null,
            null,
            $read,
            now()->subDays(14)->toDateTimeString()
        );

        // Package override applied
        $act('admin_root', 'admin', 'account',
            'Package override applied — Practice tier',
            'info',
            'You have applied a feature flag override to the Practice tier package.',
            'packages', 'override_applied',
            null, null,
            null,
            $read,
            now()->subDays(1)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // p_locked — Provider portal (limited activity before lock)
        // ══════════════════════════════════════════════════════════════════════

        $act('p_locked', 'provider', 'account',
            'Your account has been locked',
            'critical',
            'Your account has been temporarily locked due to multiple failed login attempts. Contact support to restore access.',
            'account', 'account_locked',
            null, null,
            null,
            $unread,
            now()->subDays(2)->toDateTimeString()
        );

        $act('p_locked', 'provider', 'compliance',
            'Continuity Plan setup incomplete',
            'warning',
            'Your Continuity Plan setup is incomplete. Assign a Continuity Steward to protect your practice.',
            'continuity_plan', 'setup_incomplete',
            null, null,
            null,
            $read,
            now()->subDays(10)->toDateTimeString()
        );

        $act('p_locked', 'provider', 'system',
            'Welcome to Aegis',
            'info',
            'Your Aegis account has been created. Start by setting up your Continuity Plan.',
            'system', 'welcome',
            null, null,
            null,
            $read,
            now()->subDays(15)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // p_deactivated — Provider portal (historical pre-deactivation)
        // ══════════════════════════════════════════════════════════════════════

        $act('p_deactivated', 'provider', 'account',
            'Your account has been deactivated',
            'critical',
            'Your Aegis account has been deactivated. Contact support if you believe this is an error.',
            'account', 'account_deactivated',
            null, null,
            null,
            $unread,
            now()->subDays(30)->toDateTimeString()
        );

        $act('p_deactivated', 'provider', 'compliance',
            'Plan expired due to account deactivation',
            'warning',
            'Your Continuity Plan has been set to Expired following account deactivation.',
            'continuity_plan', 'plan_expired',
            'ContinuityPlan', 'plan_deactivated',
            null,
            $read,
            now()->subDays(30)->toDateTimeString()
        );

        $act('p_deactivated', 'provider', 'payment',
            'Subscription cancelled',
            'info',
            'Your Practice tier subscription has been cancelled effective immediately.',
            'account', 'subscription_cancelled',
            null, null,
            null,
            $read,
            now()->subDays(30)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // p_access_only — Provider portal (no CS, access tier)
        // ══════════════════════════════════════════════════════════════════════

        $act('p_access_only', 'provider', 'system',
            'Welcome to Aegis',
            'info',
            'Your Aegis account is active. Upgrade to Practice tier to assign a Continuity Steward and activate your plan.',
            'system', 'welcome',
            null, null,
            null,
            $read,
            now()->subDays(20)->toDateTimeString()
        );

        $act('p_access_only', 'provider', 'compliance',
            'No Continuity Steward assigned',
            'warning',
            'Your plan is in draft. Assign a Continuity Steward to begin the countersignature process.',
            'continuity_plan', 'cs_not_assigned',
            'ContinuityPlan', 'plan_access_only',
            null,
            $unread,
            now()->subDays(18)->toDateTimeString()
        );

        $act('p_access_only', 'provider', 'news',
            'Why you need a Continuity Plan — Read in the Aegis Library',
            'info',
            'Learn why every licensed practitioner needs a continuity plan and how Aegis makes it simple.',
            'news', 'article',
            null, null,
            null,
            $unread,
            now()->subDays(19)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // cs_resigned (Rachel Kim) — CS resigned from a plan
        // ══════════════════════════════════════════════════════════════════════

        $act('cs_resigned', 'continuity_steward', 'system',
            'You have resigned from Sarah Johnson\'s plan',
            'info',
            'Your resignation as Alternate CS for Sarah Johnson has been processed. Your vault access has been revoked.',
            'stewards', 'steward_resigned',
            'ContinuityPlan', 'plan_sarah',
            'p_sarah',
            $read,
            now()->subMonths(2)->toDateTimeString()
        );

        $act('cs_resigned', 'continuity_steward', 'account',
            'Vault access revoked — Sarah Johnson',
            'info',
            'Following your resignation, vault access for Sarah Johnson\'s plan has been fully revoked.',
            'vault', 'access_revoked',
            'ContinuityPlan', 'plan_sarah',
            'p_sarah',
            $read,
            now()->subMonths(2)->toDateTimeString()
        );

        $act('cs_resigned', 'continuity_steward', 'compliance',
            'Your CS portfolio is currently empty',
            'info',
            'You are not currently serving as a Continuity Steward for any practitioners. Browse the network to find new clients.',
            'stewards', 'portfolio_empty',
            null, null,
            null,
            $unread,
            now()->subMonths(2)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // bp_team_owner (Nexus Consulting)
        // ══════════════════════════════════════════════════════════════════════

        $act('bp_team_owner', 'business_partner', 'account',
            'Team member Tanya Brooks has joined',
            'info',
            'Tanya Brooks has accepted her invitation and joined the Nexus Consulting team as Specialist.',
            'team', 'member_joined',
            null, null,
            null,
            $read,
            now()->subDays(14)->toDateTimeString()
        );

        $act('bp_team_owner', 'business_partner', 'document',
            'New job posting published',
            'info',
            'Your job posting "Clinical Admin Support (Part-Time)" is now live and accepting proposals.',
            'jobs', 'job_published',
            'BpJob', 'job_acme_open1',
            null,
            $read,
            now()->subDays(20)->toDateTimeString()
        );

        $act('bp_team_owner', 'business_partner', 'payment',
            'Invoice #BP-INV-0002 sent',
            'info',
            'Invoice #BP-INV-0002 has been sent to your client. Payment due in 30 days.',
            'invoices', 'invoice_sent',
            'BpInvoice', 'bp_inv_acme_sent',
            null,
            $unread,
            now()->subDays(3)->toDateTimeString()
        );

        $act('bp_team_owner', 'business_partner', 'system',
            'Profile verification complete',
            'info',
            'Nexus Consulting\'s business profile has been verified. You are now eligible for featured listings.',
            'account', 'profile_verified',
            null, null,
            null,
            $read,
            now()->subDays(45)->toDateTimeString()
        );

        $act('bp_team_owner', 'business_partner', 'compliance',
            'Annual review of team member permissions recommended',
            'info',
            'It has been 90 days since your last team permission review. Review access levels for all team members.',
            'team', 'permission_review',
            null, null,
            null,
            $unread,
            now()->subDays(1)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // bp_team_member (Tanya Brooks)
        // ══════════════════════════════════════════════════════════════════════

        $act('bp_team_member', 'business_partner', 'account',
            'You have joined Nexus Consulting',
            'info',
            'Welcome to the Nexus Consulting team on Aegis. You have been assigned the Specialist role.',
            'team', 'team_joined',
            null, null,
            null,
            $read,
            now()->subDays(14)->toDateTimeString()
        );

        $act('bp_team_member', 'business_partner', 'task',
            'Task assigned by team lead',
            'info',
            'A task has been assigned to you by Nexus Consulting. Review details in your dashboard.',
            'tasks', 'task_assigned',
            null, null,
            null,
            $unread,
            now()->subDays(3)->toDateTimeString()
        );

        $act('bp_team_member', 'business_partner', 'document',
            'Contract shared with you — Maria Santos',
            'info',
            'The active contract with Maria Santos has been shared with you by your team lead.',
            'contracts', 'contract_shared',
            'BpContract', 'contract_acme_maria',
            'p_maria',
            $read,
            now()->subDays(12)->toDateTimeString()
        );

        $act('bp_team_member', 'business_partner', 'system',
            'Welcome to Aegis Business Partner Portal',
            'info',
            'Your Aegis BP account is active as part of the Nexus Consulting team.',
            'system', 'welcome',
            null, null,
            null,
            $read,
            now()->subDays(14)->toDateTimeString()
        );

        $act('bp_team_member', 'business_partner', 'compliance',
            'Complete your specialist profile',
            'warning',
            'Add your skills and certifications to your profile to be assigned to practitioner contracts.',
            'account', 'profile_incomplete',
            null, null,
            null,
            $unread,
            now()->subDays(13)->toDateTimeString()
        );


        // ══════════════════════════════════════════════════════════════════════
        // INSERT ALL
        // ══════════════════════════════════════════════════════════════════════

        DB::table('activity_events')->insert($rows);

        $this->command->info('ActivitySeeder: ' . count($rows) . ' activity events seeded across all portals.');
    }
}
