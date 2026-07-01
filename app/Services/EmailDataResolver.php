<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BpMilestone;
use App\Models\BpProposal;
use App\Models\BpInvoice;
use App\Models\BpPayout;
use App\Models\ContinuityDocument;
use App\Models\ContinuityPlan;
use App\Models\IncidentTask;
use App\Models\PlanSteward;
use App\Models\User;

/**
 * Hydrates email merge-fields in two passes:
 *
 * 1. Universal fields from the recipient User (recipient_name, portal_url, …)
 * 2. Entity-specific fields from IDs the listener passed (milestone_id, document_id, …)
 *
 * Caller-supplied values always win — only fills blanks.
 */
class EmailDataResolver
{
    public function enrich(string $template, array $data, ?string $userId): array
    {
        $userId = $userId ?? ($data['user_id'] ?? null);
        $base   = rtrim((string) config('app.url'), '/');

        // Pass 1 — universal recipient fields.
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $data += array_filter([
                    'recipient_name'  => $user->display_name,
                    'recipient_email' => $user->email,
                    'role_label'      => $user->role?->label(),
                    'portal_url'      => $base . '/' . ($user->role?->portal() ?? 'provider'),
                    'settings_url'    => $base . '/settings',
                ], static fn ($v) => $v !== null && $v !== '');
            }
        }

        // Pass 2 — entity-specific by domain segment.
        $domain = explode('.', $template)[1] ?? '';

        if ($domain === 'bp') {
            $data = $this->enrichBp($data, $base);
        } elseif ($domain === 'gaps') {
            $data = $this->enrichGaps($data, $base);
        } elseif ($domain === 'steward') {
            $data = $this->enrichSteward($template, $data, $base);
        } elseif ($domain === 'incident') {
            $data = $this->enrichIncident($data, $base);
        } elseif ($domain === 'plan') {
            $data = $this->enrichPlan($data, $base);
        } elseif ($domain === 'support') {
            $data = $this->enrichSupport($data, $base);
        } elseif ($domain === 'messages') {
            $data = $this->enrichMessages($data, $base);
        }

        return $data;
    }

    private function enrichBp(array $d, string $base): array
    {
        if (! empty($d['milestone_id'])) {
            $m = BpMilestone::with('contract')->find($d['milestone_id']);
            if ($m) {
                $contract = $m->contract;
                $d += [
                    'milestone_title'   => $m->title,
                    'contract_title'    => $contract?->title ?? 'Contract #' . ($contract?->id ?? ''),
                    'milestone_url'     => $base . '/bp/contracts/' . ($contract?->id ?? ''),
                    'bp_name'           => User::find($contract?->bp_id)?->display_name ?? '',
                    'practitioner_name' => User::find($contract?->practitioner_id)?->display_name ?? '',
                    'submitted_at'      => $m->submitted_at?->toFormattedDateString() ?? '',
                    'approved_at'       => $m->approved_at?->toFormattedDateString() ?? '',
                    'payout_eta'        => $m->approved_at?->addDays(3)->toFormattedDateString()
                                          ?? 'Within 3 business days',
                ];
            }
        }
        if (! empty($d['proposal_id'])) {
            $p = BpProposal::find($d['proposal_id']);
            if ($p) {
                $d += [
                    'proposal_title'    => $p->job?->title ?? 'Proposal #' . $p->id,
                    'proposal_url'      => $base . '/provider/jobs/' . ($p->job_id ?? ''),
                    'bp_name'           => User::find($p->bp_id)?->display_name ?? '',
                    'practitioner_name' => User::find($p->job?->practitioner_id)?->display_name ?? '',
                    'submitted_at'      => $p->submitted_at?->toFormattedDateString() ?? '',
                ];
                // If contract_id is also supplied (for proposal-accepted email), enrich it too
                if (! empty($d['contract_id'])) {
                    $c = \App\Models\BpContract::find($d['contract_id']);
                    $d += [
                        'contract_title'    => $c?->title ?? '',
                        'contract_url'      => $base . '/bp/contracts/' . ($d['contract_id'] ?? ''),
                        'accepted_at'       => $d['accepted_at'] ?? now()->toFormattedDateString(),
                    ];
                }
            }
        }
        if (! empty($d['contract_id']) && empty($d['proposal_id'])) {
            // Contract-only enrichment (bp/35-contract-created, gaps/66-contract-signed)
            $c = \App\Models\BpContract::find($d['contract_id']);
            if ($c) {
                $d += [
                    'contract_title'    => $c->title,
                    'contract_url'      => $base . '/bp/contracts/' . $c->id,
                    'bp_name'           => User::find($c->bp_id)?->display_name ?? '',
                    'practitioner_name' => User::find($c->practitioner_id)?->display_name ?? '',
                    'created_at'        => $c->created_at?->toFormattedDateString() ?? '',
                ];
            }
        }
        if (! empty($d['invoice_id'])) {
            // Invoice enrichment (bp/38-invoice-received, bp/39-invoice-paid)
            $inv = \App\Models\BpInvoice::find($d['invoice_id']);
            if ($inv) {
                $d += [
                    'invoice_title'     => $inv->invoice_number ?? 'Invoice #' . $inv->id,
                    'invoice_url'       => $base . '/provider/finances',
                    'bp_name'           => User::find($inv->bp_id)?->display_name ?? '',
                    'practitioner_name' => User::find($inv->practitioner_id)?->display_name ?? '',
                    'amount'            => '$' . number_format(($inv->amount_cents ?? 0) / 100, 2),
                    'due_date'          => $inv->due_at?->toFormattedDateString() ?? '',
                    'paid_at'           => $d['paid_at'] ?? ($inv->paid_at?->toFormattedDateString() ?? ''),
                ];
            }
        }
        if (! empty($d['payout_id'])) {
            // Payout enrichment (bp/40-payout-released)
            $payout = \App\Models\BpPayout::find($d['payout_id']);
            if ($payout) {
                $d += [
                    'amount'      => '$' . number_format(($payout->amount_cents ?? 0) / 100, 2),
                    'payout_url'  => $base . '/bp/finances',
                    'released_at' => $d['released_at'] ?? ($payout->paid_at?->toFormattedDateString() ?? now()->toFormattedDateString()),
                ];
            }
        }
        return $d;
    }

    private function enrichGaps(array $d, string $base): array
    {
        if (! empty($d['document_id'])) {
            $doc = ContinuityDocument::with('practitioner')->find($d['document_id']);
            if ($doc) {
                $steward = PlanSteward::where('plan_id', $doc->plan_id)
                    ->where('status', 'active')
                    ->where('steward_type', 'continuity_steward')
                    ->with('steward')
                    ->first();
                $d += [
                    'document_title'    => $doc->title,
                    'document_url'      => $base . '/provider/documents/' . $doc->id,
                    'practitioner_name' => $doc->practitioner?->display_name ?? '',
                    'steward_name'      => $steward?->steward?->display_name ?? '',
                    'new_expiry_date'   => $doc->expires_at?->toFormattedDateString() ?? '',
                ];
            }
        }
        if (! empty($d['contract_id'])) {
            // gaps/66-contract-signed, gaps/67-contract-cancelled
            $c = \App\Models\BpContract::find($d['contract_id']);
            if ($c) {
                $recipientId = $d['user_id'] ?? null;
                $counterpartyId = $recipientId === $c->practitioner_id ? $c->bp_id : $c->practitioner_id;
                $d += [
                    'contract_title'    => $c->title,
                    'contract_url'      => $base . '/provider/support-services',
                    'counterparty_name' => User::find($counterpartyId)?->display_name ?? '',
                    'bp_name'           => User::find($c->bp_id)?->display_name ?? '',
                    'practitioner_name' => User::find($c->practitioner_id)?->display_name ?? '',
                    'signed_at'         => $d['signed_at'] ?? ($c->fully_executed_at?->toFormattedDateString() ?? ''),
                    'cancel_reason'     => $d['reason'] ?? '',
                ];
            }
        }
        return $d;
    }

    private function enrichSteward(string $tpl, array $d, string $base): array
    {
        if (! empty($d['plan_steward_id'])) {
            $ps = PlanSteward::with(['steward', 'plan'])->find($d['plan_steward_id']);
            if ($ps) {
                $practitioner = User::find($ps->plan?->practitioner_id);
                $d += [
                    'cs_name'           => $ps->steward?->display_name ?? '',
                    'practitioner_name' => $practitioner?->display_name ?? '',
                    'plan_url'          => $base . '/provider/plan',
                    'review_url'        => $base . '/provider/stewards',
                    'requested_at'      => now()->toFormattedDateString(),
                    'activated_at'      => now()->toFormattedDateString(),
                ];
                if (str_contains($tpl, '25-alternate')) {
                    $former = PlanSteward::where('plan_id', $ps->plan_id)
                        ->where('steward_id', '!=', $ps->steward_id)
                        ->where('steward_type', 'continuity_steward')
                        ->with('steward')
                        ->first();
                    $d += [
                        'alternate_cs_name' => $ps->steward?->display_name ?? '',
                        'former_cs_name'    => $former?->steward?->display_name ?? '',
                    ];
                }
            }
        }
        return $d;
    }

    private function enrichIncident(array $d, string $base): array
    {
        if (! empty($d['incident_task_id'])) {
            $task = IncidentTask::with('incident')->find($d['incident_task_id']);
            if ($task) {
                $incident     = $task->incident;
                $practitioner = User::find($incident?->practitioner_id);
                $d += [
                    'task_title'        => $task->title,
                    'task_note'         => $task->description ?? '',
                    'task_due_date'     => '',
                    'task_url'          => $base . '/cs/incidents/' . ($incident?->id ?? ''),
                    'practitioner_name' => $practitioner?->display_name ?? '',
                    'cs_name'           => User::find($task->assigned_to_id)?->display_name ?? '',
                ];
            }
        }
        return $d;
    }

    private function enrichPlan(array $d, string $base): array
    {
        $planId = $d['plan_id'] ?? null;
        if (! $planId) {
            return $d;
        }
        $plan = ContinuityPlan::find($planId);
        if (! $plan) {
            return $d;
        }
        $practitioner = User::find($plan->practitioner_id);
        $d += [
            'plan_title'        => $plan->title ?? 'Continuity Plan',
            'plan_url'          => $base . '/provider/plan',
            'practitioner_name' => $practitioner?->display_name ?? '',
            'updated_at'        => now()->toFormattedDateString(),
        ];
        if (! empty($d['plan_steward_id'])) {
            $ps = PlanSteward::with('steward')->find($d['plan_steward_id']);
            if ($ps) {
                $d += [
                    'cs_name'  => $ps->steward?->display_name ?? '',
                    'ss_name'  => $ps->steward?->display_name ?? '',
                    'sign_url' => $base . '/provider/plan/sign',
                ];
            }
        }
        return $d;
    }

    private function enrichSupport(array $d, string $base): array
    {
        if (!empty($d['complaint_id'])) {
            $ticket = \App\Models\Complaint::find($d['complaint_id']);
            if ($ticket) {
                $submitter = User::find($ticket->submitter_id);
                $portal    = $submitter?->role?->portal() ?? 'provider';
                $d += [
                    'ticket_id'       => $ticket->id,
                    'ticket_subject'  => $ticket->subject,
                    'ticket_body'     => $ticket->body,
                    'ticket_status'   => $ticket->status,
                    'ticket_priority' => $ticket->priority,
                    'ticket_category' => $ticket->category,
                    'ticket_url'      => $base . '/' . $portal . '/support',
                    'submitted_at'    => $ticket->created_at?->toFormattedDateString() ?? '',
                    'resolved_at'     => $ticket->resolved_at?->toFormattedDateString() ?? '',
                    'feedback_type'   => match ($ticket->category) {
                        'bug'             => 'Bug Report',
                        'feature_request' => 'Feature Request',
                        'feedback'        => 'General Feedback',
                        default           => 'Support Ticket',
                    },
                ];
            }
        }

        if (!empty($d['reply_id'])) {
            $reply = \App\Models\ComplaintReply::with('author')->find($d['reply_id']);
            if ($reply) {
                $d += [
                    'reply_body'    => $reply->body,
                    'replier_name'  => $reply->author?->display_name ?? 'Support',
                    'reply_preview' => \Illuminate\Support\Str::limit($reply->body, 200),
                    'replied_at'    => $reply->created_at?->toFormattedDateString() ?? '',
                ];
            }
        }

        return $d;
    }

    private function enrichMessages(array $d, string $base): array
    {
        if (!empty($d['thread_id'])) {
            $thread = \App\Models\MessageThread::find($d['thread_id']);
            if ($thread) {
                $recipientUser = User::find($d['user_id'] ?? null);
                $portal        = $recipientUser?->role?->portal() ?? 'provider';
                $d += [
                    'thread_title' => $thread->title ?? 'Direct Message',
                    'messages_url' => $base . '/' . $portal . '/messages?thread=' . $thread->id,
                ];
            }
        }

        if (!empty($d['message_id'])) {
            $msg = \App\Models\Message::find($d['message_id']);
            if ($msg) {
                $sender = User::find($msg->sender_id);
                $d += [
                    'sender_name'     => $sender?->display_name ?? 'A contact',
                    'message_preview' => \Illuminate\Support\Str::limit($msg->body ?? '', 300),
                    'sent_at'         => $msg->sent_at?->toFormattedDateString() ?? '',
                ];
            }
        }

        return $d;
    }
}
