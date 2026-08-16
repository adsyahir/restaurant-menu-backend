<?php

use App\Models\Category;
use App\Models\MenuItem;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('forbids a waiter from managing the menu', function () {
    [, $workspace] = actingAsMember('waiter');
    $category = Category::factory()->create(['workspace_id' => $workspace->id]);

    postJson('/api/categories', ['name' => 'Mains'])->assertForbidden();
    putJson("/api/categories/{$category->id}", ['name' => 'X'])->assertForbidden();
    deleteJson("/api/categories/{$category->id}")->assertForbidden();
    postJson('/api/menu-items', ['categoryId' => $category->id, 'name' => 'X', 'basePrice' => 1])
        ->assertForbidden();
});

it('forbids a waiter from managing staff, workspace and analytics', function () {
    actingAsMember('waiter');

    getJson('/api/staff')->assertForbidden();
    postJson('/api/staff', ['email' => 'x@example.com', 'role' => 'waiter'])->assertForbidden();
    putJson('/api/workspace', ['plan' => 'pro'])->assertForbidden();
    getJson('/api/dashboard')->assertForbidden();
});

it('allows a waiter to read the menu and manage orders', function () {
    [, $workspace] = actingAsMember('waiter');
    $item = MenuItem::factory()->create(['workspace_id' => $workspace->id]);

    getJson('/api/categories')->assertOk();
    getJson('/api/menu-items')->assertOk();
    getJson('/api/workspace')->assertOk();

    postJson('/api/orders', [
        'items' => [['menuItemId' => $item->id, 'name' => 'X', 'unitPrice' => 5, 'quantity' => 1]],
    ])->assertCreated();

    getJson('/api/orders')->assertOk();
});

it('allows an admin to manage everything', function () {
    [, $workspace] = actingAsMember('admin');

    postJson('/api/categories', ['name' => 'Mains'])->assertCreated();
    getJson('/api/dashboard')->assertOk();
    getJson('/api/staff')->assertOk();
});
