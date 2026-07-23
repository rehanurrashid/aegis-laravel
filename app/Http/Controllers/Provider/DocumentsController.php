<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreDocumentRequest;
use App\Http\Requests\Provider\TerminateDocumentRequest;
use App\Http\Requests\Provider\RenewDocumentRequest;
use App\Http\Requests\Provider\UploadSupportingDocRequest;
use App\Models\ContinuityDocument;
use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\PlanService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DocumentsController extends Controller
{
    public function __construct(
        private DocumentService $documents,
        private PlanService $plans,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);

        $allDocs = $this->documents->getForPractitioner($user->id)
            ->load(['signedBy', 'countersignedBy', 'holderSteward', 'amendments']);

        $now = Carbon::now();

        $continuityDocs = $allDocs->filter(fn ($d) => !(bool) $d->is_supporting)->values();
        $supportingDocs = $allDocs->filter(fn ($d) => (bool) $d->is_supporting)->values();

        $shapedDocs = $continuityDocs->map(fn ($doc) => $this->shapeDoc($doc, $user, $now));

        $shapedSupporting = $supportingDocs->map(fn ($doc) => [
            'id'            => $doc->id,
            'title'         => $doc->title,
            'doc_type'      => $doc->doc_type,
            'meta'          => implode(' · ', array_filter([
                $doc->doc_type ? Str::upper($doc->doc_type) : null,
                $doc->created_at?->format('M j, Y'),
                $doc->holderSteward?->display_name,
            ])),
            'badge_label'   => $this->badgeLabel($this->statusVal($doc)),
            'badge_variant' => $this->badgeVariant($this->statusVal($doc)),
            'related_to'    => $doc->related_to,
            'file_ref'      => $doc->file_ref,
            'has_file'      => !empty($doc->file_ref),
        ])->values();

        // Stat chips
        $expiringCount = $continuityDocs->filter(
            fn ($d) => $d->expires_at && Carbon::parse($d->expires_at)->isBetween($now, $now->copy()->addDays(30))
        )->count();

        $docStats = [
            'total'       => $continuityDocs->count(),
            'pending_my_sig'    => $continuityDocs->filter(fn ($d) => in_array($this->statusVal($d), ['pending_sign']))->count(),
            'awaiting_counter'  => $continuityDocs->filter(fn ($d) => in_array($this->statusVal($d), ['countersign_pending', 'countersign']))->count(),
            'expiring'          => $expiringCount,
            'active'            => $continuityDocs->filter(fn ($d) => in_array($this->statusVal($d), ['active', 'fully_executed']))->count(),
            'archived'          => $continuityDocs->filter(fn ($d) => in_array($this->statusVal($d), ['archived', 'terminated']))->count(),
        ];

        // Sidebar menu badges (per tab count)
        $menuBadges = [
            'all'               => $continuityDocs->count(),
            'pending_sign'      => $docStats['pending_my_sig'],
            'countersign'       => $docStats['awaiting_counter'],
            'active'            => $docStats['active'],
            'expiring'          => $expiringCount,
            'archived'          => $docStats['archived'],
            'amendments'        => $continuityDocs->filter(fn ($d) => $d->doc_type === 'plan_amendment' || !empty($d->amends_document_id))->count(),
            'supporting'        => $supportingDocs->count(),
        ];

        // Party counts — scoped to current sidebar filter for accurate pill labels
        $sidebarKey = $request->query('category', 'all');
        $sidebarBase = match ($sidebarKey) {
            'pending_sign' => $shapedDocs->filter(fn ($d) => $d['status'] === 'pending_sign'),
            'countersign'  => $shapedDocs->filter(fn ($d) => in_array($d['status'], ['countersign', 'countersign_pending'])),
            'active'       => $shapedDocs->filter(fn ($d) => in_array($d['status'], ['active', 'fully_executed'])),
            'expiring'     => $shapedDocs->filter(fn ($d) => (bool) ($d['is_expiring'] ?? false)),
            'archived'     => $shapedDocs->filter(fn ($d) => in_array($d['status'], ['archived', 'terminated'])),
            default        => $shapedDocs,
        };

        $partyCounts = [
            'all' => $sidebarBase->count(),
            'pe'  => $sidebarBase->filter(fn ($d) => ($d['category'] ?? '') === 'pe')->count(),
            'pd'  => $sidebarBase->filter(fn ($d) => ($d['category'] ?? '') === 'pd')->count(),
            'de'  => $sidebarBase->filter(fn ($d) => ($d['category'] ?? '') === 'de')->count(),
            'tri' => $sidebarBase->filter(fn ($d) => ($d['category'] ?? '') === 'tri')->count(),
        ];

        // Active counterparties for wizard party B
        $stewards = $plan
            ? PlanSteward::where('plan_id', $plan->id)
                ->where('status', 'active')
                ->get()
                ->map(function ($ps) {
                    $u = User::find($ps->steward_id);
                    return [
                        'id'       => $ps->steward_id,
                        'initials' => $this->initials($u?->display_name ?? '?'),
                        'name'     => $u?->display_name ?? 'Unknown',
                        'meta'     => Str::ucfirst(str_replace('_', ' ', $ps->steward_category ?? 'steward')) . ' · Active',
                        'category' => $ps->steward_category ?? 'steward',
                    ];
                })->values()->toArray()
            : [];

        return Inertia::render('Provider/ImportantDocuments', [
            'documents'          => $shapedDocs,
            'supportingDocs'     => $shapedSupporting,
            'docStats'           => $docStats,
            'menuBadges'         => $menuBadges,
            'partyCounts'        => $partyCounts,
            'stewards'           => $stewards,
            'planStatus'         => $plan?->status?->value ?? null,
            'annualReviewDate'   => $plan?->annual_review_date?->toISOString() ?? null,
            'hasDraftInProgress' => ContinuityPlan::where('practitioner_id', $user->id)->where('status', 'draft')->exists(),
            'draftPlanVersion'   => ContinuityPlan::where('practitioner_id', $user->id)->where('status', 'draft')->value('plan_version'),
        ]);
    }

    // ── Write actions ──────────────────────────────────────────────────────────

    public function request(StoreDocumentRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);

        $data    = $request->validated();
        $isDraft = (bool) ($data['is_draft'] ?? false);
        $isAmend = isset($data['parent_id']);

        $title = $data['title'] ?? implode(' — ', array_filter([
            $data['doc_type'] ?? null,
            $data['category'] ?? null,
            $data['reference'] ?? null,
        ])) ?: 'New Agreement';

        $doc = $this->documents->requestDocument($plan, $request->user(), [
            'title'              => $title,
            'doc_type'           => $data['doc_type'] ?? 'agreement',
            'body'               => $data['proposed'] ?? $data['notes'] ?? null,
            'status'             => $isDraft ? 'draft' : 'pending_sign',
            'category'           => $data['category'] ?? null,
            'party_b_id'         => $data['party_b_id'] ?? null,
            'effective_date'     => $data['effective_date'] ?? null,
            'expires_at'         => $data['expiry_date'] ?? null,
            'auto_renew'         => str_contains(strtolower($data['auto_renew'] ?? ''), 'yes') ? 1 : 0,
            'notes'              => $data['notes'] ?? null,
            'amends_document_id' => $data['parent_id'] ?? null,
        ]);

        // Provider signs immediately on creation — agreement goes straight to
        // countersign_pending so the counterparty is notified without an extra step.
        if (!$isDraft && !$isAmend) {
            $this->documents->sign($doc, $request->user(), [
                'name' => $request->user()->display_name,
                'ip'   => $request->ip(),
            ]);
        }

        $msg = $isDraft
            ? 'Draft saved.'
            : ($isAmend ? 'Amendment request sent.' : 'Agreement created and signed. Awaiting countersignature.');

        return back()->with('success', $msg);
    }

    public function sign(Request $request, ContinuityDocument $document): RedirectResponse
    {
        $this->authorize('sign', $document);
        $this->documents->sign($document, $request->user(), [
            'name' => $request->user()->display_name,
            'ip'   => $request->ip(),
        ]);
        return back()->with('success', 'Agreement signed. Awaiting countersignature.');
    }

    public function remind(Request $request, ContinuityDocument $document): RedirectResponse
    {
        abort_unless($document->practitioner_id === $request->user()->id, 403);
        $this->documents->remind($document, $request->user());
        return back()->with('success', 'Reminder sent.');
    }

    public function renew(RenewDocumentRequest $request, ContinuityDocument $document): RedirectResponse
    {
        abort_unless($document->practitioner_id === $request->user()->id, 403);

        $data = $request->validated();

        // Create a new doc that supersedes this one
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);

        $newDoc = $this->documents->create($plan, [
            'title'              => $document->title . ' (Renewed)',
            'doc_type'           => $document->doc_type,
            'body'               => $document->body,
            'category'           => $document->category,
            'status'             => 'pending_sign',
            'party_b_id'         => $document->party_b_id,
            'holder_steward_id'  => $document->holder_steward_id,
            'effective_date'     => $data['effective_date'] ?? now()->toDateString(),
            'expires_at'         => $data['expiry_date'] ?? null,
            'auto_renew'         => $data['auto_renew'] ?? false,
            'notes'              => $data['notes'] ?? null,
            'amends_document_id' => $document->id,
        ]);

        // Mark old doc superseded
        $document->update(['status' => 'archived', 'archived_at' => now()]);

        return back()->with('success', 'Renewal initiated. New agreement sent for signature.');
    }

    public function terminate(TerminateDocumentRequest $request, ContinuityDocument $document): RedirectResponse
    {
        $this->authorize('archive', $document);

        $data = $request->validated();
        $this->documents->archive($document, $request->user(), $data['reason']);

        return back()->with('success', 'Agreement terminated.');
    }

    public function archive(Request $request, ContinuityDocument $document): RedirectResponse
    {
        $this->authorize('archive', $document);
        $this->documents->archive($document, $request->user(), $request->input('reason', 'archived'));
        return back()->with('success', 'Document archived.');
    }

    public function upload(UploadSupportingDocRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);

        $data    = $request->validated();
        $fileRef = null;

        if ($request->hasFile('file')) {
            $path    = $request->file('file')->store("docs/plan_{$plan->id}/supporting", 'local');
            $fileRef = $path;
        }

        ContinuityDocument::create([
            'id'              => 'cd_' . Str::lower(Str::random(12)),
            'plan_id'         => $plan->id,
            'practitioner_id' => $request->user()->id,
            'title'           => $data['name'],
            'doc_type'        => strtolower(str_replace(' ', '_', $data['type'] ?? 'supporting')),
            'status'          => 'active',
            'is_supporting'   => true,
            'related_to'      => $data['related_to'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'file_ref'        => $fileRef,
            'created_at'      => now(),
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function shapeDoc(ContinuityDocument $doc, User $user, Carbon $now): array
    {
        $status     = $this->statusVal($doc);
        $expiresAt  = $doc->expires_at ? Carbon::parse($doc->expires_at) : null;
        $isExpiring = $expiresAt && $expiresAt->isBetween($now, $now->copy()->addDays(30));
        $isExpired  = $expiresAt && $expiresAt->isPast();
        $daysToExp  = $expiresAt && $expiresAt->isFuture() ? (int) $now->diffInDays($expiresAt) : null;

        // Counterparty details
        $counterparty   = null;
        $people         = [['initials' => $this->initials($user->display_name), 'color' => 'gold']];
        $partyBUser     = $doc->party_b_id ? User::find($doc->party_b_id) : null;
        $holderUser     = $doc->holderSteward;
        $counterpartUser = $partyBUser ?? $holderUser;

        if ($counterpartUser) {
            $people[] = ['initials' => $this->initials($counterpartUser->display_name), 'color' => 'dark'];
            $counterparty = [
                'id'        => $counterpartUser->id,
                'name'      => $counterpartUser->display_name,
                'initials'  => $this->initials($counterpartUser->display_name),
                'meta'      => 'Continuity Steward · Active',
                'signed_at' => $doc->countersigned_at
                    ? Carbon::parse($doc->countersigned_at)->format('M j, Y')
                    : null,
            ];
        }

        $peopleLabel = $counterpartUser
            ? ($user->display_name . ' & ' . $counterpartUser->display_name)
            : $user->display_name;

        [$whenText, $whenClass, $whenIcon] = $this->whenMeta($status, $expiresAt, $isExpiring, $isExpired, $doc);

        $primaryAction = $this->primaryAction($status);
        $canAmend      = in_array($status, ['active', 'fully_executed']) && $doc->practitioner_id === $user->id;
        $canTerminate  = !in_array($status, ['terminated', 'archived']) && $doc->practitioner_id === $user->id;
        $canRemind     = in_array($status, ['countersign_pending', 'countersign', 'pending_sign']);
        $amendCount    = $doc->amendments ? $doc->amendments->count() : 0;

        return [
            'id'               => $doc->id,
            'title'            => $doc->title,
            'reference'        => $doc->reference ?? 'DOC-' . strtoupper(substr($doc->id, -6)),
            'doc_type'         => $doc->doc_type,
            'doc_type_label'   => Str::upper($doc->doc_type ?? 'DOC') . ' · ' . $this->docTypeLabel($doc->doc_type),
            'category'         => $doc->category,
            'category_label'   => $this->catLabel($doc->category),
            'status'           => $status,
            'is_expiring'      => $isExpiring || $isExpired || $status === 'expiring',
            'badge_label'      => $this->badgeLabel($status),
            'badge_variant'    => $this->badgeVariant($status),
            'people'           => $people,
            'people_label'     => $peopleLabel,
            'when_text'        => $whenText,
            'when_class'       => $whenClass,
            'when_icon'        => $whenIcon,
            'primary_action'   => $primaryAction,
            'can_amend'        => $canAmend,
            'can_terminate'    => $canTerminate,
            'can_remind'       => $canRemind,
            'amendment_count'  => $amendCount,
            'days_until_expiry'=> $daysToExp,
            'counterparty'     => $counterparty,
            'body'             => $doc->body,
            'history'          => $this->buildHistory($doc),
            'effective_date'   => $doc->effective_date?->format('M j, Y'),
            'expiry_date'      => $expiresAt?->format('M j, Y'),
            'party_b_id'       => $doc->party_b_id,
            'amends_document_id' => $doc->amends_document_id,
        ];
    }

    private function statusVal(ContinuityDocument $doc): string
    {
        return $doc->status instanceof \App\Enums\DocumentStatus
            ? $doc->status->value
            : (string) $doc->status;
    }

    private function primaryAction(string $status): string
    {
        return match ($status) {
            'pending_sign'                         => 'sign',
            'draft'                                => 'edit',
            'countersign', 'countersign_pending'   => 'remind',
            'expiring', 'expired'                  => 'renew',
            default                                => 'view',
        };
    }

    private function whenMeta(string $status, ?Carbon $expiresAt, bool $isExpiring, bool $isExpired, ContinuityDocument $doc): array
    {
        if ($status === 'pending_sign') {
            return ['Awaiting your signature', 'is-warning', 'clock'];
        }
        if ($status === 'countersign' || $status === 'countersign_pending') {
            return ['Awaiting countersignature', 'is-info', 'clock'];
        }
        if ($status === 'draft') {
            return ['Draft — not yet sent', 'is-muted', null];
        }
        if ($status === 'terminated') {
            return [$doc->archived_at ? 'Terminated ' . Carbon::parse($doc->archived_at)->format('M j, Y') : 'Terminated', 'is-muted', null];
        }
        if ($status === 'archived') {
            return [$doc->archived_at ? 'Archived ' . Carbon::parse($doc->archived_at)->format('M j, Y') : 'Archived', 'is-muted', null];
        }
        if ($isExpired) {
            return ['Expired ' . ($expiresAt?->format('M j, Y') ?? ''), 'is-danger', 'alert-triangle'];
        }
        if ($isExpiring) {
            return ['Expires ' . ($expiresAt?->format('M j, Y') ?? ''), 'is-warning', 'calendar'];
        }
        if ($expiresAt && $expiresAt->isFuture()) {
            return ['Expires ' . $expiresAt->format('M j, Y'), 'is-muted', null];
        }
        return [$doc->issued_at ? Carbon::parse($doc->issued_at)->format('M j, Y') : '', 'is-muted', null];
    }

    private function badgeLabel(string $status): string
    {
        return match ($status) {
            'active', 'fully_executed'        => 'Active',
            'pending_sign'                    => 'Awaiting Signature',
            'countersign', 'countersign_pending' => 'Awaiting Countersig.',
            'draft'                           => 'Draft',
            'expiring'                        => 'Expiring Soon',
            'expired'                         => 'Expired',
            'archived'                        => 'Archived',
            'terminated'                      => 'Terminated',
            'release_pending'                 => 'Release Pending',
            default                           => Str::ucfirst($status),
        };
    }

    private function badgeVariant(string $status): string
    {
        return match ($status) {
            'active', 'fully_executed'        => 'green',
            'pending_sign'                    => 'gold',
            'countersign', 'countersign_pending' => 'blue',
            'draft'                           => 'gray',
            'expiring'                        => 'orange',
            'expired', 'terminated'           => 'red',
            'archived'                        => 'gray',
            default                           => 'gray',
        };
    }

    private function docTypeLabel(?string $type): string
    {
        return match ($type) {
            'MSA'                  => 'Master Service Agreement',
            'NDA'                  => 'Non-Disclosure Agreement',
            'SOW'                  => 'Statement of Work',
            'MOU'                  => 'Memorandum of Understanding',
            'SLA'                  => 'Service Level Agreement',
            'BAA'                  => 'Business Associate Agreement',
            'REF'                  => 'Referral Agreement',
            'ICA'                  => 'Independent Contractor Agreement',
            'steward_designation'  => 'Steward Designation',
            'client_notification'  => 'Client Notification',
            'plan_amendment'       => 'Plan Amendment',
            'cs_retainer_agreement'=> 'CS Retainer Agreement',
            'ss_authorization_agreement' => 'SS Authorization',
            'fee_amendment'        => 'Fee Amendment',
            'records_release'      => 'Records Release',
            'plan_agreement'       => 'Continuity Plan Agreement',
            default                => Str::headline($type ?? 'Document'),
        };
    }

    private function catLabel(?string $cat): string
    {
        return match ($cat) {
            'pe'  => 'Provider & CS',
            'pd'  => 'Provider & SS',
            'de'  => 'SS & CS',
            'tri' => 'Tri-Party',
            default => 'Continuity Plan',
        };
    }

    private function buildHistory(ContinuityDocument $doc): array
    {
        $h = [];
        if ($doc->created_at) {
            $h[] = ['date' => Carbon::parse($doc->created_at)->format('M j, Y'), 'title' => 'Document Created', 'desc' => 'Agreement drafted and saved.', 'dot' => 'gray'];
        }
        if ($doc->signed_at) {
            $h[] = ['date' => Carbon::parse($doc->signed_at)->format('M j, Y'), 'title' => 'Signed by Provider', 'desc' => ($doc->signedBy?->display_name ?? 'Provider') . ' applied signature.', 'dot' => 'gold'];
        }
        if ($doc->countersigned_at) {
            $h[] = ['date' => Carbon::parse($doc->countersigned_at)->format('M j, Y'), 'title' => 'Countersigned', 'desc' => ($doc->countersignedBy?->display_name ?? 'Steward') . ' countersigned. Agreement fully executed.', 'dot' => 'green'];
        }
        if ($doc->expires_at) {
            $h[] = ['date' => Carbon::parse($doc->expires_at)->format('M j, Y'), 'title' => 'Scheduled Expiry', 'desc' => 'Agreement expires unless renewed.', 'dot' => 'orange'];
        }
        if ($doc->archived_at) {
            $h[] = ['date' => Carbon::parse($doc->archived_at)->format('M j, Y'), 'title' => 'Archived', 'desc' => 'Agreement archived or terminated.', 'dot' => 'red'];
        }
        return $h;
    }

    private function initials(string $name): string
    {
        $parts = explode(' ', trim($name));
        return strtoupper(
            substr($parts[0] ?? 'P', 0, 1) . substr($parts[1] ?? '', 0, 1)
        );
    }
}
