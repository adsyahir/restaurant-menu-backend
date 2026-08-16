<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 120);

        return [
            'workspace_id' => Workspace::factory(),
            'order_number' => 'ORD-'.fake()->unique()->numberBetween(1, 999999),
            'table_id' => null,
            'created_by' => null,
            'status' => 'placed',
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'notes' => null,
            'payment_method' => null,
            'placed_at' => now(),
        ];
    }
}
