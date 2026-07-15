<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreProviderCredentialRequest;
use App\Http\Requests\Provider\UpdateProviderCredentialRequest;
use App\Models\ProviderCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\ProfileCompletionService;
use Illuminate\Support\Str;

/**
 * CRUD for the provider's tracked licenses, certifications, and insurance.
 *
 * document_path stores a JSON array of file paths, e.g.:
 *   ["credentials/1/abc.pdf", "credentials/1/def.jpg"]
 * Single-file legacy values (plain strings) are handled transparently.
 */
class ProviderCredentialController extends Controller
{
    public function __construct(private ProfileCompletionService $completion) {}

    public function store(StoreProviderCredentialRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = $data['cred_type'] === 'custom' ? ($data['custom_type'] ?? 'Other') : $data['cred_type'];

        $paths = $this->storeDocuments($request, []);

        ProviderCredential::create([
            'id'            => (string) Str::uuid(),
            'user_id'       => $request->user()->id,
            'cred_type'     => $type,
            'icon'          => $this->iconFor($type),
            'name'          => $data['name'] ?? $type,
            'subtitle'      => $data['subtitle'] ?? null,
            'issuer'        => $data['issuer'] ?? null,
            'number'        => $data['number'] ?? null,
            'issued_on'     => $data['issued_on'] ?? null,
            'expires_on'    => $data['expires_on'] ?? null,
            'document_path' => $paths ? json_encode($paths) : null,
            'sort_order'    => (ProviderCredential::where('user_id', $request->user()->id)->max('sort_order') ?? 0) + 1,
        ]);

        $this->completion->recompute($request->user());
        return back()->with('success', 'Credential added.');
    }

    public function update(UpdateProviderCredentialRequest $request, ProviderCredential $credential): RedirectResponse
    {
        abort_unless($credential->user_id === $request->user()->id, 403);

        $data = $request->validated();
        unset($data['document']);

        if ($request->hasFile('document')) {
            // Append new uploads to existing paths
            $existing = $this->parsePaths($credential->document_path);
            $new      = $this->storeDocuments($request, $existing);
            $data['document_path'] = $new ? json_encode($new) : null;
        }

        $credential->update($data);

        return back()->with('success', 'Credential updated.');
    }

    public function destroyDocument(Request $request, ProviderCredential $credential): RedirectResponse
    {
        abort_unless($credential->user_id === $request->user()->id, 403);

        $pathToRemove = $request->input('path');
        $existing     = $this->parsePaths($credential->document_path);
        $updated      = array_filter($existing, fn ($p) => $p !== $pathToRemove);

        if ($pathToRemove) {
            Storage::disk('public')->delete($pathToRemove);
        }

        $credential->update(['document_path' => $updated ? json_encode(array_values($updated)) : null]);

        return back()->with('success', 'Document removed.');
    }

    public function destroy(Request $request, ProviderCredential $credential): RedirectResponse
    {
        abort_unless($credential->user_id === $request->user()->id, 403);

        foreach ($this->parsePaths($credential->document_path) as $path) {
            Storage::disk('public')->delete($path);
        }

        $credential->delete();
        $this->completion->recompute($request->user());
        return back()->with('success', 'Credential removed.');
    }

    public function download(Request $request, ProviderCredential $credential): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($credential->user_id === $request->user()->id, 403);

        // Support ?path= for multi-file; fall back to first path
        $target = $request->query('path') ?? ($this->parsePaths($credential->document_path)[0] ?? null);

        abort_unless($target, 404);
        abort_unless(Storage::disk('public')->exists($target), 404);

        $ext      = pathinfo($target, PATHINFO_EXTENSION);
        $label    = $credential->name ?? $credential->cred_type ?? 'document';
        $filename = Str::slug($label) . '.' . $ext;

        return Storage::disk('public')->download($target, $filename);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /** Store all uploaded document[] files, return merged paths array. */
    private function storeDocuments(Request $request, array $existing): array
    {
        $dir = 'credentials/' . $request->user()->id;

        if ($request->hasFile('document')) {
            $files = $request->file('document');
            // Accept both document (single File) and document[] (array)
            if (! is_array($files)) $files = [$files];

            foreach ($files as $file) {
                $existing[] = $file->store($dir, 'public');
            }
        }

        return $existing;
    }

    /** Normalize document_path — handles JSON array, plain string, or null. */
    private function parsePaths(?string $raw): array
    {
        if (! $raw) return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [$raw];
    }

    private function iconFor(string $type): string
    {
        $lower = strtolower($type);
        if (str_contains($lower, 'insurance') || str_contains($lower, 'liability')) return 'shield';
        if (str_contains($lower, 'business'))  return 'briefcase';
        if (str_contains($lower, 'dea'))       return 'clipboard';
        return 'credit-card';
    }
}
