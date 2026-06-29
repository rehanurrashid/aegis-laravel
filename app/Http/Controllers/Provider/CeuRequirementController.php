<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreCeuRequirementRequest;
use App\Http\Requests\Provider\UpdateCeuRequirementRequest;
use App\Models\CeuRequirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CeuRequirementController extends Controller
{
    public function store(StoreCeuRequirementRequest $request): RedirectResponse
    {
        CeuRequirement::create(array_merge(
            $request->validated(),
            [
                'id'      => (string) Str::uuid(),
                'user_id' => $request->user()->id,
            ]
        ));
        return back()->with('success', 'CEU requirement saved.');
    }

    public function update(UpdateCeuRequirementRequest $request, CeuRequirement $requirement): RedirectResponse
    {
        abort_unless($requirement->user_id === $request->user()->id, 403);
        $requirement->update($request->validated());
        return back()->with('success', 'CEU requirement updated.');
    }

    public function destroy(Request $request, CeuRequirement $requirement): RedirectResponse
    {
        abort_unless($requirement->user_id === $request->user()->id, 403);
        $requirement->delete();
        return back()->with('success', 'CEU requirement removed.');
    }
}
