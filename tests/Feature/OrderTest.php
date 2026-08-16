<?php

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\RestaurantTable;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('creates an order with computed totals and a sequential number', function () {
    [$user, $workspace] = actingAsOwner();
    $item = MenuItem::factory()->create(['workspace_id' => $workspace->id, 'base_price' => 9.5]);
    $table = RestaurantTable::factory()->create(['workspace_id' => $workspace->id]);

    postJson('/api/orders', [
        'tableId' => $table->id,
        'items' => [
            ['menuItemId' => $item->id, 'name' => 'Nasi Lemak', 'unitPrice' => 9.5, 'quantity' => 2],
            ['name' => 'Teh Tarik', 'unitPrice' => 3, 'quantity' => 1],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.orderNumber', 'ORD-0001')
        ->assertJsonPath('data.subtotal', 22)
        ->assertJsonPath('data.total', 22)
        ->assertJsonPath('data.createdByName', $user->name)
        ->assertJsonCount(2, 'data.items');
});

it('reconciles line prices from the menu, ignoring a tampered client price', function () {
    [, $workspace] = actingAsOwner();
    $item = MenuItem::factory()->create(['workspace_id' => $workspace->id, 'base_price' => 12.00]);

    // Client sends a bogus 0.01 price; server must use the menu's 12.00.
    postJson('/api/orders', [
        'items' => [
            ['menuItemId' => $item->id, 'name' => 'Hacked', 'unitPrice' => 0.01, 'quantity' => 2],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.total', 24)
        ->assertJsonPath('data.items.0.unitPrice', 12)
        ->assertJsonPath('data.items.0.name', $item->name);
});

it('increments the order number per workspace', function () {
    [, $workspace] = actingAsOwner();
    $item = MenuItem::factory()->create(['workspace_id' => $workspace->id]);
    $payload = ['items' => [['menuItemId' => $item->id, 'name' => 'X', 'unitPrice' => 5, 'quantity' => 1]]];

    postJson('/api/orders', $payload)->assertJsonPath('data.orderNumber', 'ORD-0001');
    postJson('/api/orders', $payload)->assertJsonPath('data.orderNumber', 'ORD-0002');
});

it('requires at least one item', function () {
    actingAsOwner();

    postJson('/api/orders', ['items' => []])
        ->assertStatus(422)
        ->assertJsonValidationErrors('items');
});

it('advances an order status', function () {
    [, $workspace] = actingAsOwner();
    $order = Order::factory()->create(['workspace_id' => $workspace->id]);

    putJson("/api/orders/{$order->id}", ['status' => 'preparing'])
        ->assertOk()
        ->assertJsonPath('data.status', 'preparing');
});

it('replaces items and recomputes totals on update', function () {
    [, $workspace] = actingAsOwner();
    $order = Order::factory()->create(['workspace_id' => $workspace->id, 'subtotal' => 5, 'total' => 5]);

    putJson("/api/orders/{$order->id}", [
        'items' => [['name' => 'New', 'unitPrice' => 10, 'quantity' => 3]],
    ])
        ->assertOk()
        ->assertJsonPath('data.total', 30)
        ->assertJsonCount(1, 'data.items');
});

it('isolates orders across workspaces', function () {
    actingAsOwner();
    $foreign = Order::factory()->create();

    getJson("/api/orders/{$foreign->id}")->assertNotFound();
    deleteJson("/api/orders/{$foreign->id}")->assertNotFound();
});
