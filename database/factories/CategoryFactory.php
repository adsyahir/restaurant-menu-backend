<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->randomElement(['Mains', 'Beverages', 'Desserts', 'Snacks']).' '.fake()->numberBetween(1, 999),
            'display_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
