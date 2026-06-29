<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\CreateTicketRequest;
use App\Http\Requests\Support\SubmitFeedbackRequest;
use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\HelpArticle;
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

        $allComplaints = $this->support->getForUser($user->id);

        // Tickets = anything NOT categorized as feedback
        $tickets = $allComplaints
            ->where('category', '!=', 'feedback')
            ->values()
            ->map(fn ($t) => $this->formatTicket($t));

        // Feedback history = the user's previous feedback submissions
        $feedbackHistory = $allComplaints
            ->where('category', 'feedback')
            ->values()
            ->map(fn ($f) => [
                'id'         => $f->id,
                'subject'    => $f->subject,
                'body'       => $f->body,
                'category'   => $f->category,
                'created_at' => $f->created_at,
            ]);

        // Help articles grouped by category
        $helpArticles = $this->support->getHelpArticles();
        $helpByCategory = $helpArticles
            ->groupBy(fn ($a) => $a->category ?? 'General')
            ->map(fn ($items, $cat) => [
                'category' => $cat,
                'articles' => $items->map(fn ($a) => [
                    'id'    => $a->id,
                    'title' => $a->title,
                    'body'  => $a->body,
                ])->values(),
            ])
            ->values();

        // Replies, keyed by complaint id, only non-internal
        $ticketIds = $tickets->pluck('id')->all();
        $repliesGrouped = ComplaintReply::whereIn('complaint_id', $ticketIds)
            ->where('is_internal', 0)
            ->orderBy('created_at')
            ->get()
            ->groupBy('complaint_id')
            ->map(fn ($items) => $items->map(fn ($r) => [
                'id'         => $r->id,
                'author_id'  => $r->author_id,
                'body'       => $r->body,
                'created_at' => $r->created_at,
                'is_user'    => $r->author_id === $user->id,
            ])->values());

        return Inertia::render('Shared/Support', [
            'tickets'          => $tickets,
            'feedbackHistory'  => $feedbackHistory,
            'helpByCategory'   => $helpByCategory,
            'ticketReplies'    => (object) $repliesGrouped->toArray(),
            'openCount'        => $tickets->whereIn('status', ['open', 'in_progress'])->count(),
            'resolvedCount'    => $tickets->whereIn('status', ['resolved', 'closed'])->count(),
            'currentUserId'    => $user->id,
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
        $data = $request->validated();
        $this->support->submitFeedback(
            $request->user(),
            $data['body'],
            $data['channel'] ?? 'in_app'
        );
        return back()->with('success', 'Thanks for your feedback.');
    }

    private function formatTicket(Complaint $t): array
    {
        return [
            'id'              => $t->id,
            'subject'         => $t->subject,
            'body'            => $t->body,
            'category'        => $t->category,
            'priority'        => $t->priority,
            'status'          => $t->status,
            'created_at'      => $t->created_at,
            'resolved_at'     => $t->resolved_at,
            'module'          => $t->category,
        ];
    }
}
