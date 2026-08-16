<?php

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

it('shows the account subscription with plan limits and usage', function () {
    actingAsOwner(); // factory user defaults to the free plan, owns 1 workspace

    getJson('/api/subscription')
        ->assertOk()
        ->assertJsonPath('plan', 'free')
        ->assertJsonPath('limits.restaurants', 1)
        ->assertJsonPath('usage.restaurants', 1)
        ->assertJsonPath('canAddRestaurant', false);
});

it('upgrades to pro', function () {
    [$user] = actingAsOwner();

    putJson('/api/subscription', ['plan' => 'pro'])
        ->assertOk()
        ->assertJsonPath('plan', 'pro')
        ->assertJsonPath('status', 'active')
        ->assertJsonPath('limits.restaurants', 5)
        ->assertJsonPath('canAddRestaurant', true);

    expect($user->fresh()->renews_on)->not->toBeNull();
});

it('reports unlimited restaurants on business', function () {
    actingAsOwner();

    putJson('/api/subscription', ['plan' => 'business'])
        ->assertOk()
        ->assertJsonPath('limits.restaurants', null)
        ->assertJsonPath('canAddRestaurant', true);
});

it('downgrading to free keeps status active with no renewal', function () {
    [$user] = actingAsOwner();
    putJson('/api/subscription', ['plan' => 'pro'])->assertOk();

    putJson('/api/subscription', ['plan' => 'free'])
        ->assertOk()
        ->assertJsonPath('plan', 'free')
        ->assertJsonPath('status', 'active')
        ->assertJsonPath('limits.restaurants', 1);

    expect($user->fresh()->renews_on)->toBeNull();
});

it('rejects an invalid plan', function () {
    actingAsOwner();

    putJson('/api/subscription', ['plan' => 'enterprise'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('plan');
});

it('requires authentication', function () {
    getJson('/api/subscription')->assertUnauthorized();
});
