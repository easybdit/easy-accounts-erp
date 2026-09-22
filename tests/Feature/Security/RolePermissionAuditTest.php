<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Role/Permission-change audit trail (Section 90 Phase 11): neither Role's
 * permission sync nor User's role sync is a plain model attribute, so
 * neither is captured by automatic LogsActivity tracking — these are
 * logged explicitly by RoleController/UserController via
 * activity()->withChanges().
 */
class RolePermissionAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_role_logs_its_initial_permissions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('security.roles.store'), [
            'name' => 'Auditor',
            'permissions' => ['reports.view', 'audit.view'],
        ]);

        $role = Role::findByName('Auditor');
        $activity = Activity::where('subject_type', Role::class)->where('subject_id', $role->id)->where('event', 'created')->first();

        $this->assertNotNull($activity);
        $this->assertSame(['audit.view', 'reports.view'], $activity->attribute_changes['attributes']['permissions']);
    }

    public function test_updating_a_roles_permissions_logs_the_before_and_after_sets(): void
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('Auditor');
        $role->syncPermissions(['reports.view']);

        $this->actingAs($user)->put(route('security.roles.update', $role), [
            'name' => 'Auditor',
            'permissions' => ['reports.view', 'audit.view'],
        ]);

        $activity = Activity::where('subject_type', Role::class)->where('subject_id', $role->id)->where('event', 'updated')->latest()->first();

        $this->assertNotNull($activity);
        $this->assertSame(['reports.view'], $activity->attribute_changes['old']['permissions']);
        $this->assertSame(['audit.view', 'reports.view'], $activity->attribute_changes['attributes']['permissions']);
    }

    public function test_deleting_a_role_logs_the_permissions_it_had(): void
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('Auditor');
        $role->syncPermissions(['reports.view']);
        $roleId = $role->id;

        $this->actingAs($user)->delete(route('security.roles.destroy', $role));

        $activity = Activity::where('subject_type', Role::class)->where('subject_id', $roleId)->where('event', 'deleted')->first();

        $this->assertNotNull($activity);
        $this->assertSame(['reports.view'], $activity->attribute_changes['old']['permissions']);
    }

    public function test_changing_a_users_roles_logs_the_before_and_after_sets(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $target->syncRoles([]);
        Role::findOrCreate('Sales');
        Role::findOrCreate('Auditor');
        $target->assignRole('Sales');

        $this->actingAs($admin)->put(route('security.users.update', $target), [
            'roles' => ['Auditor'],
        ]);

        $activity = Activity::where('subject_type', User::class)->where('subject_id', $target->id)->where('event', 'roles_updated')->first();

        $this->assertNotNull($activity);
        $this->assertSame(['Sales'], $activity->attribute_changes['old']['roles']);
        $this->assertSame(['Auditor'], $activity->attribute_changes['attributes']['roles']);
    }

    public function test_the_audit_log_shows_added_and_removed_permissions(): void
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('Auditor');
        $role->syncPermissions(['reports.view']);

        $this->actingAs($user)->put(route('security.roles.update', $role), [
            'name' => 'Auditor',
            'permissions' => ['audit.view'],
        ]);

        $response = $this->actingAs($user)->get(route('security.audit-log.index', ['event' => 'updated']));
        $props = $response->viewData('page')['props'];

        $row = collect($props['activities']['data'])->firstWhere('subject_type', 'Role');
        $this->assertSame(['reports.view'], $row['attribute_changes']['old']['permissions']);
        $this->assertSame(['audit.view'], $row['attribute_changes']['attributes']['permissions']);
    }
}
