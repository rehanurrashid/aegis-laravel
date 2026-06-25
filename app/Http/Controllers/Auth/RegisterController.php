<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Events\Auth\UserRegistered;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\UserRoleAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    /** Default user-meta notify_* keys created at signup */
    private const DEFAULT_NOTIFY_KEYS = [
        'notify_email', 'notify_incident', 'notify_message', 'notify_task',
        'notify_assignment', 'notify_attestation', 'notify_plan_change',
        'notify_plan_review', 'notify_role_change', 'notify_payment',
        'notify_proposal', 'notify_agreement', 'notify_summary',
    ];

    /** GET /register */
    public function show(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /** POST /register */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'role'         => ['required', 'string', 'in:practitioner,continuity_steward,support_steward,business_partner'],
            'bp_type'      => ['required_if:role,business_partner', 'nullable', 'in:freelancer,agency'],
        ]);

        $user = DB::transaction(function () use ($request) {
            $userId = 'ae_' . Str::lower(Str::random(12));
            $slug   = $this->generateSlug($request->display_name);

            /** @var User $user */
            $user = new User();
            $user->forceFill([
                'id'             => $userId,
                'role'           => $request->role,
                'display_name'   => $request->display_name,
                'email'          => $request->email,
                'password'       => Hash::make($request->password),
                'slug'           => $slug,
                'tier'           => 'access',
                'verified'       => 0,
                'bp_type'        => $request->role === 'business_partner' ? $request->bp_type : null,
            ])->save();

            UserRoleAssignment::create([
                'id'         => 'ur_' . Str::lower(Str::random(12)),
                'user_id'    => $user->id,
                'role'       => $request->role,
                'is_default' => 1,
                'enabled_at' => now(),
            ]);

            $now = now();
            $metaRows = [];
            foreach (self::DEFAULT_NOTIFY_KEYS as $key) {
                $metaRows[] = [
                    'id'         => 'um_' . Str::lower(Str::random(12)),
                    'user_id'    => $user->id,
                    'meta_key'   => $key,
                    'meta_value' => '1',
                    'meta_type'  => 'boolean',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            UserMeta::insert($metaRows);

            return $user;
        });

        event(new UserRegistered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($this->portalDashboardRoute($user));
    }

    private function generateSlug(string $name): string
    {
        $base = Str::slug(preg_replace('/^(dr|mr|mrs|ms|prof)\.?\s+/i', '', $name)) ?: 'user';
        $slug = $base;
        $i    = 1;
        while (User::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    private function portalDashboardRoute(User $user): string
    {
        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFrom((string) $user->role);

        return match ($role) {
            UserRole::Practitioner      => 'provider.dashboard',
            UserRole::ContinuitySteward => 'cs.dashboard',
            UserRole::SupportSteward    => 'ss.dashboard',
            UserRole::BusinessPartner   => 'bp.dashboard',
            UserRole::Admin             => 'admin.dashboard',
            default                     => 'login',
        };
    }
}
