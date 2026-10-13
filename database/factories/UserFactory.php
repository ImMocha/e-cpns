<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            'role' => UserRole::User,
            'status' => UserStatus::Active,
            'phone' => fake()->numerify('08## #### ####'),
            'address' => fake()->city().', '.fake()->state(),
            'institution' => fake()->randomElement([
                'Kementerian Dalam Negeri',
                'Kementerian Digital',
                'Dinas Pendidikan Kota Bandung',
                'Universitas Indonesia',
                'PT Telkom Indonesia',
            ]),
            'last_login_at' => fake()->dateTimeBetween('-3 weeks', 'now'),
        ];
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

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Inactive,
            'last_login_at' => null,
        ]);
    }

    public function neverLoggedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_login_at' => null,
        ]);
    }
}
