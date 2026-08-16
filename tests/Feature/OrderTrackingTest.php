<?php

use App\Models\Order;
use App\Models\OrderItem;

use function Pest\Laravel\getJson;

it('publicly tracks an order by its token without auth', function () {
    $order = Order::factory()->create(['status' => 'preparing']);
    OrderItem::factory()->create(['order_id' => $order->id, 'name' => 'Roti Canai', 'quantity' => 2]);

    getJson("/api/track/{$order->public_token}")
        ->assertOk()
        ->assertJsonPath('data.orderNumber', $order->order_number)
        ->assertJsonPath('data.status', 'preparing')
        ->assertJsonPath('data.items.0.name', 'Roti Canai')
        ->assertJsonPath('data.items.0.quantity', 2);
});

it('does not expose staff-only fields on the public tracker', function () {
    $order = Order::factory()->create(['notes' => 'secret note', 'payment_method' => 'cash']);

    $response = getJson("/api/track/{$order->public_token}")->assertOk();

    expect($response->json('data'))->not->toHaveKeys(['notes', 'paymentMethod', 'createdByName', 'id']);
});

it('auto-generates a unique public token on order creation', function () {
    $a = Order::factory()->create();
    $b = Order::factory()->create();

    expect($a->public_token)->not->toBeNull()
        ->and($a->public_token)->not->toBe($b->public_token);
});

it('returns 404 for an unknown token', function () {
    getJson('/api/track/00000000-0000-0000-0000-000000000000')->assertNotFound();
});
