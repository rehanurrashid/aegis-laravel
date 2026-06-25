<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AdminRoleService
{
    public function getAll(): Collection
    {
        return Role::orderBy('name')->with('permissions')->get();
    }

    public function create(User $admin, string $name, string $description, array $permissions = []): Role
    {
        return DB::transaction(function () use ($admin, $name, $description, $permissions) {
            $role = Role::create([
                'id'          => 'r_' . Str::lower(Str::random(12)),
                'name'        => $name,
                'system_role' => 0,
                'description' => $description,
                'created_at'  => now(),
            ]);

            foreach ($permissions as $perm) {
                RolePermission::create([
                    'id'         => 'rp_' . Str::lower(Str::random(12)),
                    'role_id'    => $role->id,
                    'permission' => $perm,
                ]);
            }

            $this->audit($admin, 'create_role', $role->id, ['name' => $name, 'permissions' => $permissions]);
            return $role;
        });
    }

    public function setPermissions(User $admin, Role $role, array $permissions): Role
    {
        if ($role->system_role) {
            throw new RuntimeException('Cannot modify permissions on a system role.');
        }

        DB::transaction(function () use ($role, $permissions) {
            RolePermission::where('role_id', $role->id)->delete();
            foreach ($permissions as $perm) {
                RolePermission::create([
                    'id'         => 'rp_' . Str::lower(Str::random(12)),
                    'role_id'    => $role->id,
                    'permission' => $perm,
                ]);
            }
        });

        $this->audit($admin, 'set_role_permissions', $role->id, ['permissions' => $permissions]);
        return $role->fresh()->load('permissions');
    }

    public function delete(User $admin, Role $role): bool
    {
        if ($role->system_role) {
            throw new RuntimeException('Cannot delete a system role.');
        }
        RolePermission::where('role_id', $role->id)->delete();
        $this->audit($admin, 'delete_role', $role->id);
        return (bool) $role->delete();
    }

    private function audit(User $admin, string $action, string $roleId, array $meta = []): void
    {
        AdminAuditLog::create([
            'id'          => 'aal_' . Str::lower(Str::random(12)),
            'admin_id'    => $admin->id,
            'action'      => $action,
            'target_type' => 'role',
            'target_id'   => $roleId,
            'meta_json'   => json_encode($meta),
            'created_at'  => now(),
        ]);
    }
}
