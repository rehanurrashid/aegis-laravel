<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

use App\Enums\ActivitySeverity;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\CsInvoice;
use App\Models\PlanSteward;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CS-side invoice management.
 *
 * CS issues invoices to practitioners they steward. When a practitioner pays
 * an invoice via /provider/finances/cs-invoices/{invoice}/pay, the destination
 * charge fires Provider Stripe → CS Stripe Connect account.
 */
class InvoicesController extends Controller
{
    public function __construct(private ActivityService $activity) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $invoices = CsInvoice::where('cs_id', $user->id)
            ->with(['practitioner:id,display_name,slug,email'])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->map(function (CsInvoice $inv) {
                $status = $inv->status instanceof InvoiceStatus ? $inv->status->value : (string) $inv->status;
                return [
                    'id'             => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'practitioner'   => $inv->practitioner?->display_name ?? '—',
                    'practitioner_id'=> $inv->practitioner_id,
                    'total_cents'    => (int) $inv->total_cents,
                    'currency'       => $inv->currency ?? 'USD',
                    'status'         => $status,
                    'issued_at'      => $inv->issued_at?->format('M j, Y'),
                    'due_at'         => $inv->due_at?->format('M j, Y'),
                    'paid_at'        => $inv->paid_at?->format('M j, Y'),
                ];
            });

        // Practitioners this CS actively stewards — used to populate the "bill to"
        // dropdown in the create-invoice modal.
        $practitionerIds = PlanSteward::where('steward_id', $user->id)
            ->where('status', 'active')
            ->join('continuity_plans', 'continuity_plans.id', '=', 'plan_stewards.plan_id')
            ->pluck('continuity_plans.practitioner_id')
            ->unique()
            ->values();

        $practitioners = User::whereIn('id', $practitionerIds)
            ->select(['id', 'display_name', 'email'])
            ->orderBy('display_name')
            ->get();

        return Inertia::render('continuity-steward/Invoices', [
            'invoices'      => $invoices,
            'practitioners' => $practitioners,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'practitioner_id' => ['required', 'string', 'exists:users,id'],
            'total_cents'     => ['required', 'integer', 'min:100', 'max:100000000'],
            'due_at'          => ['nullable', 'date', 'after:today'],
            'memo'            => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        // Guard: this CS must actively steward the target practitioner
        $isSteward = PlanSteward::where('steward_id', $user->id)
            ->where('status', 'active')
            ->join('continuity_plans', 'continuity_plans.id', '=', 'plan_stewards.plan_id')
            ->where('continuity_plans.practitioner_id', $data['practitioner_id'])
            ->exists();

        if (!$isSteward) {
            return back()->withErrors(['practitioner_id' => 'You do not steward this practitioner.']);
        }

        $invoice = CsInvoice::create([
            'id'              => (string) Str::uuid(),
            'cs_id'           => $user->id,
            'practitioner_id' => $data['practitioner_id'],
            'invoice_number'  => 'CS-' . strtoupper(Str::random(8)),
            'status'          => InvoiceStatus::Draft->value,
            'total_cents'     => (int) $data['total_cents'],
            'currency'        => 'USD',
            'due_at'          => !empty($data['due_at']) ? $data['due_at'] : null,
        ]);

        $this->activity->log(
            $user->id, 'continuity_steward', 'invoices',
            ActivitySeverity::Info,
            'cs_invoice_created', 'CS invoice drafted',
            'Draft invoice ' . $invoice->invoice_number . ' created for $' . number_format($invoice->total_cents / 100, 2) . '.',
            CsInvoice::class, $invoice->id, $data['practitioner_id'], 'log', $user->id,
        );

        return back()->with('success', 'Invoice ' . $invoice->invoice_number . ' created as draft.');
    }

    public function send(Request $request, CsInvoice $invoice): RedirectResponse
    {
        $user = $request->user();

        if ($invoice->cs_id !== $user->id) {
            abort(403, 'Not authorised to send this invoice.');
        }

        $status = $invoice->status instanceof InvoiceStatus ? $invoice->status->value : (string) $invoice->status;
        if ($status !== InvoiceStatus::Draft->value) {
            return back()->withErrors(['invoice' => 'Only draft invoices can be sent.']);
        }

        $invoice->update([
            'status'    => InvoiceStatus::Sent->value,
            'issued_at' => now(),
        ]);

        $this->activity->log(
            $user->id, 'continuity_steward', 'invoices',
            ActivitySeverity::Info,
            'cs_invoice_sent', 'CS invoice sent',
            'Invoice ' . $invoice->invoice_number . ' was sent to the practitioner.',
            CsInvoice::class, $invoice->id, $invoice->practitioner_id, 'log', $user->id,
        );

        // Notify practitioner
        $this->activity->log(
            $invoice->practitioner_id, 'provider', 'invoices',
            ActivitySeverity::Info,
            'cs_invoice_received', 'You received a CS invoice',
            'Invoice ' . $invoice->invoice_number . ' for $' . number_format($invoice->total_cents / 100, 2) . ' is due.',
            CsInvoice::class, $invoice->id, $user->id, 'notification', $user->id,
        );

        return back()->with('success', 'Invoice sent to practitioner.');
    }

    public function void(Request $request, CsInvoice $invoice): RedirectResponse
    {
        $user = $request->user();

        if ($invoice->cs_id !== $user->id) {
            abort(403, 'Not authorised to void this invoice.');
        }

        $status = $invoice->status instanceof InvoiceStatus ? $invoice->status->value : (string) $invoice->status;
        if ($status === InvoiceStatus::Paid->value) {
            return back()->withErrors(['invoice' => 'A paid invoice cannot be voided.']);
        }

        $invoice->update(['status' => InvoiceStatus::Void->value]);

        $this->activity->log(
            $user->id, 'continuity_steward', 'invoices',
            ActivitySeverity::Warning,
            'cs_invoice_voided', 'CS invoice voided',
            'Invoice ' . $invoice->invoice_number . ' was voided.',
            CsInvoice::class, $invoice->id, $invoice->practitioner_id, 'log', $user->id,
        );

        return back()->with('success', 'Invoice voided.');
    }
}
