<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_change_another_users_roles(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $target->syncRoles(['Viewer']);

        $this->actingAs($admin)->put(route('security.users.update', $target), [
            'roles' => ['Accountant'],
        ])->assertRedirect(route('security.users.index'));

        $this->assertTrue($target->fresh()->hasRole('Accountant'));
        $this->assertFalse($target->fresh()->hasRole('Viewer'));
    }

    public function test_a_user_cannot_remove_their_own_administrator_role(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['Administrator']);

        $this->actingAs($admin)->put(route('security.users.update', $admin), [
            'roles' => ['Viewer'],
        ])->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->hasRole('Administrator'));
    }

    public function test_an_administrator_can_remove_another_administrators_role(): void
    {
        $admin = User::factory()->create();
        $otherAdmin = User::factory()->create();
        $otherAdmin->syncRoles(['Administrator']);

        $this->actingAs($admin)->put(route('security.users.update', $otherAdmin), [
            'roles' => ['Viewer'],
        ])->assertRedirect(route('security.users.index'));

        $this->assertFalse($otherAdmin->fresh()->hasRole('Administrator'));
    }
}
