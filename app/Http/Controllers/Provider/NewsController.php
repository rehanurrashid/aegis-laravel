<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\News\CreateCommentRequest;
use App\Http\Requests\News\CreateNewsPostRequest;
use App\Http\Requests\News\PollVoteRequest;
use App\Http\Requests\News\ReactionRequest;
use App\Http\Requests\News\RsvpEventRequest;
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

    public function events(): Response
    {
        return Inertia::render('provider/Events', [
            'events' => NewsEvent::orderBy('starts_at')->limit(100)->get(),
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
}
