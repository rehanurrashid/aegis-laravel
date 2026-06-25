<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ContinuityDocument;
use App\Services\DocumentService;
use App\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $plan = $this->plans->getForPractitioner($request->user()->id);
        $docs = $plan ? $this->documents->getForPlan($plan->id) : collect();

        return Inertia::render('Provider/ImportantDocuments', [
            'documents'         => $docs,
            'pendingSignatures' => $docs->where('status', 'pending_sign')->values(),
        ]);
    }

    public function request(Request $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);

        $data = $request->validate([
            'title'    => 'required|string|max:200',
            'doc_type' => 'required|string',
            'body'     => 'nullable|string|max:50000',
        ]);
        $this->documents->requestDocument($plan, $request->user(), $data);
        return back()->with('success', 'Document requested.');
    }

    public function sign(Request $request, ContinuityDocument $document): RedirectResponse
    {
        $this->authorize('sign', $document);
        $data = $request->validate(['name' => 'required|string|max:100']);
        $this->documents->sign($document, $request->user(), [
            'name' => $data['name'],
            'ip'   => $request->ip(),
        ]);
        return back()->with('success', 'Document signed.');
    }
}
