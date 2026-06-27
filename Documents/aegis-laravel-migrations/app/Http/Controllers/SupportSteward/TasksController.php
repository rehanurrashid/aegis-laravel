<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportSteward;

use App\Http\Controllers\Controller;
use App\Models\IncidentTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TasksController extends Controller
{
    public function index(Request $request): Response
    {
        $tasks = IncidentTask::where('assigned_to_user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'in_progress', 'flagged'])->get();
        return Inertia::render('SupportSteward/MyTasks', ['tasks' => $tasks]);
    }

    public function complete(Request $request, IncidentTask $task): RedirectResponse
    {
        abort_unless($task->assigned_to_user_id === $request->user()->id, 403);
        $task->update(['status' => 'completed', 'completed_at' => now()]);
        return back()->with('success', 'Task completed.');
    }
}
