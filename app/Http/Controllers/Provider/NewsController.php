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
use App\Http\Requests\News\UpdateNewsPostRequest;
use App\Models\CeuEntry;
use App\Models\CeuRequirement;
use App\Models\NewsEvent;
use App\Models\NewsPost;
use App\Services\CeuService;
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
        $user       = $request->user();
        $filterType = $request->get('filter', 'all');
        $tag        = $request->get('tag');

        $data = $this->news->feedData($user, $filterType, $tag);

        return Inertia::render('provider/News', array_merge($data, [
            'activeFilter' => $filterType,
            'activeTag'    => $tag ?? '',
        ]));
    }

    public function library(): Response
    {
        return Inertia::render('provider/NewsLibrary', [
            'library' => \App\Models\NewsLibraryItem::orderByDesc('created_at')->limit(100)->get(),
        ]);
    }

    /** Returns saved + reported posts for the current user — consumed as JSON by the My Library modal. */
    public function myLibrary(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $data = $this->news->myLibraryData($user);
        return response()->json($data);
    }

    public function events(Request $request): Response
    {
        $user      = $request->user();
        $userId    = $user->id;
        $now       = now();

        $allEvents = NewsEvent::published()
            ->approved()
            ->where(function ($q) use ($userId) {
                $q->where('role_visibility', 'all')
                  ->orWhere('role_visibility', 'practitioner');
            })
            ->orderBy('starts_at')
            ->get();

        $registeredIds = $allEvents->filter(fn($e) => $e->isAttending($userId))
            ->pluck('id')
            ->values();

        $myEvents = $allEvents->filter(
            fn($e) => $e->isAttending($userId) && $e->starts_at && $e->starts_at >= $now
        )->values();

        $upcomingAll     = $allEvents->filter(fn($e) => $e->starts_at && $e->starts_at >= $now);
        $registeredCount = $registeredIds->count();

        $ceuEntries = CeuEntry::where('practitioner_id', $userId)
            ->whereYear('completed_on', $now->year)
            ->get();
        $ceuEarned = $ceuEntries->sum('credit_hours');

        $requirements = CeuRequirement::where('user_id', $userId)->get();
        $ceuRows = $requirements->map(function ($req) use ($ceuEntries) {
            $earned   = (float) $ceuEntries->sum('credit_hours');
            $required = (float) $req->total_hours;
            $pct      = $required > 0 ? min(100, round($earned / $required * 100)) : 0;
            return [
                'category'     => $req->jurisdiction,
                'icon'         => 'book',
                'earned_hrs'   => $earned,
                'required_hrs' => $required,
                'pct'          => $pct,
                'status'       => $pct >= 100 ? 'done' : ($pct >= 50 ? 'warn' : 'danger'),
                'meta_label'   => $req->due_date ? 'Due ' . $req->due_date->format('M j') : null,
            ];
        })->values();

        $ceuTranscript = CeuEntry::where('practitioner_id', $userId)
            ->orderByDesc('completed_on')
            ->get()
            ->map(fn($e) => [
                'id'              => $e->id,
                'course'          => $e->title,
                'type'            => $e->provider_name ?? 'Training',
                'badge'           => 'teal',
                'date'            => $e->completed_on?->format('M j, Y') ?? '—',
                'credits'         => number_format((float) $e->credit_hours, 1),
                'certificate'     => $e->certificate_ref,
                'certificate_url' => CeuService::certificateUrl($e->certificate_ref),
            ]);

        $eventDays = $allEvents->filter(fn($e) => $e->starts_at !== null)
            ->mapWithKeys(fn($e) => [
                $e->starts_at->format('Y-') .
                ((int)$e->starts_at->format('n') - 1) . '-' . $e->starts_at->format('j') => true
            ]);

        return Inertia::render('provider/Events', [
            'events'             => $allEvents->map(fn($e) => array_merge($e->toArray(), [
                'ceu_credits'    => (float) $e->ceu_credits,
                'is_free'        => (bool) $e->is_free,
                'external_url'   => $e->rsvp_url,
                'attendee_count' => count($e->rsvps_json ?? []),
                'is_external'    => !empty($e->rsvp_url),
            ]))->values(),
            'countTotal'         => $upcomingAll->count(),
            'registeredCount'    => $registeredCount,
            'ceuEarned'          => round((float) $ceuEarned, 1),
            'ceuRows'            => $ceuRows,
            'ceuTranscript'      => $ceuTranscript->values(),
            'myEvents'           => $myEvents->map(fn($e) => array_merge($e->toArray(), [
                'ceu_credits'  => (float) $e->ceu_credits,
                'external_url' => $e->rsvp_url,
                'is_external'  => !empty($e->rsvp_url),
            ]))->values(),
            'registeredEventIds' => $registeredIds,
            'eventDays'          => $eventDays,
        ]);
    }

    public function storePost(CreateNewsPostRequest $request): RedirectResponse
    {
        $this->news->publishPost($request->user(), $request->validated());
        return back()->with('success', 'Post published.');
    }

    public function updatePost(UpdateNewsPostRequest $request, NewsPost $post): RedirectResponse
    {
        $this->news->updatePost($post, $request->validated());
        return back()->with('success', 'Post updated.');
    }

    public function destroyPost(Request $request, NewsPost $post): RedirectResponse
    {
        abort_unless($post->author_id === $request->user()?->id, 403);
        $this->news->deletePost($post);
        return back()->with('success', 'Post deleted.');
    }

    public function comment(CreateCommentRequest $request, NewsPost $post): RedirectResponse
    {
        $this->news->comment($request->user(), $post, $request->validated()['body']);
        return back()->with('success', 'Comment added.');
    }

    public function deleteComment(Request $request, \App\Models\NewsComment $comment): RedirectResponse
    {
        $this->news->deleteComment($request->user(), $comment);
        return back()->with('success', 'Comment deleted.');
    }

    public function updateComment(Request $request, \App\Models\NewsComment $comment): RedirectResponse
    {
        $request->validate(['body' => ['required', 'string', 'min:1', 'max:1000']]);
        $this->news->updateComment($request->user(), $comment, $request->input('body'));
        return back()->with('success', 'Comment updated.');
    }

    public function react(ReactionRequest $request, NewsPost $post): RedirectResponse
    {
        // ReactionRequest validates 'reaction_type'; map to service which uses 'reaction' column
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

        return response()->streamDownload(function () use ($entries, $user) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Practitioner', 'Course', 'Provider', 'Credits', 'Completed On', 'Certificate']);
            foreach ($entries as $e) {
                fputcsv($handle, [
                    $user->display_name ?? $user->id,
                    $e->title,
                    $e->provider_name ?? '',
                    $e->credit_hours,
                    $e->completed_on?->format('Y-m-d') ?? '',
                    $e->certificate_ref ? CeuService::certificateUrl($e->certificate_ref) : '',
                ]);
            }
            fclose($handle);
        }, 'ceu-transcript-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
