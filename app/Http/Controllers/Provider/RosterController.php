<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\LogCeuEntryRequest;
use App\Http\Requests\Roster\UpsertRosterEntryRequest;
use App\Models\CeuEntry;
use App\Services\CeuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Provider roster — credentials, CEU entries, professional history.
 * Per UC-PRV-145..152.
 */
class RosterController extends Controller
{
    public function __construct(private CeuService $ceu) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        return Inertia::render('provider/Roster', [
            'credentials' => $user?->meta()->where('key', 'like', 'credential_%')->get() ?? [],
            'ceu_entries' => CeuEntry::where('practitioner_id', $user?->id)
                ->orderByDesc('completed_on')
                ->get(),
            'ceu_summary' => [
                'this_year'  => CeuEntry::where('practitioner_id', $user?->id)
                                    ->whereYear('completed_on', now()->year)
                                    ->sum('credit_hours'),
                'last_year'  => CeuEntry::where('practitioner_id', $user?->id)
                                    ->whereYear('completed_on', now()->subYear()->year)
                                    ->sum('credit_hours'),
            ],
        ]);
    }

    public function logCeu(LogCeuEntryRequest $request): RedirectResponse
    {
        $this->ceu->create(
            $request->user(),
            $request->validated(),
            $request->file('certificate')
        );
        return back()->with('success', 'CEU entry logged.');
    }

    public function upsert(UpsertRosterEntryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (($data['type'] ?? '') === 'ceu') {
            $this->ceu->create($request->user(), $data);
        }
        return back()->with('success', 'Roster updated.');
    }

    public function deleteCeu(Request $request, CeuEntry $entry): RedirectResponse
    {
        abort_unless($entry->practitioner_id === $request->user()?->id, 403);
        $this->ceu->delete($entry);
        return back()->with('success', 'CEU entry removed.');
    }
}
