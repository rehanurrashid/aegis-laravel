<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\UploadTaxDocRequest;
use App\Http\Requests\Business\W9SubmissionRequest;
use App\Models\BpTaxDocument;
use App\Services\TaxDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxDocumentsController extends Controller
{
    public function __construct(private TaxDocumentService $tax) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        return Inertia::render('business-partner/TaxDocuments', [
            'documents' => $this->tax->listForBp($user),
        ]);
    }

    public function upload(UploadTaxDocRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $path = $request->file('document')->store('tax-documents', 'local');
        $this->tax->upload($request->user(), $data['doc_type'], $path, $data['year'] ?? null);
        return back()->with('success', 'Document uploaded.');
    }

    public function submitW9(W9SubmissionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $path = $request->file('document')->store('tax-documents', 'local');
        $this->tax->upload($request->user(), 'w9', $path);
        return back()->with('success', 'W-9 submitted.');
    }

    public function destroy(Request $request, BpTaxDocument $document): RedirectResponse
    {
        abort_unless($document->bp_id === $request->user()?->id, 403);
        $this->tax->delete($document);
        return back()->with('success', 'Document removed.');
    }
}
