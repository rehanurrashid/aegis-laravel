<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\{Permission, Role};

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled("type")) {
            $query->where("user_type", (int) $request->type);
        }
        if ($request->filled("search")) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where("display_name", "like", "%$s%")
                  ->orWhere("email", "like", "%$s%");
            });
        }

        $users = $query->orderBy("display_name")->paginate(25)->through(fn($u) => [
            "id" => $u->id,
            "display_name" => $u->display_name,
            "email" => $u->email,
            "user_type" => $u->user_type,
            "type_label" => $u->getTypeLabel(),
            "role" => $u->role,
            "verified" => $u->verified,
            "tier" => $u->tier,
            "roles" => $u->getRoleNames()->toArray(),
            "permissions_count" => $u->getAllPermissions()->count(),
            "created_at" => $u->created_at?->format("M d, Y"),
        ]);

        $allRoles = Role::all()->map(fn($r) => ["name" => $r->name, "id" => $r->id]);

        return Inertia::render("Admin/Users", [
            "user" => auth()->user(),
            "users" => $users,
            "filters" => $request->only(["type", "search"]),
            "allRoles" => $allRoles,
        ]);
    }

    public function edit(string $id)
    {
        $editUser = User::with("roles.permissions")->findOrFail($id);
        $allRoles = Role::with("permissions")->get()->map(fn($r) => [
            "name" => $r->name,
            "id" => $r->id,
            "permissions" => $r->permissions->pluck("name"),
        ]);
        $allPermissions = Permission::all()->pluck("name");

        return Inertia::render("Admin/UserForm", [
            "user" => auth()->user(),
            "editUser" => [
                "id" => $editUser->id,
                "display_name" => $editUser->display_name,
                "email" => $editUser->email,
                "user_type" => $editUser->user_type,
                "type_label" => $editUser->getTypeLabel(),
                "role" => $editUser->role,
                "verified" => $editUser->verified,
                "tier" => $editUser->tier,
                "assigned_roles" => $editUser->getRoleNames()->toArray(),
                "assigned_permissions" => $editUser->getPermissionNames()->toArray(),
            ],
            "allRoles" => $allRoles,
            "allPermissions" => $allPermissions,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $editUser = User::findOrFail($id);
        $v = $request->validate([
            "display_name" => "required|string|max:255",
            "email" => "required|email|unique:aegis_users,email," . $id . ",id",
            "user_type" => "required|integer|in:0,1,2,3,4",
            "verified" => "boolean",
            "roles" => "nullable|array",
            "permissions" => "nullable|array",
        ]);

        $roleMap = [0 => "admin", 1 => "practitioner", 2 => "continuity_steward", 3 => "support_steward", 4 => "business_partner"];
        $editUser->update([
            "display_name" => $v["display_name"],
            "email" => $v["email"],
            "user_type" => (int) $v["user_type"],
            "role" => $roleMap[(int) $v["user_type"]] ?? $editUser->role,
            "verified" => $v["verified"] ?? false,
        ]);

        $editUser->syncRoles($v["roles"] ?? []);
        $editUser->syncPermissions($v["permissions"] ?? []);

        return redirect()->route("admin.users")->with("success", "User updated.");
    }

// FROM UserAdminController.php: see git history for full content; methods preserved below.

    public function __construct(private AdminUserService $users) {}

    public function show(User $user): Response
    {
        $detail = $this->users->getDetail($user->id);
        return Inertia::render('Admin/Users', ['selectedUser' => $detail]);
    }
    public function lock(LockUserRequest $request, User $user): RedirectResponse
    {
        $this->users->lock($request->user(), $user, $request->validated()['reason']);
        return back()->with('success', 'User locked.');
    }
    public function unlock(Request $request, User $user): RedirectResponse
    {
        $this->users->unlock($request->user(), $user);
        return back()->with('success', 'User unlocked.');
    }
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->users->forcePasswordReset($request->user(), $user);
        return back()->with('success', 'Password reset email sent.');
    }
    public function updateRole(ChangeRoleRequest $request, User $user): RedirectResponse
    {
        $this->users->changeRole($request->user(), $user, $request->validated()['role']);
        return back()->with('success', 'Role updated.');
    }
    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $reason = $request->validate(['reason' => 'nullable|string|max:500'])['reason'] ?? null;
        $this->users->deactivate($request->user(), $user, $reason);
        return back()->with('success', 'User deactivated.');
    }
    public function restore(Request $request, User $user): RedirectResponse
    {
        $this->users->restore($request->user(), $user);
        return back()->with('success', 'User restored.');
    }
}
