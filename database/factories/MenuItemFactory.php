<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'category_id' => fn (array $attrs) => Category::factory()->create(['workspace_id' => $attrs['workspace_id']])->id,
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'base_price' => fake()->randomFloat(2, 3, 40),
            'is_available' => true,
            'dietary_tags' => fake()->randomElements(['halal', 'spicy', 'vegetarian'], 2),
            'variants' => [],
            'add_ons' => [],
            'image_url' => null,
        ];
    }
}
