<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreUserRequest;
use App\Http\Requests\Security\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Security/Users/Index', [
            // profile_photo_url comes along automatically — it's in User::$appends.
            'users' => User::query()->with('roles:id,name')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Security/Users/Create', [
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $roles = collect($request->validated('roles', []))->sort()->values()->all();
        $user->syncRoles($roles);

        // Mirrors update()'s explicit activity logging below — User::create()
        // is picked up by LogsActivity, but the roles pivot sync isn't, so
        // it's logged the same way a subsequent role change would be.
        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->event('created')
            ->withChanges(['attributes' => ['name' => $user->name, 'email' => $user->email, 'roles' => $roles]])
            ->log("User \"{$user->name}\" created");

        return redirect()->route('security.users.index')->with('success', 'User created.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Security/Users/Edit', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'profile_photo_url' => $user->profile_photo_url,
                'failed_login_attempts' => $user->failed_login_attempts,
                'locked_until' => $user->locked_until?->toIso8601String(),
                'is_locked' => (bool) $user->locked_until?->isFuture(),
            ],
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    /**
     * Manually clears a brute-force account lock — the lock also expires on
     * its own after Company Settings' lockout window, but a support case
     * ("I'm locked out right now") shouldn't have to wait for that.
     */
    public function unlock(User $user): RedirectResponse
    {
        $user->forceFill(['failed_login_attempts' => 0, 'locked_until' => null])->save();

        activity()
            ->causedBy(request()->user())
            ->performedOn($user)
            ->event('account_unlocked')
            ->log("Login lock cleared for user \"{$user->name}\" by an administrator");

        return back()->with('success', 'Account unlocked.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        if ($user->id === $request->user()->id && ! in_array('Administrator', $validated['roles'] ?? [], true) && $user->hasRole('Administrator')) {
            return back()->with('error', 'You cannot remove your own Administrator role.');
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        $passwordWasReset = ! empty($validated['password']);
        if ($passwordWasReset) {
            $user->password = Hash::make($validated['password']);
        }

        // name/email changes are picked up by User::getActivitylogOptions()
        // (logOnly ['name', 'email'], logOnlyDirty) via this save(); the
        // password itself is never logged.
        $user->save();

        $oldRoles = $user->roles->pluck('name')->sort()->values()->all();
        $newRoles = collect($validated['roles'] ?? [])->sort()->values()->all();

        if ($oldRoles !== $newRoles) {
            $user->syncRoles($newRoles);

            // Role model isn't LogsActivity-tracked and syncRoles() is a pivot
            // sync, not a plain attribute — logged explicitly, same as
            // RoleController's permission changes (Section 90 Phase 11).
            activity()
                ->causedBy($request->user())
                ->performedOn($user)
                ->event('roles_updated')
                ->withChanges(['old' => ['roles' => $oldRoles], 'attributes' => ['roles' => $newRoles]])
                ->log("Roles updated for user \"{$user->name}\"");
        }

        if ($passwordWasReset) {
            activity()
                ->causedBy($request->user())
                ->performedOn($user)
                ->event('password_reset')
                ->log("Password reset for user \"{$user->name}\" by an administrator");
        }

        return redirect()->route('security.users.index')->with('success', 'User updated.');
    }
}
