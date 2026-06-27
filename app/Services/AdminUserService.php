<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Account\AccountClosed;

use App\Enums\ActivitySeverity;
use App\Events\Admin\UserLocked;
use App\Events\Admin\UserRoleChanged;
use App\Jobs\SendEmailJob;
use App\Models\ActivityEvent;
use App\Models\AdminAuditLog;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserService
{
    public function __construct(private ActivityService $activity) {}

    /**
     * @param array{q?:string, role?:string, tier?:string, status?:string} $filters
     */
    public function search(array $filters = [], int $limit = 50): Collection
    {
        $q = User::query();

        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->where(function ($w) use ($term) {
                $w->where('display_name', 'like', $term)
                  ->orWhere('email', 'like', $term);
            });
        }
        if (!empty($filters['role']))   $q->where('role', $filters['role']);
        if (!empty($filters['tier']))   $q->where('tier', $filters['tier']);

        if (($filters['status'] ?? null) === 'locked')      $q->whereNotNull('locked_at');
        elseif (($filters['status'] ?? null) === 'deactivated') $q->whereNotNull('deactivated_at');
        elseif (($filters['status'] ?? null) === 'active') $q->whereNull('locked_at')->whereNull('deactivated_at');

        return $q->orderByDesc('created_at')->limit($limit)->get();
    }

    public function getDetail(string $userId): ?array
    {
        $user = User::find($userId);
        if (!$user) return null;

        $roles = UserRoleAssignment::where('user_id', $userId)->get();
        $audit = AdminAuditLog::where('target_user_id', $userId)
            ->orderByDesc('created_at')->limit(50)->get();

        return ['user' => $user, 'roles' => $roles, 'audit' => $audit];
    }

    public function lock(User $admin, User $target, string $reason): User
    {
        $target->update([
            'locked_at'     => now(),
            'locked_reason' => $reason,
        ]);
        $target->tokens()->delete();

        $this->audit($admin, 'lock_user', $target, ['reason' => $reason]);
        event(new UserLocked($target, $reason));

        return $target->fresh();
    }

    public function unlock(User $admin, User $target): User
    {
        $target->update([
            'locked_at'          => null,
            'locked_reason'      => null,
            'failed_login_count' => 0,
        ]);

        $this->audit($admin, 'unlock_user', $target);
        return $target->fresh();
    }

    public function forcePasswordReset(User $admin, User $target): string
    {
        PasswordResetToken::where('user_id', $target->id)->delete();

        $token = Str::random(64);
        PasswordResetToken::create([
            'token'      => hash('sha256', $token),
            'user_id'    => $target->id,
            'created_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        SendEmailJob::dispatch(
            'emails.admin.force-password-reset',
            ['token' => $token, 'email' => $target->email],
            $target->id
        );

        $this->audit($admin, 'force_password_reset', $target);
        return $token;
    }

    public function changeRole(User $admin, User $target, string $newRole): User
    {
        $before = $target->role;
        $target->update(['role' => $newRole]);

        // Sync default UserRoleAssignment row
        UserRoleAssignment::where('user_id', $target->id)->update(['is_default' => 0]);
        UserRoleAssignment::updateOrCreate(
            ['user_id' => $target->id, 'role' => $newRole],
            ['is_default' => 1, 'enabled_at' => now()]
        );

        $this->audit($admin, 'change_role', $target, ['before' => $before, 'after' => $newRole]);
        event(new UserRoleChanged($target, $before, $newRole));

        return $target->fresh();
    }

    public function deactivate(User $admin, User $target, ?string $reason = null): User
    {
        $target->update(['deactivated_at' => now()]);
        $target->tokens()->delete();
        $this->audit($admin, 'deactivate_user', $target, ['reason' => $reason]);
        event(new AccountClosed($target->fresh(), $reason));
        return $target->fresh();
    }

    public function restore(User $admin, User $target): User
    {
        $target->update(['deactivated_at' => null]);
        $this->audit($admin, 'restore_user', $target);
        return $target->fresh();
    }

    public function impersonate(User $admin, User $target): string
    {
        $this->audit($admin, 'impersonate', $target);
        // Returns a Sanctum token for the target — caller uses this for the impersonation session
        return $target->createToken('impersonate-' . $admin->id, ['*'], now()->addHour())->plainTextToken;
    }

    public function getActivity(string $userId, int $limit = 50): Collection
    {
        return ActivityEvent::where('user_id', $userId)
            ->orderByDesc('created_at')->limit($limit)->get();
    }

    private function audit(User $admin, string $action, User $target, array $meta = []): void
    {
        AdminAuditLog::create([
            'id'             => 'aal_' . Str::lower(Str::random(12)),
            'admin_id'       => $admin->id,
            'action'         => $action,
            'target_user_id' => $target->id,
            'target_type'    => 'user',
            'target_id'      => $target->id,
            'meta_json'      => json_encode($meta),
            'created_at'     => now(),
        ]);
    }
}
