<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_create_a_user_with_roles(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['Administrator']);

        $response = $this->actingAs($admin)->post(route('security.users.store'), [
            'name' => 'New Hire',
            'email' => 'newhire@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => ['Accountant'],
        ]);

        $response->assertRedirect(route('security.users.index'));

        $created = User::where('email', 'newhire@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->hasRole('Accountant'));
        $this->assertTrue(Hash::check('password', $created->password));
    }

    public function test_a_user_without_users_manage_permission_cannot_create_a_user(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Viewer']);

        $this->actingAs($user)->post(route('security.users.store'), [
            'name' => 'New Hire',
            'email' => 'newhire@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'newhire@example.com']);
    }

    public function test_creating_a_user_requires_a_unique_email(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['Administrator']);
        $existing = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('security.users.store'), [
            'name' => 'Duplicate',
            'email' => $existing->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_the_public_registration_route_no_longer_exists(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }

    public function test_an_administrator_can_change_another_users_roles(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $target->syncRoles(['Viewer']);

        $this->actingAs($admin)->put(route('security.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
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
            'name' => $admin->name,
            'email' => $admin->email,
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
            'name' => $otherAdmin->name,
            'email' => $otherAdmin->email,
            'roles' => ['Viewer'],
        ])->assertRedirect(route('security.users.index'));

        $this->assertFalse($otherAdmin->fresh()->hasRole('Administrator'));
    }

    public function test_an_administrator_can_update_a_users_name_and_email(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['Administrator']);
        $target = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);
        $target->syncRoles(['Viewer']);

        $this->actingAs($admin)->put(route('security.users.update', $target), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'roles' => ['Viewer'],
        ])->assertRedirect(route('security.users.index'));

        $target->refresh();
        $this->assertSame('New Name', $target->name);
        $this->assertSame('new@example.com', $target->email);
    }

    public function test_updating_a_user_requires_a_unique_email(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['Administrator']);
        $target = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($admin)->put(route('security.users.update', $target), [
            'name' => $target->name,
            'email' => $other->email,
            'roles' => [],
        ])->assertSessionHasErrors('email');
    }

    public function test_an_administrator_can_reset_another_users_password(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['Administrator']);
        $target = User::factory()->create();

        $this->actingAs($admin)->put(route('security.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'roles' => [],
        ])->assertRedirect(route('security.users.index'));

        $this->assertTrue(Hash::check('new-password', $target->fresh()->password));
    }

    public function test_leaving_the_password_blank_keeps_the_users_current_password(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['Administrator']);
        $target = User::factory()->create();
        $originalHash = $target->password;

        $this->actingAs($admin)->put(route('security.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'roles' => [],
        ])->assertRedirect(route('security.users.index'));

        $this->assertSame($originalHash, $target->fresh()->password);
    }
}
