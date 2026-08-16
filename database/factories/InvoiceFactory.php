<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'number' => 'INV-'.fake()->unique()->numberBetween(1000, 999999),
            'issued_on' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, 20, 300),
            'status' => fake()->randomElement(['paid', 'due', 'failed']),
        ];
    }
}
