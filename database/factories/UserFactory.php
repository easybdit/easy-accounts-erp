<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Every factory-made user defaults to full access (Administrator), since
     * most existing tests exercise business/domain logic and were written
     * before permission gating existed — they assume an unrestricted actor.
     * Tests that specifically exercise the permission matrix should call
     * ->syncRoles(['SomeOtherRole']) on the created user to override this.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            $administrator = Role::whereName('Administrator')->first();

            if ($administrator !== null) {
                $user->assignRole($administrator);
            }
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
