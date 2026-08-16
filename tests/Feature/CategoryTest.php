<?php

use App\Models\Category;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('lists only the current workspace categories', function () {
    [, $workspace] = actingAsOwner();
    Category::factory()->count(2)->create(['workspace_id' => $workspace->id]);
    Category::factory()->create(); // other workspace

    getJson('/api/categories')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('creates a category in the current workspace', function () {
    [, $workspace] = actingAsOwner();

    postJson('/api/categories', ['name' => 'Mains', 'displayOrder' => 2])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Mains')
        ->assertJsonPath('data.displayOrder', 2);

    $this->assertDatabaseHas('categories', ['name' => 'Mains', 'workspace_id' => $workspace->id]);
});

it('validates category name is required', function () {
    actingAsOwner();

    postJson('/api/categories', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

it('updates a category', function () {
    [, $workspace] = actingAsOwner();
    $category = Category::factory()->create(['workspace_id' => $workspace->id]);

    putJson("/api/categories/{$category->id}", ['name' => 'Renamed', 'isActive' => false])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed')
        ->assertJsonPath('data.isActive', false);
});

it('deletes a category', function () {
    [, $workspace] = actingAsOwner();
    $category = Category::factory()->create(['workspace_id' => $workspace->id]);

    deleteJson("/api/categories/{$category->id}")->assertNoContent();

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('cannot access a category from another workspace', function () {
    actingAsOwner();
    $foreign = Category::factory()->create();

    getJson("/api/categories/{$foreign->id}")->assertNotFound();
    putJson("/api/categories/{$foreign->id}", ['name' => 'x'])->assertNotFound();
    deleteJson("/api/categories/{$foreign->id}")->assertNotFound();
});
