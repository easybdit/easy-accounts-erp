<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $user->syncRoles($validated['roles'] ?? []);

        return redirect()->route('security.users.index')->with('success', 'User roles updated.');
    }
}
