<?php

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Workspace;

use function Pest\Laravel\getJson;

it('serves a public menu by slug without auth', function () {
    $workspace = Workspace::factory()->create(['slug' => 'warung-nusantara', 'name' => 'Warung Nusantara']);
    $category = Category::factory()->create(['workspace_id' => $workspace->id, 'is_active' => true]);
    MenuItem::factory()->create(['workspace_id' => $workspace->id, 'category_id' => $category->id, 'name' => 'Nasi Lemak']);

    getJson('/api/menu/warung-nusantara')
        ->assertOk()
        ->assertJsonPath('workspace.name', 'Warung Nusantara')
        ->assertJsonCount(1, 'categories')
        ->assertJsonPath('items.0.name', 'Nasi Lemak');
});

it('hides inactive categories and their items from the public menu', function () {
    $workspace = Workspace::factory()->create(['slug' => 'kopitiam']);
    $inactive = Category::factory()->create(['workspace_id' => $workspace->id, 'is_active' => false]);
    MenuItem::factory()->create(['workspace_id' => $workspace->id, 'category_id' => $inactive->id]);

    getJson('/api/menu/kopitiam')
        ->assertOk()
        ->assertJsonCount(0, 'categories')
        ->assertJsonCount(0, 'items');
});

it('returns 404 for an unknown restaurant slug', function () {
    getJson('/api/menu/does-not-exist')->assertNotFound();
});
