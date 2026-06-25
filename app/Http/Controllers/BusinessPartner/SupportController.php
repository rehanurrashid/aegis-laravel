<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\CreateTicketRequest;
use App\Models\Complaint;
use App\Services\SupportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    public function __construct(private SupportService $support) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        return Inertia::render('business-partner/Support', [
            'tickets' => Complaint::where('user_id', $user?->id)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function help(): Response
    {
        return Inertia::render('business-partner/HelpCenter', [
            'articles' => \App\Models\HelpArticle::where('published', 1)
                ->where(fn($q) => $q->where('role_visibility', 'all')->orWhere('role_visibility', 'business_partner'))
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function createTicket(CreateTicketRequest $request): RedirectResponse
    {
        $this->support->createTicket($request->user(), $request->validated());
        return back()->with('success', 'Ticket submitted.');
    }
}
