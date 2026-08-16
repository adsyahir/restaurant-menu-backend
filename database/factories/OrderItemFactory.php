<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'menu_item_id' => null,
            'name' => fake()->words(2, true),
            'variant_label' => null,
            'unit_price' => fake()->randomFloat(2, 3, 30),
            'quantity' => fake()->numberBetween(1, 4),
            'notes' => null,
        ];
    }
}
