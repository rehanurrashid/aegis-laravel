<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Jobs\SendEmailJob;
use App\Models\ContinuityDocument;
use App\Models\ContinuityPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class DocumentService
{
    public function __construct(private ActivityService $activity) {}

    public function create(ContinuityPlan $plan, array $data): ContinuityDocument
    {
        return ContinuityDocument::create([
            'id'              => 'cd_' . Str::lower(Str::random(12)),
            'plan_id'         => $plan->id,
            'practitioner_id' => $plan->practitioner_id,
            'title'           => $data['title'],
            'doc_type'        => $data['doc_type'] ?? 'agreement',
            'body'            => $data['body'] ?? null,
            'status'          => $data['status'] ?? 'draft',
            'created_at'      => now(),
        ]);
    }

    public function sign(ContinuityDocument $doc, User $signer, array $signature): ContinuityDocument
    {
        $doc->update([
            'status'         => 'countersign_pending',
            'signed_at'      => now(),
            'signed_by_id'   => $signer->id,
            'signature_name' => $signature['name'] ?? $signer->display_name,
            'signature_ip'   => $signature['ip'] ?? null,
        ]);

        $recipients = $this->activity->getPlanStewardRecipients($doc->plan_id);
        foreach ($recipients as $r) {
            if ($r['portal'] === 'continuity_steward') {
                $this->activity->log(
                    $r['user_id'], $r['portal'], 'document', ActivitySeverity::Info,
                    'document_signed',
                    "{$signer->display_name} signed: {$doc->title}",
                    'Awaiting your countersignature.',
                    'continuity_document', $doc->id, $signer->id
                );
            }
        }

        return $doc->fresh();
    }

    public function countersign(ContinuityDocument $doc, User $cs, array $signature): ContinuityDocument
    {
        $doc->update([
            'status'                 => 'fully_executed',
            'countersigned_at'       => now(),
            'countersigned_by_id'    => $cs->id,
            'countersignature_name'  => $signature['name'] ?? $cs->display_name,
            'countersignature_ip'    => $signature['ip'] ?? null,
        ]);

        $this->activity->log(
            $doc->practitioner_id, 'provider', 'document', ActivitySeverity::Info,
            'document_countersigned',
            "{$cs->display_name} countersigned: {$doc->title}",
            'The document is fully executed.',
            'continuity_document', $doc->id, $cs->id
        );

        return $doc->fresh();
    }

    public function requestDocument(ContinuityPlan $plan, User $requester, array $data): ContinuityDocument
    {
        $doc = $this->create($plan, array_merge($data, ['status' => 'pending_sign']));

        $recipients = $this->activity->getPlanStewardRecipients($plan->id);
        foreach ($recipients as $r) {
            $this->activity->log(
                $r['user_id'], $r['portal'], 'document', ActivitySeverity::Info,
                'document_requested',
                "{$requester->display_name} requested a document: {$doc->title}",
                'Please review and sign.',
                'continuity_document', $doc->id, $requester->id
            );
        }

        return $doc;
    }

    public function remind(ContinuityDocument $doc, User $sender): void
    {
        SendEmailJob::dispatch(
            'emails.document.36-document-reminder',
            ['document_id' => $doc->id],
            $doc->practitioner_id
        );
    }

    public function archive(ContinuityDocument $doc): ContinuityDocument
    {
        $doc->update(['status' => 'archived']);
        return $doc->fresh();
    }

    public function getForPlan(string $planId): Collection
    {
        return ContinuityDocument::where('plan_id', $planId)
            ->orderByDesc('created_at')
            ->get();
    }
}
