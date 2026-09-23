<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Security/Users/Index', [
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
            ],
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        if ($user->id === $request->user()->id && ! in_array('Administrator', $validated['roles'] ?? [], true) && $user->hasRole('Administrator')) {
            return back()->with('error', 'You cannot remove your own Administrator role.');
        }

        $oldRoles = $user->roles->pluck('name')->sort()->values()->all();
        $newRoles = collect($validated['roles'] ?? [])->sort()->values()->all();

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

        return redirect()->route('security.users.index')->with('success', 'User roles updated.');
    }
}
