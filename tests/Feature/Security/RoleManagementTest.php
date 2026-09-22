<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_role_can_be_created_with_permissions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('security.roles.store'), [
            'name' => 'Auditor',
            'permissions' => ['reports.view', 'audit.view'],
        ])->assertRedirect(route('security.roles.index'));

        $this->assertDatabaseHas('roles', ['name' => 'Auditor']);
        $this->assertTrue(Role::findByName('Auditor')->hasPermissionTo('reports.view'));
        $this->assertFalse(Role::findByName('Auditor')->hasPermissionTo('accounts.manage'));
    }

    public function test_a_role_can_be_updated(): void
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('Auditor');
        $role->syncPermissions(['reports.view']);

        $this->actingAs($user)->put(route('security.roles.update', $role), [
            'name' => 'Auditor',
            'permissions' => ['reports.view', 'audit.view'],
        ])->assertRedirect(route('security.roles.index'));

        $this->assertTrue($role->fresh()->hasPermissionTo('audit.view'));
    }

    public function test_the_administrator_role_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $administrator = Role::findByName('Administrator');

        $this->actingAs($user)->delete(route('security.roles.destroy', $administrator))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('roles', ['name' => 'Administrator']);
    }

    public function test_a_role_assigned_to_a_user_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $assignedUser = User::factory()->create();
        $assignedUser->syncRoles([]);
        $role = Role::findOrCreate('Auditor');
        $assignedUser->assignRole($role);

        $this->actingAs($admin)->delete(route('security.roles.destroy', $role))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('roles', ['name' => 'Auditor']);
    }

    public function test_an_unassigned_non_administrator_role_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('Auditor');

        $this->actingAs($user)->delete(route('security.roles.destroy', $role))
            ->assertRedirect(route('security.roles.index'));

        $this->assertDatabaseMissing('roles', ['name' => 'Auditor']);
    }
}
