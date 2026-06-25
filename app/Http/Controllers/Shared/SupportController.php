<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\CreateTicketRequest;
use App\Http\Requests\Support\SubmitFeedbackRequest;
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
        return Inertia::render('Shared/Support', [
            'tickets'        => $this->support->getForUser($request->user()->id),
            'helpArticles'   => $this->support->getHelpArticles(),
        ]);
    }

    public function storeTicket(CreateTicketRequest $request): RedirectResponse
    {
        $this->support->createTicket($request->user(), $request->validated());
        return back()->with('success', 'Ticket submitted.');
    }

    public function replyToTicket(Request $request, Complaint $ticket): RedirectResponse
    {
        abort_unless($ticket->submitter_id === $request->user()->id, 403);
        $body = $request->validate(['body' => 'required|string|min:1|max:5000'])['body'];
        $this->support->replyToTicket($ticket, $request->user(), $body, false);
        return back()->with('success', 'Reply sent.');
    }

    public function closeTicket(Request $request, Complaint $ticket): RedirectResponse
    {
        $this->support->closeSelfTicket($ticket, $request->user());
        return back()->with('success', 'Ticket closed.');
    }

    public function storeFeedback(SubmitFeedbackRequest $request): RedirectResponse
    {
        $this->support->submitFeedback($request->user(), $request->validated()['body'], $request->validated()['channel'] ?? 'in_app');
        return back()->with('success', 'Thanks for your feedback.');
    }
}
