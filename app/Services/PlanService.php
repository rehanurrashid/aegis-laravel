<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Events\Plan\AnnualReviewCompleted;
use App\Events\Plan\PlanReadyForCs;
use App\Events\Plan\PlanReadyForSs;
use App\Events\Plan\PlanSigned;
use App\Events\Plan\PlanVersionUpdated;
use App\Events\Plan\VaultAttested;
use App\Models\ContinuityPlan;
use App\Models\PlanIncidentConfig;
use App\Models\PlanSteward;
use App\Models\PlanTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PlanService
{
    public function __construct(private ActivityService $activity) {}

    public function createDraft(User $practitioner): ContinuityPlan
    {
        $existing = ContinuityPlan::where('practitioner_id', $practitioner->id)
            ->whereIn('status', ['draft', 'pending_review', 'active', 'annual_review_due'])
            ->first();
        if ($existing) {
            throw new RuntimeException('Practitioner already has a plan.');
        }

        return ContinuityPlan::create([
            'id'              => 'cp_' . Str::lower(Str::random(12)),
            'practitioner_id' => $practitioner->id,
            'status'          => 'draft',
            'plan_version'    => 1,
            'created_at'      => now(),
        ]);
    }

    public function updateSection(ContinuityPlan $plan, string $section, array $data): ContinuityPlan
    {
        $allowed = ['signature_name', 'signature_title'];
        $update = array_intersect_key($data, array_flip($allowed));
        if (!empty($update)) {
            $plan->update($update);
        }

        event(new PlanVersionUpdated($plan, $section));
        return $plan->fresh();
    }

    public function addTask(ContinuityPlan $plan, array $taskData): PlanTask
    {
        return PlanTask::create([
            'id'            => 'pt_' . Str::lower(Str::random(12)),
            'plan_id'       => $plan->id,
            'assigned_to'   => $taskData['assigned_to'],
            'title'         => $taskData['title'],
            'description'   => $taskData['description'] ?? null,
            'category'      => $taskData['category'] ?? null,
            'timeline'      => $taskData['timeline'] ?? null,
            'sort_order'    => $taskData['sort_order'] ?? 0,
            'is_custom'     => $taskData['is_custom'] ?? 1,
        ]);
    }

    public function removeTask(PlanTask $task): bool
    {
        return (bool) $task->delete();
    }

    public function reorderTasks(ContinuityPlan $plan, array $orderedTaskIds): void
    {
        DB::transaction(function () use ($orderedTaskIds) {
            foreach ($orderedTaskIds as $i => $taskId) {
                PlanTask::where('id', $taskId)->update(['sort_order' => $i]);
            }
        });
    }

    public function configureIncidentType(ContinuityPlan $plan, string $incidentType, array $config): PlanIncidentConfig
    {
        return PlanIncidentConfig::updateOrCreate(
            ['plan_id' => $plan->id, 'incident_type' => $incidentType],
            [
                'id'                    => 'pic_' . Str::lower(Str::random(12)),
                'is_active'             => $config['is_active'] ?? $config['enabled'] ?? 0,
                'is_optin'              => $config['is_optin'] ?? 0,
                'docs_required'         => $config['docs_required'] ?? null,
                'docs_required_other'   => $config['docs_required_other'] ?? null,
                'authorized_ss_ids'     => isset($config['authorized_ss_ids']) ? json_encode($config['authorized_ss_ids']) : null,
                'authorized_cs_ids'     => isset($config['authorized_cs_ids']) ? json_encode($config['authorized_cs_ids']) : null,
            ]
        );
    }

    /**
     * Sign the plan. Validates required sections, sets signed_at + status=active,
     * fires PlanSigned, fans out to all stewards.
     */
    public function sign(ContinuityPlan $plan, array $signature, string $ip): ContinuityPlan
    {
        // Required: at least 1 enabled incident config, at least 1 CS
        $hasConfig = PlanIncidentConfig::where('plan_id', $plan->id)->where('is_active', 1)->exists();
        if (!$hasConfig) {
            throw new RuntimeException('At least one incident type must be enabled before signing.');
        }
        $hasCs = \App\Models\PlanSteward::where('plan_id', $plan->id)
            ->where('steward_category', 'continuity_steward')
            ->where('status', 'active')
            ->exists();
        if (!$hasCs) {
            throw new RuntimeException('At least one active Continuity Steward is required to sign.');
        }

        $plan->update([
            'status'           => 'active',
            'signed_at'        => now(),
            'signature_name'   => $signature['name'] ?? null,
            'signature_title'  => $signature['title'] ?? null,
            'signature_ip'     => $ip,
            'expires_at'       => now()->addYear(),
            'annual_review_date' => now()->addYear(),
        ]);

        $practitioner = User::find($plan->practitioner_id);
        event(new PlanSigned($plan->fresh(), $practitioner));

        $recipients = $this->activity->getPlanStewardRecipients($plan->id);
        $this->activity->fanOut($recipients, [
            'module'       => 'plan',
            'severity'     => ActivitySeverity::Info,
            'event_type'   => 'plan_signed',
            'title'        => "{$practitioner->display_name} signed their Continuity Plan",
            'description'  => 'The plan is now active. Your steward responsibilities are in effect.',
            'linkable_type'=> 'continuity_plan',
            'linkable_id'  => $plan->id,
            'related_user_id' => $practitioner->id,
        ]);

        return $plan->fresh();
    }

    public function attestVault(ContinuityPlan $plan, ?string $note = null): ContinuityPlan
    {
        $plan->update([
            'vault_attested_at'      => now(),
            'vault_attestation_note' => $note,
        ]);

        $practitioner = User::find($plan->practitioner_id);
        event(new VaultAttested($plan->fresh(), $practitioner));

        $recipients = $this->activity->getPlanStewardRecipients($plan->id);
        $this->activity->fanOut($recipients, [
            'module'      => 'vault',
            'severity'    => ActivitySeverity::Info,
            'event_type'  => 'vault_attested',
            'title'       => "{$practitioner->display_name} attested vault contents",
            'description' => 'The provider has confirmed essential supplemental information is in the Vault.',
            'linkable_type' => 'continuity_plan',
            'linkable_id'  => $plan->id,
            'related_user_id' => $practitioner->id,
        ]);

        return $plan->fresh();
    }

    public function beginAnnualReview(ContinuityPlan $plan): ContinuityPlan
    {
        $plan->update(['status' => 'annual_review_due']);
        return $plan->fresh();
    }

    /**
     * Complete an annual review. Validates 8-item checklist, increments version,
     * resets review timer, fans out.
     */
    public function completeAnnualReview(ContinuityPlan $plan, array $checklist, ?string $notes = null): ContinuityPlan
    {
        $required = ['stewards', 'incidents', 'documents', 'vault', 'tasks', 'fees', 'contacts', 'preferences'];
        foreach ($required as $key) {
            if (empty($checklist[$key])) {
                throw new RuntimeException("Annual review checklist incomplete: {$key}");
            }
        }

        $plan->update([
            'status'              => 'active',
            'last_review_at'      => now(),
            'annual_review_notes' => $notes,
            'annual_review_date'  => now()->addYear(),
            'expires_at'          => now()->addYear(),
            'plan_version'        => ($plan->plan_version ?? 1) + 1,
        ]);

        $practitioner = User::find($plan->practitioner_id);
        event(new AnnualReviewCompleted($plan->fresh()));

        $recipients = $this->activity->getPlanStewardRecipients($plan->id);
        $this->activity->fanOut($recipients, [
            'module'      => 'plan',
            'severity'    => ActivitySeverity::Info,
            'event_type'  => 'annual_review_completed',
            'title'       => "{$practitioner->display_name} completed annual review",
            'description' => 'Plan is renewed for another year. Please review any updated responsibilities.',
            'linkable_type' => 'continuity_plan',
            'linkable_id'  => $plan->id,
            'related_user_id' => $practitioner->id,
        ]);

        return $plan->fresh();
    }

    public function submitForReview(ContinuityPlan $plan): ContinuityPlan
    {
        $plan->update(['status' => 'review_pending']);

        $stewards = PlanSteward::where('plan_id', $plan->id)
            ->where('status', 'active')
            ->get();

        foreach ($stewards as $steward) {
            $portal = $steward->steward_category === 'support_steward' ? 'support_steward' : 'continuity_steward';
            $this->activity->log(
                $steward->steward_id, $portal, 'plan', ActivitySeverity::Info,
                'plan_ready_for_review',
                'Continuity Plan is ready for your review',
                'Please review and sign the plan.',
                'continuity_plan', $plan->id, $plan->practitioner_id
            );

            if ($steward->steward_category === 'support_steward') {
                event(new PlanReadyForSs($plan, $steward));
            } else {
                event(new PlanReadyForCs($plan, $steward));
            }
        }

        return $plan->fresh();
    }

    public function publishVersion(ContinuityPlan $plan, string $changeSummary): ContinuityPlan
    {
        $plan->update(['version' => ($plan->version ?? 1) + 1, 'last_published_at' => now()]);

        $stewards = PlanSteward::where('plan_id', $plan->id)
            ->where('status', 'active')
            ->get();

        foreach ($stewards as $steward) {
            $portal = $steward->steward_category === 'support_steward' ? 'support_steward' : 'continuity_steward';
            $this->activity->log(
                $steward->steward_id, $portal, 'plan', ActivitySeverity::Info,
                'plan_version_updated',
                'Continuity Plan has been updated',
                $changeSummary,
                'continuity_plan', $plan->id, $plan->practitioner_id
            );
        }

        event(new PlanVersionUpdated($plan->fresh(), $changeSummary));
        return $plan->fresh();
    }

    /**
     * Compute the 8-section checklist shape defined in the spec §1.
     * Returns array of {key, title, complete, blocks_signing, href, warning}.
     */
    public function computeSections(ContinuityPlan $plan): array
    {
        $practitioner = User::find($plan->practitioner_id);

        // Section 1 — Practice Info (columns: display_name, phone, title, organization)
        $practiceInfoComplete = !empty($practitioner->display_name)
            && !empty($practitioner->phone)
            && !empty($practitioner->title);

        // Section 2 — Continuity Stewards
        $csCount = PlanSteward::where('plan_id', $plan->id)
            ->where('steward_category', 'continuity_steward')
            ->whereIn('status', ['active'])
            ->count();

        // Section 3 — Support Stewards
        $ssCount = PlanSteward::where('plan_id', $plan->id)
            ->where('steward_category', 'support_steward')
            ->whereIn('status', ['active'])
            ->count();

        // Section 4 — Incident Types
        $activeIncidentCount = PlanIncidentConfig::where('plan_id', $plan->id)
            ->where('is_active', 1)
            ->count();

        // Section 5 — Response Tasks (tasks are plan-level, not per-incident)
        $tasksComplete = PlanTask::where('plan_id', $plan->id)->exists();

        // Section 6 — Vault
        $vaultAttested = !is_null($plan->vault_attested_at);

        // Section 7 — Documents (conditional)
        $hasFeeCs = PlanSteward::where('plan_id', $plan->id)
            ->where('steward_category', 'continuity_steward')
            ->where('status', 'active')
            ->where('fee_cents', '>', 0)
            ->exists();
        $docsComplete = !$hasFeeCs
            || \App\Models\ContinuityDocument::where('plan_id', $plan->id)
                ->where('doc_type', 'cs_engagement_agreement')
                ->where('status', 'fully_executed')
                ->exists();

        // Section 8 — Sign (terminal)
        $signed = !is_null($plan->signed_at);

        return [
            ['key' => 'practice_info',       'title' => 'Practice Info',          'complete' => $practiceInfoComplete, 'blocks_signing' => true,  'href' => '/provider/profile',              'warning' => null],
            ['key' => 'continuity_stewards',  'title' => 'Continuity Stewards',    'complete' => $csCount >= 1,         'blocks_signing' => true,  'href' => '/provider/continuity-stewards',  'warning' => null],
            ['key' => 'support_stewards',     'title' => 'Support Stewards',       'complete' => $ssCount >= 1,         'blocks_signing' => false, 'href' => '/provider/support-stewards',     'warning' => 'Recommended but not required'],
            ['key' => 'incident_types',       'title' => 'Incident Types',         'complete' => $activeIncidentCount >= 1, 'blocks_signing' => true, 'href' => '/provider/continuity-plan#incident-grid', 'warning' => null],
            ['key' => 'response_tasks',       'title' => 'Response Tasks',         'complete' => $tasksComplete,        'blocks_signing' => true,  'href' => '/provider/continuity-plan#incident-grid', 'warning' => null],
            ['key' => 'vault',                'title' => 'Vault',                  'complete' => $vaultAttested,        'blocks_signing' => true,  'href' => '/provider/vault',                'warning' => null],
            ['key' => 'documents',            'title' => 'Documents',              'complete' => $docsComplete,         'blocks_signing' => $hasFeeCs, 'href' => '/provider/important-documents', 'warning' => $hasFeeCs && !$docsComplete ? 'CS engagement agreement required' : null],
            ['key' => 'sign',                 'title' => 'Sign Plan',              'complete' => $signed,               'blocks_signing' => false, 'href' => null,                             'warning' => null],
        ];
    }

    public function canSign(ContinuityPlan $plan): bool
    {
        $sections = $this->computeSections($plan);
        // Sections 0-indexed: 0=practice_info, 1=cs, 2=ss(skip), 3=incident, 4=tasks, 5=vault, 6=docs
        $blockingKeys = ['practice_info', 'continuity_stewards', 'incident_types', 'response_tasks', 'vault'];
        $keyedSections = collect($sections)->keyBy('key');

        foreach ($blockingKeys as $k) {
            if (!($keyedSections[$k]['complete'] ?? false)) {
                return false;
            }
        }

        // Conditional: docs blocks if CS has fee
        $docs = $keyedSections['documents'] ?? null;
        if ($docs && $docs['blocks_signing'] && !$docs['complete']) {
            return false;
        }

        return is_null($plan->signed_at) || $plan->status?->value === 'annual_review_due';
    }

    public function canActivate(ContinuityPlan $plan, User $user): bool
    {
        if ($plan->practitioner_id !== $user->id) return false;
        if ($plan->status?->value !== 'active') return false;
        return !\App\Models\CriticalIncident::where('plan_id', $plan->id)
            ->whereIn('status', ['active', 'pending'])
            ->exists();
    }

    public function getForPractitioner(string $practitionerId): ?ContinuityPlan
    {
        return ContinuityPlan::where('practitioner_id', $practitionerId)
            ->orderByDesc('created_at')
            ->first();
    }

    public function getStatus(ContinuityPlan $plan): string
    {
        return $plan->status;
    }
}
