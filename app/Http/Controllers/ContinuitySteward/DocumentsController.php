<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

use App\Http\Controllers\Controller;
use App\Models\ContinuityDocument;
use App\Models\PlanSteward;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentsController extends Controller
{
    public function index(Request $request): Response
    {
        $planIds = PlanSteward::where('steward_id', $request->user()->id)
            ->where('status', 'active')
            ->pluck('plan_id');

        return Inertia::render('ContinuitySteward/ImportantDocuments', [
            'documents' => ContinuityDocument::whereIn('plan_id', $planIds)
                ->orderByDesc('created_at')->get(),
        ]);
    }
}
