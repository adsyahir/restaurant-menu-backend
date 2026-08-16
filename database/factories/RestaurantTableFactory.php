<?php

namespace Database\Factories;

use App\Models\RestaurantTable;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RestaurantTable>
 */
class RestaurantTableFactory extends Factory
{
    protected $model = RestaurantTable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'label' => 'T'.fake()->unique()->numberBetween(1, 999),
            'seating_capacity' => fake()->numberBetween(2, 8),
            'status' => 'available',
        ];
    }
}
