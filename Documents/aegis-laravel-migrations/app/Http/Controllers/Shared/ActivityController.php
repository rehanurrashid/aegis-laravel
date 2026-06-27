<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\ActivityEvent;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function __construct(private ActivityService $activity) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['module', 'severity', 'unread', 'portal']);
        return Inertia::render('Shared/Activity', [
            'events'  => $this->activity->getForUser($request->user()->id, $filters, 100),
            'filters' => $filters,
            'unreadCount' => $this->activity->getUnreadCount($request->user()->id),
        ]);
    }

    public function markRead(Request $request, ActivityEvent $event): RedirectResponse
    {
        $this->activity->markRead($event->id, $request->user()->id);
        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->activity->markAllRead($request->user()->id);
        return back()->with('success', 'All notifications marked read.');
    }
}
