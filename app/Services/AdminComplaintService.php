<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Support\TicketResolved;

use App\Enums\ActivitySeverity;
use App\Events\Support\TicketReplied;
use App\Models\AdminAuditLog;
use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class AdminComplaintService
{
    public function __construct(private ActivityService $activity) {}

    public function getAll(array $filters = []): Collection
    {
        $q = Complaint::query();
        if (!empty($filters['status']))   $q->where('status', $filters['status']);
        if (!empty($filters['priority'])) $q->where('priority', $filters['priority']);
        if (!empty($filters['category'])) $q->where('category', $filters['category']);
        if (!empty($filters['assigned'])) $q->where('assigned_to', $filters['assigned']);

        return $q->orderByDesc('created_at')->limit($filters['limit'] ?? 100)->get();
    }

    public function getDetail(string $complaintId): ?array
    {
        $c = Complaint::find($complaintId);
        if (!$c) return null;
        $replies = ComplaintReply::where('complaint_id', $complaintId)
            ->orderBy('created_at')->get();
        return ['complaint' => $c, 'replies' => $replies];
    }

    public function assign(User $admin, Complaint $complaint, string $assigneeId): Complaint
    {
        $complaint->update(['assigned_to' => $assigneeId]);
        $this->audit($admin, 'assign_complaint', $complaint, ['assignee' => $assigneeId]);
        return $complaint->fresh();
    }

    public function reply(User $admin, Complaint $complaint, string $body, bool $isInternal = false): ComplaintReply
    {
        $reply = ComplaintReply::create([
            'id'           => 'cpr_' . Str::lower(Str::random(12)),
            'complaint_id' => $complaint->id,
            'author_id'    => $admin->id,
            'body'         => $body,
            'is_internal'  => $isInternal ? 1 : 0,
            'created_at'   => now(),
        ]);

        if (!$isInternal) {
            $submitter = User::find($complaint->submitter_id);
            $this->activity->log(
                $complaint->submitter_id,
                $this->portalFor($submitter?->role ?? 'practitioner'),
                'support',
                ActivitySeverity::Info,
                'support_reply',
                'Admin replied to your ticket',
                Str::limit($body, 140),
                'complaint',
                $complaint->id,
                $admin->id
            );
            event(new TicketReplied($complaint, $reply));
        }

        if ($complaint->status === 'open') {
            $complaint->update(['status' => 'in_progress']);
        }

        $this->audit($admin, 'reply_complaint', $complaint, ['is_internal' => $isInternal]);
        return $reply;
    }

    public function setStatus(User $admin, Complaint $complaint, string $status): Complaint
    {
        $valid = ['open', 'in_progress', 'resolved', 'closed'];
        if (!in_array($status, $valid, true)) {
            throw new \RuntimeException("Invalid complaint status: {$status}");
        }

        $update = ['status' => $status];
        if (in_array($status, ['resolved', 'closed'], true)) {
            $update['resolved_at'] = now();
        }
        $complaint->update($update);

        $this->audit($admin, 'set_complaint_status', $complaint, ['status' => $status]);

        if (in_array($status, ['resolved', 'closed'], true)) {
            event(new TicketResolved($complaint->fresh()));
        }

        return $complaint->fresh();
    }

    public function escalate(User $admin, Complaint $complaint, string $reason): Complaint
    {
        $complaint->update([
            'priority'     => 'high',
            'escalated_at' => now(),
        ]);
        $this->audit($admin, 'escalate_complaint', $complaint, ['reason' => $reason]);

        $this->activity->log(
            $complaint->submitter_id,
            'admin',
            'support',
            ActivitySeverity::Warning,
            'complaint_escalated',
            'Your ticket was escalated',
            $reason,
            'complaint',
            $complaint->id,
            $admin->id
        );

        return $complaint->fresh();
    }

    public function getMetrics(): array
    {
        return [
            'open'        => Complaint::where('status', 'open')->count(),
            'in_progress' => Complaint::where('status', 'in_progress')->count(),
            'resolved_30d'=> Complaint::where('status', 'resolved')
                                     ->where('resolved_at', '>=', now()->subDays(30))->count(),
            'avg_response_hours' => $this->avgResponseHours(),
        ];
    }

    private function avgResponseHours(): float
    {
        // Average hours between complaint.created_at and first non-internal reply
        $rows = Complaint::join('complaint_replies', 'complaints.id', '=', 'complaint_replies.complaint_id')
            ->where('complaint_replies.is_internal', 0)
            ->groupBy('complaints.id')
            ->selectRaw('complaints.created_at, MIN(complaint_replies.created_at) as first_reply')
            ->get();
        if ($rows->isEmpty()) return 0.0;
        $sum = 0;
        foreach ($rows as $r) {
            $sum += abs(strtotime($r->first_reply) - strtotime($r->created_at));
        }
        return round(($sum / $rows->count()) / 3600, 2);
    }

    private function portalFor(string $role): string
    {
        return match ($role) {
            'practitioner'       => 'provider',
            'continuity_steward' => 'continuity_steward',
            'support_steward'    => 'support_steward',
            'business_partner'   => 'business_partner',
            'admin'              => 'admin',
            default              => 'provider',
        };
    }

    private function audit(User $admin, string $action, Complaint $complaint, array $meta = []): void
    {
        AdminAuditLog::create([
            'id'             => 'aal_' . Str::lower(Str::random(12)),
            'admin_id'       => $admin->id,
            'action'         => $action,
            'target_user_id' => $complaint->submitter_id,
            'target_type'    => 'complaint',
            'target_id'      => $complaint->id,
            'meta_json'      => json_encode($meta),
            'created_at'     => now(),
        ]);
    }
}
