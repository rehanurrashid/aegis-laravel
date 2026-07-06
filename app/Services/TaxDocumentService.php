<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Models\BpTaxDocument;
use App\Services\ActivityService;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * BP tax document storage (W-9 upload, 1099 issuance, EIN docs).
 * Per UC-BP-tax-*. Files are stored on the private disk; download_url is
 * a signed temporary URL generated at view time.
 */
class TaxDocumentService
{
    public function __construct(private ActivityService $activity) {}

    /** BP uploads their W-9 or other tax form. */
    public function upload(User $bp, string $docType, string $storagePath, ?int $year = null): BpTaxDocument
    {
        if (!in_array($docType, ['w9', '1099', 'ein_doc', 'other'], true)) {
            throw new RuntimeException("Invalid doc_type: {$docType}");
        }

        $doc = BpTaxDocument::create([
            'id'           => 'td_' . Str::lower(Str::random(12)),
            'bp_id'        => $bp->id,
            'doc_type'     => $docType,
            'status'       => 'pending',
            'download_url' => $storagePath,
            'year'         => $year,
            'created_at'   => now(),
        ]);

        $this->activity->log(
            $bp->id, 'business_partner', 'account', ActivitySeverity::Info,
            'tax_document_uploaded', 'Tax document uploaded',
            "You uploaded a {$docType} document.",
            BpTaxDocument::class, $doc->id, null, 'log', $bp->id,
        );

        return $doc;
    }

    /** Admin issues a 1099 for a BP at year end. */
    public function issue1099(User $bp, int $year, string $downloadUrl): BpTaxDocument
    {
        $doc = BpTaxDocument::create([
            'id'           => 'td_' . Str::lower(Str::random(12)),
            'bp_id'        => $bp->id,
            'doc_type'     => '1099',
            'status'       => 'available',
            'download_url' => $downloadUrl,
            'year'         => $year,
            'created_at'   => now(),
        ]);

        $this->activity->log(
            $bp->id, 'business_partner', 'account', ActivitySeverity::Info,
            'tax_1099_issued', '1099 available for download',
            "Your {$year} 1099 form is now available.",
            BpTaxDocument::class, $doc->id, null, 'notification', null,
        );

        return $doc;
    }

    public function verify(BpTaxDocument $doc): BpTaxDocument
    {
        $doc->update(['status' => 'verified']);
        return $doc->fresh();
    }

    public function markAvailable(BpTaxDocument $doc, ?string $downloadUrl = null): BpTaxDocument
    {
        $update = ['status' => 'available'];
        if ($downloadUrl !== null) $update['download_url'] = $downloadUrl;
        $doc->update($update);
        return $doc->fresh();
    }

    public function listForBp(User $bp, ?string $docType = null, ?int $year = null): Collection
    {
        $q = BpTaxDocument::where('bp_id', $bp->id);
        if ($docType !== null) $q->where('doc_type', $docType);
        if ($year !== null) $q->where('year', $year);
        return $q->orderByDesc('year')->orderByDesc('created_at')->get();
    }

    public function find(string $id): ?BpTaxDocument
    {
        return BpTaxDocument::find($id);
    }

    public function delete(BpTaxDocument $doc): bool
    {
        return (bool) $doc->delete();
    }
}
