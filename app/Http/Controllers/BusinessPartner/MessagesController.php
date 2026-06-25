<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Messages\CreateThreadRequest;
use App\Http\Requests\Messages\SendMessageRequest;
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
        $user = $request->user();
        return Inertia::render('business-partner/Messages', [
            'threads' => MessageThread::query()
                ->whereJsonContains('participant_ids', $user?->id)
                ->orderByDesc('updated_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function show(MessageThread $thread): Response
    {
        return Inertia::render('business-partner/Messages', [
            'thread'   => $thread,
            'messages' => $thread->messages()->orderBy('created_at')->limit(200)->get(),
        ]);
    }

    public function createThread(CreateThreadRequest $request): RedirectResponse
    {
        $thread = $this->messaging->createThread($request->user(), $request->validated());
        return redirect()->route('bp.messages.show', $thread->id);
    }

    public function send(SendMessageRequest $request, MessageThread $thread): RedirectResponse
    {
        $this->messaging->send($request->user(), $thread, $request->validated()['body']);
        return back();
    }
}
