<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreDocumentRequest;
use App\Http\Requests\Provider\UploadSupportingDocRequest;
use App\Models\ContinuityDocument;
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

        $allDocs = $plan
            ? $this->documents->getForPlan($plan->id)->load(['signedBy', 'countersignedBy', 'holderSteward'])
            : collect();

        $now = Carbon::now();

        // Separate supporting docs from continuity plan documents
        $continuityDocs   = $allDocs->where('is_supporting', false)->values();
        $supportingDocs   = $allDocs->where('is_supporting', true)->values();

        // Shape documents for Vue
        $shapedDocs = $continuityDocs->map(fn ($doc) => $this->shapeDoc($doc, $user, $now));

        $shapedSupporting = $supportingDocs->map(fn ($doc) => [
            'id'           => $doc->id,
            'title'        => $doc->title,
            'meta'         => implode(' · ', array_filter([
                $doc->doc_type ? Str::upper($doc->doc_type) : null,
                $doc->created_at?->format('M j, Y'),
                $doc->holderSteward?->display_name,
            ])),
            'badge_label'  => $this->badgeLabel($doc->status instanceof \App\Enums\DocumentStatus ? $doc->status->value : (string) $doc->status),
            'badge_variant'=> $this->badgeVariant($doc->status instanceof \App\Enums\DocumentStatus ? $doc->status->value : (string) $doc->status),
        ])->values();

        // Stats
        $expiringCount = $continuityDocs->filter(
            fn ($d) => $d->expires_at && Carbon::parse($d->expires_at)->isBetween($now, $now->copy()->addDays(30))
        )->count();

        $statusVal = fn ($d) => $d->status instanceof \App\Enums\DocumentStatus ? $d->status->value : (string) $d->status;
        $docStats = [
            'total'    => $continuityDocs->count(),
            'active'   => $continuityDocs->filter(fn ($d) => in_array($statusVal($d), ['active', 'fully_executed']))->count(),
            'pending'  => $continuityDocs->filter(fn ($d) => in_array($statusVal($d), ['pending_sign', 'countersign_pending', 'countersign']))->count(),
            'expiring' => $expiringCount,
        ];

        // Active CS for wizard party B
        $stewards = $plan
            ? PlanSteward::where('plan_id', $plan->id)
                ->where('status', 'active')
                ->get()
                ->map(fn ($ps) => [
                    'id'       => $ps->steward_id,
                    'initials' => $this->initials(User::find($ps->steward_id)?->display_name ?? '?'),
                    'name'     => User::find($ps->steward_id)?->display_name ?? 'Unknown',
                    'meta'     => Str::ucfirst(str_replace('_', ' ', $ps->steward_category ?? 'steward')) . ' · Active',
                ])->values()->toArray()
            : [];

        return Inertia::render('Provider/ImportantDocuments', [
            'documents'     => $shapedDocs,
            'supportingDocs'=> $shapedSupporting,
            'docStats'      => $docStats,
            'stewards'      => $stewards,
        ]);
    }

    // ── Write actions ──────────────────────────────────────────────────────────

    /**
     * Create a new agreement from wizard, or save draft, or submit amendment.
     */
    public function request(StoreDocumentRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);

        $data = $request->validated();
        $isDraft = (bool) ($data['is_draft'] ?? false);
        $isAmendment = isset($data['parent_id']);

        // Build title from wizard fields when not explicitly given
        $title = $data['title'] ?? implode(' — ', array_filter([
            $data['doc_type'] ?? null,
            $data['category'] ?? null,
            $data['reference'] ?? null,
        ])) ?: 'New Agreement';

        $this->documents->requestDocument($plan, $request->user(), [
            'title'        => $title,
            'doc_type'     => $data['doc_type'] ?? 'agreement',
            'body'         => $data['proposed'] ?? $data['notes'] ?? null,
            'status'       => $isDraft ? 'draft' : 'pending_sign',
            'category'     => $data['category'] ?? null,
            'party_b_id'   => $data['party_b_id'] ?? null,
            'effective_date'=> $data['effective_date'] ?? null,
            'auto_renew'   => str_contains(strtolower($data['auto_renew'] ?? ''), 'yes') ? 1 : 0,
            'notes'        => $data['notes'] ?? null,
            'amends_document_id' => $data['parent_id'] ?? null,
        ]);

        $msg = $isDraft ? 'Draft saved.' : ($isAmendment ? 'Amendment request sent.' : 'Agreement sent for signature.');
        return back()->with('success', $msg);
    }

    /**
     * Provider applies their signature. No form name field — uses display_name.
     */
    public function sign(Request $request, ContinuityDocument $document): RedirectResponse
    {
        $this->authorize('sign', $document);
        $this->documents->sign($document, $request->user(), [
            'name' => $request->user()->display_name,
            'ip'   => $request->ip(),
        ]);
        return back()->with('success', 'Agreement signed. Awaiting countersignature.');
    }

    /**
     * Send a reminder / renewal notification for a document.
     */
    public function remind(Request $request, ContinuityDocument $document): RedirectResponse
    {
        abort_unless($document->practitioner_id === $request->user()->id, 403);
        $this->documents->remind($document, $request->user());
        return back()->with('success', 'Reminder sent.');
    }

    /**
     * Archive or terminate a document.
     */
    public function archive(Request $request, ContinuityDocument $document): RedirectResponse
    {
        $this->authorize('archive', $document);
        $this->documents->archive($document, $request->user(), $request->input('reason', 'archived'));
        return back()->with('success', 'Document archived.');
    }

    /**
     * Upload a supporting document.
     */
    public function upload(UploadSupportingDocRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);

        $data = $request->validated();
        $fileRef = null;

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store("docs/plan_{$plan->id}/supporting", 'local');
            $fileRef = $path;
        }

        ContinuityDocument::create([
            'id'             => 'cd_' . Str::lower(Str::random(12)),
            'plan_id'        => $plan->id,
            'practitioner_id'=> $request->user()->id,
            'title'          => $data['name'],
            'doc_type'       => strtolower(str_replace(' ', '_', $data['type'] ?? 'supporting')),
            'status'         => 'active',
            'is_supporting'  => true,
            'related_to'     => $data['related_to'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'file_ref'       => $fileRef,
            'created_at'     => now(),
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function shapeDoc(ContinuityDocument $doc, User $user, Carbon $now): array
    {
        $status       = $doc->status instanceof \App\Enums\DocumentStatus
            ? $doc->status->value
            : (string) $doc->status;
        $expiresAt    = $doc->expires_at ? Carbon::parse($doc->expires_at) : null;
        $isExpiring   = $expiresAt && $expiresAt->isBetween($now, $now->copy()->addDays(30));
        $isExpired    = $expiresAt && $expiresAt->isPast();

        // Tab key
        $catToTab = [
            'pe'  => 'pe',
            'pd'  => 'pd',
            'de'  => 'de',
            'tri' => 'tri',
        ];
        $tabKey = $catToTab[$doc->category ?? ''] ?? 'pe';

        // People (counterparty if any)
        $people = [['initials' => $this->initials($user->display_name), 'color' => 'gold']];
        $counterparty = null;
        if ($doc->holderSteward) {
            $cs = $doc->holderSteward;
            $people[] = ['initials' => $this->initials($cs->display_name), 'color' => 'dark'];
            // Load countersignature data
            $counterparty = [
                'name'      => $cs->display_name,
                'initials'  => $this->initials($cs->display_name),
                'meta'      => 'Continuity Steward · Active',
                'signed_at' => $doc->countersigned_at
                    ? Carbon::parse($doc->countersigned_at)->format('M j, Y')
                    : null,
            ];
        }

        $peopleParts  = array_map(fn ($p) => $p['initials'], $people);
        $peopleLabel  = $doc->holderSteward?->display_name
            ? ($user->display_name . ' & ' . $doc->holderSteward->display_name)
            : $user->display_name;

        // When text
        [$whenText, $whenClass, $whenIcon] = $this->whenMeta($status, $expiresAt, $isExpiring, $isExpired, $doc);

        // Primary action
        $primaryAction = $this->primaryAction($status, $doc->practitioner_id, $user->id);

        return [
            'id'             => $doc->id,
            'title'          => $doc->title,
            'reference'      => $doc->reference ?? 'DOC-' . strtoupper(substr($doc->id, -6)),
            'doc_type'       => $doc->doc_type,
            'doc_type_label' => Str::upper($doc->doc_type ?? 'DOC') . ' · ' . $this->docTypeLabel($doc->doc_type),
            'category_label' => $this->catLabel($doc->category),
            'tab_key'        => $tabKey,
            'status'         => $status,
            'is_expiring'    => $isExpiring || $isExpired || $status === 'expiring',
            'badge_label'    => $this->badgeLabel($status),
            'badge_variant'  => $this->badgeVariant($status),
            'people'         => $people,
            'people_label'   => $peopleLabel,
            'when_text'      => $whenText,
            'when_class'     => $whenClass,
            'when_icon'      => $whenIcon,
            'primary_action' => $primaryAction,
            'counterparty'   => $counterparty,
            'body'           => $doc->body,
            'history'        => $this->buildHistory($doc),
        ];
    }

    private function primaryAction(string $status, string $practitionerId, string $userId): string
    {
        if ($practitionerId !== $userId) return 'default';
        return match ($status) {
            'pending_sign'                    => 'sign',
            'draft'                           => 'edit',
            'expiring', 'expired', 'countersign',
            'countersign_pending'             => 'renew',
            default                           => 'default',
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
        if ($status === 'terminated' || $status === 'archived') {
            return [$doc->archived_at ? Carbon::parse($doc->archived_at)->format('M j, Y') : 'Archived', 'is-muted', null];
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
            'active', 'fully_executed' => 'Active',
            'pending_sign'             => 'Awaiting Signature',
            'countersign',
            'countersign_pending'      => 'Awaiting Countersig.',
            'draft'                    => 'Draft',
            'expiring'                 => 'Expiring Soon',
            'expired'                  => 'Expired',
            'archived'                 => 'Archived',
            'terminated'               => 'Terminated',
            'release_pending'          => 'Release Pending',
            default                    => Str::ucfirst($status),
        };
    }

    private function badgeVariant(string $status): string
    {
        return match ($status) {
            'active', 'fully_executed' => 'green',
            'pending_sign'             => 'gold',
            'countersign',
            'countersign_pending'      => 'blue',
            'draft'                    => 'gray',
            'expiring'                 => 'orange',
            'expired', 'terminated'    => 'red',
            'archived'                 => 'gray',
            default                    => 'gray',
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
            'records_release'      => 'Records Release',
            'review_record'        => 'Review Record',
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
