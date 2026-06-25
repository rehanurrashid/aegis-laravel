<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ComplaintsController extends Controller
{
    public function index(Request $request)
    {
        $query = Complaint::with("user");

        if ($request->filled("status")) {
            $query->where("status", $request->status);
        }
        if ($request->filled("priority")) {
            $query->where("priority", $request->priority);
        }
        if ($request->filled("category")) {
            $query->where("category", $request->category);
        }

        $complaints = $query->latest("created_at")->paginate(25)->through(fn($c) => [
            "id" => $c->id,
            "user_name" => $c->user?->display_name ?? "Unknown",
            "user_email" => $c->user?->email ?? "",
            "user_type" => $c->user?->user_type,
            "subject" => $c->subject,
            "description" => Str::limit($c->description, 120),
            "category" => $c->category,
            "priority" => $c->priority,
            "status" => $c->status,
            "created_at" => $c->created_at?->format("M d, Y"),
            "resolved_at" => $c->resolved_at?->format("M d, Y"),
        ]);

        $summary = [
            "total" => Complaint::count(),
            "open" => Complaint::open()->count(),
            "in_progress" => Complaint::inProgress()->count(),
            "critical" => Complaint::critical()->open()->count(),
        ];

        return Inertia::render("Admin/Complaints", [
            "user" => auth()->user(),
            "complaints" => $complaints,
            "summary" => $summary,
            "filters" => $request->only(["status", "priority", "category"]),
        ]);
    }

    public function show(string $id)
    {
        $complaint = Complaint::with(["user", "resolvedByUser"])->findOrFail($id);

        return Inertia::render("Admin/Complaints", [
            "user" => auth()->user(),
            "complaint" => [
                "id" => $complaint->id,
                "user_name" => $complaint->user?->display_name,
                "user_email" => $complaint->user?->email,
                "subject" => $complaint->subject,
                "description" => $complaint->description,
                "category" => $complaint->category,
                "priority" => $complaint->priority,
                "status" => $complaint->status,
                "admin_notes" => $complaint->admin_notes,
                "resolved_by" => $complaint->resolvedByUser?->display_name,
                "resolved_at" => $complaint->resolved_at?->format("M d, Y H:i"),
                "created_at" => $complaint->created_at?->format("M d, Y H:i"),
            ],
        ]);
    }

    public function resolve(Request $request, string $id)
    {
        $v = $request->validate([
            "status" => "required|in:open,in_progress,resolved,closed",
            "admin_notes" => "nullable|string",
            "priority" => "nullable|in:low,medium,high,critical",
        ]);

        $complaint = Complaint::findOrFail($id);
        $complaint->status = $v["status"];
        if (isset($v["admin_notes"])) {
            $complaint->admin_notes = $v["admin_notes"];
        }
        if (isset($v["priority"])) {
            $complaint->priority = $v["priority"];
        }
        if ($v["status"] === "resolved" || $v["status"] === "closed") {
            $complaint->resolved_by = auth()->id();
            $complaint->resolved_at = now();
        }
        $complaint->save();

        return redirect()->route("admin.complaints")->with("success", "Complaint updated.");
    }

// FROM ComplaintAdminController.php: see git history for full content; methods preserved below.

    public function __construct(private AdminComplaintService $complaints) {}

    public function assign(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $request->validate(['assignee_id' => 'required|exists:users,id']);
        $this->complaints->assign($request->user(), $complaint, $data['assignee_id']);
        return back()->with('success', 'Complaint assigned.');
    }
    public function reply(ReplyComplaintRequest $request, Complaint $complaint): RedirectResponse
    {
        $data = $request->validated();
        $this->complaints->reply($request->user(), $complaint, $data['body'], $data['is_internal']);
        return back()->with('success', 'Reply posted.');
    }
    public function updateStatus(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:open,in_progress,resolved,closed']);
        $this->complaints->setStatus($request->user(), $complaint, $data['status']);
        return back()->with('success', 'Status updated.');
    }
    public function escalate(Request $request, Complaint $complaint): RedirectResponse
    {
        $reason = $request->validate(['reason' => 'required|string|min:10|max:1000'])['reason'];
        $this->complaints->escalate($request->user(), $complaint, $reason);
        return back()->with('success', 'Complaint escalated.');
    }
}
