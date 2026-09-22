<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role/Permission-change audit trail (Section 90 Phase 11): the Role model
 * comes from spatie/laravel-permission and doesn't use our LogsActivity
 * trait, and permission assignment is a pivot-table sync, not a plain
 * model attribute — neither is captured by automatic activity logging.
 * These controller actions log the before/after permission and name sets
 * explicitly via activity()->withChanges(), in the same
 * {old, attributes} shape LogsActivity produces, so the Audit Log's diff
 * view renders them identically to any other tracked change.
 */
class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Security/Roles/Index', [
            'roles' => Role::query()->withCount(['users', 'permissions'])->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Security/Roles/Create', [
            'permissions' => Permission::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->validated('name'), 'guard_name' => 'web']);
        $permissions = collect($request->validated('permissions', []))->sort()->values()->all();
        $role->syncPermissions($permissions);

        activity()
            ->causedBy($request->user())
            ->performedOn($role)
            ->event('created')
            ->withChanges(['attributes' => ['name' => $role->name, 'permissions' => $permissions]])
            ->log("Role \"{$role->name}\" created");

        return redirect()->route('security.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('Security/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
            'permissions' => Permission::orderBy('name')->pluck('name'),
        ]);
    }

    public function update(StoreRoleRequest $request, Role $role): RedirectResponse
    {
        $oldName = $role->name;
        $oldPermissions = $role->permissions->pluck('name')->sort()->values()->all();

        $role->update(['name' => $request->validated('name')]);
        $newPermissions = collect($request->validated('permissions', []))->sort()->values()->all();
        $role->syncPermissions($newPermissions);

        activity()
            ->causedBy($request->user())
            ->performedOn($role)
            ->event('updated')
            ->withChanges([
                'old' => ['name' => $oldName, 'permissions' => $oldPermissions],
                'attributes' => ['name' => $role->name, 'permissions' => $newPermissions],
            ])
            ->log("Role \"{$role->name}\" updated");

        return redirect()->route('security.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        if ($role->name === 'Administrator') {
            return back()->with('error', 'The Administrator role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'This role is assigned to users and cannot be deleted.');
        }

        $name = $role->name;
        $permissions = $role->permissions->pluck('name')->sort()->values()->all();

        activity()
            ->causedBy($request->user())
            ->performedOn($role)
            ->event('deleted')
            ->withChanges(['old' => ['name' => $name, 'permissions' => $permissions]])
            ->log("Role \"{$name}\" deleted");

        $role->delete();

        return redirect()->route('security.roles.index')->with('success', 'Role deleted.');
    }
}
