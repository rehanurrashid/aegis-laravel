<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreProviderCredentialRequest;
use App\Http\Requests\Provider\UpdateProviderCredentialRequest;
use App\Models\ProviderCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * CRUD for the provider's tracked licenses, certifications, and insurance.
 * Backs the Credentials & Coverage card on the practitioner dashboard.
 *
 * Storage lives in the dedicated `provider_credentials` table (not the
 * users.credentials varchar column, which is a display-string only).
 */
class ProviderCredentialController extends Controller
{
    public function store(StoreProviderCredentialRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = $data['cred_type'] === 'custom' ? ($data['custom_type'] ?? 'Other') : $data['cred_type'];

        $path = null;
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('credentials/' . $request->user()->id, 'public');
        }

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
            'document_path' => $path,
            'sort_order'    => (ProviderCredential::where('user_id', $request->user()->id)->max('sort_order') ?? 0) + 1,
        ]);

        return back()->with('success', 'Credential added.');
    }

    public function update(UpdateProviderCredentialRequest $request, ProviderCredential $credential): RedirectResponse
    {
        abort_unless($credential->user_id === $request->user()->id, 403);

        $data = $request->validated();

        if ($request->hasFile('document')) {
            $data['document_path'] = $request->file('document')->store('credentials/' . $request->user()->id, 'public');
        }

        // Remove fields that map to file_path only
        unset($data['document']);

        $credential->update($data);

        return back()->with('success', 'Credential updated.');
    }

    public function destroy(Request $request, ProviderCredential $credential): RedirectResponse
    {
        abort_unless($credential->user_id === $request->user()->id, 403);
        $credential->delete();
        return back()->with('success', 'Credential removed.');
    }

    private function iconFor(string $type): string
    {
        $lower = strtolower($type);
        if (str_contains($lower, 'insurance') || str_contains($lower, 'liability')) return 'shield';
        if (str_contains($lower, 'business')) return 'briefcase';
        if (str_contains($lower, 'dea'))      return 'clipboard';
        return 'credit-card';
    }
}
