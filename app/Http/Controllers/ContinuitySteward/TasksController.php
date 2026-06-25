<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

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
        $user = $request->user();
        $tasks = IncidentTask::where('assigned_to_user_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress', 'flagged'])
            ->orderBy('status')
            ->get();

        return Inertia::render('ContinuitySteward/MyTasks', [
            'tasksByProvider' => $tasks->groupBy('incident_id')->values(),
            'allTasks'        => $tasks,
        ]);
    }

    public function complete(Request $request, IncidentTask $task): RedirectResponse
    {
        abort_unless($task->assigned_to_user_id === $request->user()->id, 403);
        $task->update(['status' => 'completed', 'completed_at' => now()]);
        return back()->with('success', 'Task completed.');
    }

    public function flag(Request $request, IncidentTask $task): RedirectResponse
    {
        abort_unless($task->assigned_to_user_id === $request->user()->id, 403);
        $reason = $request->validate(['reason' => 'required|string|max:500'])['reason'];
        $task->update(['status' => 'flagged', 'flag_reason' => $reason, 'flagged_at' => now()]);
        return back()->with('success', 'Task flagged.');
    }

    public function addNote(Request $request, IncidentTask $task): RedirectResponse
    {
        abort_unless($task->assigned_to_user_id === $request->user()->id, 403);
        $note = $request->validate(['note' => 'required|string|max:2000'])['note'];
        $task->update(['notes' => $note]);
        return back()->with('success', 'Note saved.');
    }
}
