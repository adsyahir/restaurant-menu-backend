<?php

use App\Models\Order;
use App\Models\OrderItem;

use function Pest\Laravel\getJson;

it('aggregates order stats for the current workspace', function () {
    [, $workspace] = actingAsOwner();

    $a = Order::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => 'paid',
        'total' => 30,
        'placed_at' => now(),
    ]);
    OrderItem::factory()->create(['order_id' => $a->id, 'name' => 'Nasi Lemak', 'unit_price' => 10, 'quantity' => 3]);

    // Cancelled orders are excluded from revenue.
    Order::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => 'cancelled',
        'total' => 999,
        'placed_at' => now(),
    ]);

    getJson('/api/dashboard?range=today')
        ->assertOk()
        ->assertJsonPath('summary.totalOrders', 1)
        ->assertJsonPath('summary.revenue', 30)
        ->assertJsonPath('summary.avgOrderValue', 30)
        ->assertJsonPath('topSelling.0.name', 'Nasi Lemak')
        ->assertJsonPath('topSelling.0.quantitySold', 3);
});

it('does not leak other workspace stats', function () {
    actingAsOwner();
    Order::factory()->create(['status' => 'paid', 'total' => 500, 'placed_at' => now()]);

    getJson('/api/dashboard?range=today')
        ->assertOk()
        ->assertJsonPath('summary.totalOrders', 0)
        ->assertJsonPath('summary.revenue', 0);
});
