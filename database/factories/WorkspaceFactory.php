<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'emoji' => '🍜',
            'cuisine' => fake()->randomElement(['Malaysian', 'Thai', 'Chinese', null]),
            'country_code' => 'MY',
            'currency' => 'MYR',
            'timezone' => 'Asia/Kuala_Lumpur',
            'plan' => 'free',
            'owner_id' => User::factory(),
        ];
    }
}
