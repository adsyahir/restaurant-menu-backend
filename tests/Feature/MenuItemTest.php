<?php

use App\Models\Category;
use App\Models\MenuItem;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('creates a menu item with json variants and tags', function () {
    [, $workspace] = actingAsOwner();
    $category = Category::factory()->create(['workspace_id' => $workspace->id]);

    postJson('/api/menu-items', [
        'categoryId' => $category->id,
        'name' => 'Nasi Lemak',
        'basePrice' => 9.5,
        'dietaryTags' => ['halal', 'spicy'],
        'variants' => [['name' => 'Large', 'priceModifier' => 2]],
    ])
        ->assertCreated()
        ->assertJsonPath('data.basePrice', 9.5)
        ->assertJsonPath('data.dietaryTags', ['halal', 'spicy'])
        ->assertJsonPath('data.variants.0.name', 'Large');
});

it('rejects a category from another workspace', function () {
    actingAsOwner();
    $foreignCategory = Category::factory()->create();

    postJson('/api/menu-items', [
        'categoryId' => $foreignCategory->id,
        'name' => 'X',
        'basePrice' => 1,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('categoryId');
});

it('filters menu items by category', function () {
    [, $workspace] = actingAsOwner();
    $catA = Category::factory()->create(['workspace_id' => $workspace->id]);
    $catB = Category::factory()->create(['workspace_id' => $workspace->id]);
    MenuItem::factory()->count(2)->create(['workspace_id' => $workspace->id, 'category_id' => $catA->id]);
    MenuItem::factory()->create(['workspace_id' => $workspace->id, 'category_id' => $catB->id]);

    getJson("/api/menu-items?category_id={$catA->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('updates and deletes a menu item', function () {
    [, $workspace] = actingAsOwner();
    $item = MenuItem::factory()->create(['workspace_id' => $workspace->id]);

    putJson("/api/menu-items/{$item->id}", ['name' => 'Updated', 'isAvailable' => false])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated')
        ->assertJsonPath('data.isAvailable', false);

    deleteJson("/api/menu-items/{$item->id}")->assertNoContent();
    $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
});

it('isolates menu items across workspaces', function () {
    actingAsOwner();
    $foreign = MenuItem::factory()->create();

    getJson("/api/menu-items/{$foreign->id}")->assertNotFound();
});
