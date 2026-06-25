<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\MessageThread;
use App\Services\MessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessagesController extends Controller
{
    public function __construct(private MessagingService $messaging) {}

    public function index(Request $request): Response
    {
        $threads = $this->messaging->getThreads($request->user()->id);
        $activeId = $request->query('thread');
        $active = $activeId ? MessageThread::find($activeId) : $threads->first();
        $messages = $active ? $this->messaging->getMessages($active) : collect();

        return Inertia::render('Shared/Messages', [
            'threads'      => $threads,
            'activeThread' => $active,
            'messages'     => $messages,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
            'title' => 'nullable|string|max:200',
            'body'  => 'required|string|min:1|max:5000',
        ]);
        $participants = array_merge([$request->user()->id], $data['participant_ids']);
        $thread = $this->messaging->createThread($participants, $data['title'] ?? null);
        $this->messaging->sendMessage($thread, $request->user(), $data['body']);
        return back()->with('success', 'Message sent.');
    }

    public function reply(Request $request, MessageThread $thread): RedirectResponse
    {
        $this->authorize('send', $thread);
        $body = $request->validate(['body' => 'required|string|min:1|max:5000'])['body'];
        $this->messaging->sendMessage($thread, $request->user(), $body);
        return back()->with('success', 'Reply sent.');
    }

    public function markRead(Request $request, MessageThread $thread): RedirectResponse
    {
        $this->authorize('read', $thread);
        $this->messaging->markRead($thread, $request->user());
        return back();
    }
}
