<?php

namespace Database\Factories;

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
            // Account subscription (mirrors the users table defaults so the
            // model instance carries them without a refresh).
            'plan' => 'free',
            'subscription_status' => 'trialing',
            'trial_ends_at' => now()->addMonths(3),
            'renews_on' => null,
        ];
    }

    /**
     * A paid Pro account (up to 5 restaurants).
     */
    public function pro(): static
    {
        return $this->state(fn () => [
            'plan' => 'pro',
            'subscription_status' => 'active',
            'trial_ends_at' => null,
            'renews_on' => now()->addMonth()->toDateString(),
        ]);
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
