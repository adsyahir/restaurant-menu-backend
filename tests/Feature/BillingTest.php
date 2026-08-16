<?php

use App\Models\Category;
use App\Models\Invoice;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;

use function Pest\Laravel\getJson;

it('returns the current plan and real usage counters', function () {
    [$owner, $workspace] = actingAsOwner();
    $workspace->update(['plan' => 'pro']);

    // Extra staff member.
    $staff = User::factory()->create();
    $workspace->members()->attach($staff->id, ['role' => 'waiter']);

    // Menu: 1 category, 3 items.
    $category = Category::factory()->create(['workspace_id' => $workspace->id]);
    MenuItem::factory()->count(3)->create([
        'workspace_id' => $workspace->id,
        'category_id' => $category->id,
    ]);

    // Orders this month: 2 counted + 1 cancelled (excluded).
    Order::factory()->count(2)->create(['workspace_id' => $workspace->id, 'placed_at' => now()]);
    Order::factory()->create(['workspace_id' => $workspace->id, 'status' => 'cancelled', 'placed_at' => now()]);

    getJson('/api/billing')
        ->assertOk()
        ->assertJsonPath('plan', 'pro')
        ->assertJsonPath('usage.ordersThisMonth', 2)
        ->assertJsonPath('usage.ordersLimit', 0)      // 0 = unlimited on pro
        ->assertJsonPath('usage.staffSeats', 2)        // owner + waiter
        ->assertJsonPath('usage.staffLimit', 10)
        ->assertJsonPath('usage.menuItems', 3)
        ->assertJsonPath('usage.categories', 1);
});

it('excludes orders placed before this month', function () {
    [, $workspace] = actingAsOwner();
    Order::factory()->create(['workspace_id' => $workspace->id, 'placed_at' => now()->subMonths(2)]);

    getJson('/api/billing')
        ->assertOk()
        ->assertJsonPath('usage.ordersThisMonth', 0);
});

it('reflects free-plan limits', function () {
    [, $workspace] = actingAsOwner();
    $workspace->update(['plan' => 'free']);

    getJson('/api/billing')
        ->assertOk()
        ->assertJsonPath('usage.ordersLimit', 100)
        ->assertJsonPath('usage.staffLimit', 3)
        ->assertJsonPath('usage.menuItemsLimit', 30);
});

it('includes subscription meta, default card and invoices', function () {
    [, $workspace] = actingAsOwner();
    $workspace->update(['subscription_status' => 'active', 'renews_on' => '2026-09-01']);

    PaymentMethod::factory()->for($workspace)->create(['is_default' => false, 'last4' => '1111']);
    PaymentMethod::factory()->for($workspace)->default()->create(['last4' => '4242']);
    Invoice::factory()->for($workspace)->create(['number' => 'INV-99']);

    getJson('/api/billing')
        ->assertOk()
        ->assertJsonPath('subscription.status', 'active')
        ->assertJsonPath('subscription.renewsOn', '2026-09-01')
        ->assertJsonPath('paymentMethod.last4', '4242')      // default surfaced
        ->assertJsonPath('paymentMethod.isDefault', true)
        ->assertJsonCount(1, 'invoices')
        ->assertJsonPath('invoices.0.number', 'INV-99');
});

it('returns a null payment method when none saved', function () {
    actingAsOwner();

    getJson('/api/billing')
        ->assertOk()
        ->assertJsonPath('paymentMethod', null)
        ->assertJsonCount(0, 'invoices');
});

it('requires authentication to view billing', function () {
    getJson('/api/billing')->assertUnauthorized();
});
