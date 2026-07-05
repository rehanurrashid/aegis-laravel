<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\News\CreateCommentRequest;
use App\Http\Requests\News\CreateNewsPostRequest;
use App\Http\Requests\News\PollVoteRequest;
use App\Http\Requests\News\ReactionRequest;
use App\Http\Requests\News\RsvpEventRequest;
use App\Http\Requests\News\SubmitEventRequest;
use App\Models\CeuEntry;
use App\Models\CeuRequirement;
use App\Models\NewsEvent;
use App\Models\NewsPost;
use App\Services\NewsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function __construct(private NewsService $news) {}

    public function index(Request $request): Response
    {
        $role = (string) ($request->user()?->role ?? 'practitioner');
        return Inertia::render('provider/News', [
            'feed' => $this->news->feed($role, 30),
        ]);
    }

    public function library(): Response
    {
        return Inertia::render('provider/NewsLibrary', [
            'library' => \App\Models\NewsLibraryItem::orderByDesc('created_at')->limit(100)->get(),
        ]);
    }

    public function events(Request $request): Response
    {
        $user      = $request->user();
        $userId    = $user->id;
        $now       = now();
        $yearStart = $now->copy()->startOfYear();

        // All published+approved events, upcoming first
        $allEvents = NewsEvent::published()
            ->approved()
            ->where(function ($q) use ($userId) {
                $q->where('role_visibility', 'all')
                  ->orWhere('role_visibility', 'practitioner');
            })
            ->orderBy('starts_at')
            ->get();

        // Registered event IDs for this user
        $registeredIds = $allEvents->filter(fn($e) => $e->isAttending($userId))
            ->pluck('id')
            ->values();

        // My upcoming registered events (for sidebar)
        $myEvents = $allEvents->filter(
            fn($e) => $e->isAttending($userId) && $e->starts_at && $e->starts_at >= $now
        )->values();

        // Stats
        $upcomingAll    = $allEvents->filter(fn($e) => $e->starts_at && $e->starts_at >= $now);
        $registeredCount = $registeredIds->count();

        // CEU data from ceu_entries
        $ceuEntries = CeuEntry::where('practitioner_id', $userId)
            ->whereYear('completed_on', $now->year)
            ->get();

        $ceuEarned = $ceuEntries->sum('credit_hours');

        // CEU rows from requirements
        $requirements = CeuRequirement::where('user_id', $userId)->get();

        $ceuRows = $requirements->map(function ($req) use ($ceuEntries) {
            $earned = $ceuEntries->where('title', 'like', '%' . explode('—', $req->jurisdiction)[0] . '%')
                                 ->sum('credit_hours');
            $earned       = (float) $earned;
            $required     = (float) $req->total_hours;
            $pct          = $required > 0 ? min(100, round($earned / $required * 100)) : 0;
            $status       = $pct >= 100 ? 'done' : ($pct >= 50 ? 'warn' : 'danger');
            $dueDate      = $req->due_date ? $req->due_date->format('M j') : null;

            return [
                'category'    => $req->jurisdiction,
                'icon'        => 'book',
                'earned_hrs'  => $earned,
                'required_hrs'=> $required,
                'pct'         => $pct,
                'status'      => $status,
                'meta_label'  => $dueDate ? "Due {$dueDate}" : null,
            ];
        })->values();

        // CEU transcript (completed entries)
        $ceuTranscript = CeuEntry::where('practitioner_id', $userId)
            ->orderByDesc('completed_on')
            ->get()
            ->map(fn($e) => [
                'id'          => $e->id,
                'course'      => $e->title,
                'type'        => $e->provider_name ?? 'Training',
                'badge'       => 'teal',
                'date'        => $e->completed_on?->format('M j, Y') ?? '—',
                'credits'     => number_format((float) $e->credit_hours, 1),
                'certificate' => $e->certificate_ref,
            ]);

        // Event days map for mini calendar: "Y-n-j" => true
        $eventDays = $allEvents->filter(fn($e) => $e->starts_at !== null)
            ->mapWithKeys(fn($e) => [
                $e->starts_at->format('Y-') .
                ((int)$e->starts_at->format('n') - 1) . '-' . $e->starts_at->format('j') => true
            ]);

        return Inertia::render('provider/Events', [
            'events'            => $allEvents->map(fn($e) => array_merge($e->toArray(), [
                'ceu_credits' => (float) $e->ceu_credits,
                'is_free'     => (bool) $e->is_free,
            ]))->values(),
            'countTotal'        => $upcomingAll->count(),
            'registeredCount'   => $registeredCount,
            'ceuEarned'         => round((float) $ceuEarned, 1),
            'ceuRows'           => $ceuRows,
            'ceuTranscript'     => $ceuTranscript->values(),
            'myEvents'          => $myEvents->map(fn($e) => array_merge($e->toArray(), [
                'ceu_credits' => (float) $e->ceu_credits,
            ]))->values(),
            'registeredEventIds'=> $registeredIds,
            'eventDays'         => $eventDays,
        ]);
    }

    public function storePost(CreateNewsPostRequest $request): RedirectResponse
    {
        $this->news->publishPost($request->user(), $request->validated());
        return back()->with('success', 'Post published.');
    }

    public function comment(CreateCommentRequest $request, NewsPost $post): RedirectResponse
    {
        $this->news->comment($request->user(), $post, $request->validated()['body']);
        return back()->with('success', 'Comment added.');
    }

    public function react(ReactionRequest $request, NewsPost $post): RedirectResponse
    {
        $this->news->react($request->user(), $post, $request->validated()['reaction_type']);
        return back();
    }

    public function votePoll(PollVoteRequest $request, NewsPost $post): RedirectResponse
    {
        $this->news->votePoll($request->user(), $post, $request->validated()['option_key']);
        return back()->with('success', 'Vote recorded.');
    }

    public function rsvp(RsvpEventRequest $request, NewsEvent $event): RedirectResponse
    {
        $this->news->rsvpEvent($request->user(), $event, $request->validated()['status'] ?? 'going');
        return back()->with('success', 'RSVP recorded.');
    }

    public function cancelRsvp(Request $request, NewsEvent $event): RedirectResponse
    {
        $this->news->cancelRsvp($request->user(), $event);
        return back()->with('success', 'Registration cancelled.');
    }

    public function submitEvent(SubmitEventRequest $request): RedirectResponse
    {
        $this->news->submitEvent($request->user(), $request->validated());
        return back()->with('success', 'Event submitted for review.');
    }

    public function exportTranscript(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user    = $request->user();
        $entries = CeuEntry::where('practitioner_id', $user->id)
            ->orderByDesc('completed_on')
            ->get();

        $filename = 'ceu-transcript-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($entries, $user) {
            $handle = fopen('php://output', 'w');
            // Header row
            fputcsv($handle, ['Practitioner', 'Course', 'Provider', 'Credits', 'Completed On', 'Expires On', 'Certificate Ref']);
            foreach ($entries as $e) {
                fputcsv($handle, [
                    $user->display_name ?? $user->name ?? $user->id,
                    $e->title,
                    $e->provider_name ?? '',
                    $e->credit_hours,
                    $e->completed_on?->format('Y-m-d') ?? '',
                    $e->expires_on?->format('Y-m-d') ?? '',
                    $e->certificate_ref ?? '',
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
