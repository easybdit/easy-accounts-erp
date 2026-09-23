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
