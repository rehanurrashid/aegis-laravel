<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\AdminRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RolesController extends Controller
{
    public function __construct(private AdminRoleService $roles) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Roles', ['roles' => $this->roles->getAll()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|max:100',
        ]);
        $this->roles->create($request->user(), $data['name'], $data['description'] ?? '', $data['permissions'] ?? []);
        return back()->with('success', 'Role created.');
    }

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|max:100',
        ]);
        $this->roles->setPermissions($request->user(), $role, $data['permissions']);
        return back()->with('success', 'Permissions updated.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->roles->delete($request->user(), $role);
        return back()->with('success', 'Role deleted.');
    }
}
