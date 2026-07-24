<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Events\Document\DocumentAmendmentRequested;
use App\Events\Document\DocumentArchived;
use App\Events\Document\DocumentRequested as DocumentRequestedEvent;
use App\Events\Document\DocumentSigned;
use App\Events\Plan\DocumentReleaseRequested;
use App\Events\Plan\DocumentUpdated;
use App\Jobs\SendEmailJob;
use App\Models\ContinuityDocument;
use App\Models\ContinuityPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class DocumentService
{
    public function __construct(private ActivityService $activity) {}

    // ── Create ─────────────────────────────────────────────────────────────────

    public function create(ContinuityPlan $plan, array $data): ContinuityDocument
    {
        return ContinuityDocument::create([
            'id'               => 'cd_' . Str::lower(Str::random(12)),
            'plan_id'          => $plan->id,
            'practitioner_id'  => $plan->practitioner_id,
            'title'            => $data['title'],
            'doc_type'         => $data['doc_type'] ?? 'agreement',
            'category'         => $data['category'] ?? null,
            'body'             => $data['body'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'status'           => $data['status'] ?? 'draft',
            'party_b_id'       => $data['party_b_id'] ?? null,
            'party_c_id'       => $data['party_c_id'] ?? null,
            'holder_steward_id'=> $data['party_b_id'] ?? null,
            'effective_date'   => $data['effective_date'] ?? null,
            'expires_at'       => $data['expires_at'] ?? null,
            'auto_renew'       => $data['auto_renew'] ?? false,
            'is_supporting'    => $data['is_supporting'] ?? false,
            'related_to'       => $data['related_to'] ?? null,
            'amends_document_id' => $data['amends_document_id'] ?? null,
            'created_at'       => now(),
        ]);
    }

    // ── Request (wizard / amendment / draft) ───────────────────────────────────

    public function requestDocument(ContinuityPlan $plan, User $requester, array $data): ContinuityDocument
    {
        $isDraft     = ($data['status'] ?? 'pending_sign') === 'draft';
        $isAmendment = !empty($data['amends_document_id']);

        $doc = $this->create($plan, $data);

        // Actor log — provider created / requested
        $actionLabel = $isDraft ? 'draft saved' : ($isAmendment ? 'amendment requested' : 'agreement created');
        $this->activity->log(
            $requester->id, 'provider', 'document', ActivitySeverity::Info,
            $isDraft ? 'document_draft_saved' : ($isAmendment ? 'document_amendment_requested' : 'document_requested'),
            'You ' . $actionLabel . ': ' . $doc->title,
            $isDraft ? 'Saved as draft — not yet sent for signature.' : 'Sent for signature to your designated stewards.',
            'continuity_document', $doc->id, null,
            'log', $requester->id
        );

        if (!$isDraft) {
            // Notify stewards (notification entry in their feed)
            $recipients = $this->activity->getPlanStewardRecipients($plan->id);
            foreach ($recipients as $r) {
                $this->activity->log(
                    $r['user_id'], $r['portal'], 'document', ActivitySeverity::Info,
                    $isAmendment ? 'document_amendment_requested' : 'document_requested',
                    $requester->display_name . ' ' . ($isAmendment ? 'requests an amendment to' : 'sent you') . ': ' . $doc->title,
                    $isAmendment ? 'Please review the proposed amendment.' : 'Please review and sign at your earliest convenience.',
                    'continuity_document', $doc->id, $requester->id,
                    'notification', $requester->id
                );
            }

            if ($isAmendment) {
                event(new DocumentAmendmentRequested($doc, $requester));
            } else {
                event(new DocumentRequestedEvent($doc, $plan, $requester));
            }
        }

        return $doc;
    }

    // ── Sign ───────────────────────────────────────────────────────────────────

    public function sign(ContinuityDocument $doc, User $signer, array $signature): ContinuityDocument
    {
        $doc->update([
            'status'         => 'countersign_pending',
            'signed_at'      => now(),
            'signed_by_id'   => $signer->id,
            'signature_name' => $signature['name'] ?? $signer->display_name,
            'signature_ip'   => $signature['ip'] ?? null,
        ]);

        // Actor log
        $this->activity->log(
            $signer->id, 'provider', 'document', ActivitySeverity::Info,
            'document_signed',
            'You signed: ' . $doc->title,
            'Awaiting countersignature from your Continuity Steward.',
            'continuity_document', $doc->id, null,
            'log', $signer->id
        );

        // Notify CS (party B) — awaiting countersignature
        $recipients = $this->activity->getPlanStewardRecipients($doc->plan_id);
        foreach ($recipients as $r) {
            if ($r['portal'] === 'continuity_steward') {
                $this->activity->log(
                    $r['user_id'], $r['portal'], 'document', ActivitySeverity::Info,
                    'document_signed',
                    $signer->display_name . ' signed: ' . $doc->title,
                    'Your countersignature is now required to fully execute this agreement.',
                    'continuity_document', $doc->id, $signer->id,
                    'notification', $signer->id
                );
            }
        }

        // Also notify Party C (support steward) for tri-party agreements
        if ($doc->party_c_id) {
            $this->activity->log(
                $doc->party_c_id, 'support_steward', 'document', ActivitySeverity::Info,
                'document_signed',
                $signer->display_name . ' signed: ' . $doc->title,
                'This tri-party agreement is awaiting countersignature from all parties.',
                'continuity_document', $doc->id, $signer->id,
                'notification', $signer->id
            );
        }

        event(new DocumentSigned($doc->fresh(), $signer, false));

        return $doc->fresh();
    }

    // ── Countersign ────────────────────────────────────────────────────────────

    public function countersign(ContinuityDocument $doc, User $cs, array $signature): ContinuityDocument
    {
        $doc->update([
            'status'                => 'fully_executed',
            'countersigned_at'      => now(),
            'countersigned_by_id'   => $cs->id,
            'countersignature_name' => $signature['name'] ?? $cs->display_name,
            'countersignature_ip'   => $signature['ip'] ?? null,
        ]);

        // CS actor log
        $this->activity->log(
            $cs->id, 'continuity_steward', 'document', ActivitySeverity::Info,
            'document_countersigned',
            'You countersigned: ' . $doc->title,
            'The agreement is now fully executed.',
            'continuity_document', $doc->id, null,
            'log', $cs->id
        );

        // Notify provider — fully executed
        $this->activity->log(
            $doc->practitioner_id, 'provider', 'document', ActivitySeverity::Info,
            'document_countersigned',
            $cs->display_name . ' countersigned: ' . $doc->title,
            'The agreement is now fully executed and legally binding.',
            'continuity_document', $doc->id, $cs->id,
            'notification', $cs->id
        );

        event(new DocumentSigned($doc->fresh(), $cs, true));

        return $doc->fresh();
    }

    // ── Remind ─────────────────────────────────────────────────────────────────

    public function remind(ContinuityDocument $doc, User $sender): void
    {
        // Actor log
        $this->activity->log(
            $sender->id, 'provider', 'document', ActivitySeverity::Info,
            'document_reminder_sent',
            'Reminder sent for: ' . $doc->title,
            'A signature reminder was dispatched to the counterparty.',
            'continuity_document', $doc->id, null,
            'log', $sender->id
        );

        // Email to CS/holder
        $recipientId = $doc->party_b_id ?? $doc->holder_steward_id;
        if ($recipientId) {
            $this->activity->log(
                $recipientId, 'continuity_steward', 'document', ActivitySeverity::Info,
                'document_reminder_received',
                $sender->display_name . ' sent a signature reminder for: ' . $doc->title,
                'Please sign this agreement at your earliest convenience.',
                'continuity_document', $doc->id, $sender->id,
                'notification', $sender->id
            );

            SendEmailJob::dispatch(
                'emails.document.73-document-signed-provider',
                [
                    'document_id'       => $doc->id,
                    'document_title'    => $doc->title,
                    'practitioner_name' => $sender->display_name,
                ],
                $recipientId
            );
        }
    }

    // ── Archive / Terminate ────────────────────────────────────────────────────

    public function archive(ContinuityDocument $doc, ?User $actor = null, string $reason = 'archived'): ContinuityDocument
    {
        $isTerm = !in_array($reason, ['archived', 'superseded', 'renewed']);

        $doc->update([
            'status'      => $isTerm ? 'terminated' : 'archived',
            'archived_at' => now(),
        ]);

        if ($actor) {
            // Actor log
            $this->activity->log(
                $actor->id, 'provider', 'document', ActivitySeverity::Warning,
                $isTerm ? 'document_terminated' : 'document_archived',
                'You ' . ($isTerm ? 'terminated' : 'archived') . ': ' . $doc->title,
                'The agreement has been ' . ($isTerm ? 'terminated' : 'archived') . ' and access revoked.',
                'continuity_document', $doc->id, null,
                'log', $actor->id
            );

            // Notify Party B (CS)
            $recipientId = $doc->party_b_id ?? $doc->holder_steward_id;
            if ($recipientId && $recipientId !== $actor->id) {
                $this->activity->log(
                    $recipientId, 'continuity_steward', 'document', ActivitySeverity::Warning,
                    $isTerm ? 'document_terminated' : 'document_archived',
                    $actor->display_name . ' ' . ($isTerm ? 'terminated' : 'archived') . ': ' . $doc->title,
                    'This agreement has been ' . ($isTerm ? 'terminated' : 'archived') . '. Your access associated with it has been revoked.',
                    'continuity_document', $doc->id, $actor->id,
                    'notification', $actor->id
                );
            }

            // Notify Party C (SS) for tri-party
            if ($doc->party_c_id && $doc->party_c_id !== $actor->id) {
                $this->activity->log(
                    $doc->party_c_id, 'support_steward', 'document', ActivitySeverity::Warning,
                    $isTerm ? 'document_terminated' : 'document_archived',
                    $actor->display_name . ' ' . ($isTerm ? 'terminated' : 'archived') . ': ' . $doc->title,
                    'This agreement has been ' . ($isTerm ? 'terminated' : 'archived') . '.',
                    'continuity_document', $doc->id, $actor->id,
                    'notification', $actor->id
                );
            }

            event(new DocumentArchived($doc->fresh(), $actor, $reason));
        }

        return $doc->fresh();
    }

    // ── Query ──────────────────────────────────────────────────────────────────

    public function getForPlan(string $planId): Collection
    {
        return ContinuityDocument::where('plan_id', $planId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getForPractitioner(string $practitionerId): Collection
    {
        return ContinuityDocument::where('practitioner_id', $practitionerId)
            ->orderByDesc('created_at')
            ->get();
    }

    // ── Legacy compat ──────────────────────────────────────────────────────────

    public function requestRelease(ContinuityDocument $doc, User $requester): void
    {
        $doc->update(['status' => 'release_pending']);

        $this->activity->log(
            $doc->practitioner_id, 'provider', 'document', ActivitySeverity::Info,
            'document_release_requested',
            $requester->display_name . ' requested release of: ' . $doc->title,
            'Review the release request in your Documents section.',
            'continuity_document', $doc->id, $requester->id,
            'notification', $requester->id
        );

        event(new DocumentReleaseRequested($doc->fresh(), $requester));
    }

    public function update(ContinuityDocument $doc, array $data, User $updater): ContinuityDocument
    {
        $changeType = $data['change_type'] ?? 'content';
        $doc->update(array_filter([
            'title'      => $data['title'] ?? null,
            'body'       => $data['body'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'status'     => $data['status'] ?? null,
        ], fn ($v) => $v !== null));

        $this->activity->log(
            $updater->id, 'provider', 'document', ActivitySeverity::Info,
            'document_updated',
            'You updated: ' . $doc->title,
            'Document content was updated.',
            'continuity_document', $doc->id, null,
            'log', $updater->id
        );

        $recipients = $this->activity->getPlanStewardRecipients($doc->plan_id);
        foreach ($recipients as $r) {
            $this->activity->log(
                $r['user_id'], $r['portal'], 'document', ActivitySeverity::Info,
                'document_updated',
                $updater->display_name . ' updated: ' . $doc->title,
                'Review the latest version.',
                'continuity_document', $doc->id, $updater->id,
                'notification', $updater->id
            );
        }

        event(new DocumentUpdated($doc->fresh(), $updater, $changeType));
        return $doc->fresh();
    }
}
