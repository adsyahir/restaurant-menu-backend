<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'brand' => fake()->randomElement(['Visa', 'Mastercard', 'Amex']),
            'last4' => (string) fake()->numberBetween(1000, 9999),
            'exp_month' => fake()->numberBetween(1, 12),
            'exp_year' => fake()->numberBetween(2027, 2032),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
