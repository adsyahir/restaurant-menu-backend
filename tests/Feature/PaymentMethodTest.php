<?php

use App\Models\PaymentMethod;
use App\Models\Workspace;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

it('lists only the current workspace payment methods', function () {
    [, $workspace] = actingAsOwner();
    PaymentMethod::factory()->for($workspace)->create(['last4' => '1111']);
    PaymentMethod::factory()->create(['last4' => '9999']); // other workspace

    getJson('/api/payment-methods')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.last4', '1111');
});

it('makes the first card default automatically', function () {
    actingAsOwner();

    postJson('/api/payment-methods', [
        'brand' => 'Visa', 'last4' => '4242', 'expMonth' => 8, 'expYear' => 2029,
    ])->assertCreated()->assertJsonPath('data.isDefault', true);
});

it('promoting a new default demotes the previous one', function () {
    [, $workspace] = actingAsOwner();
    $first = PaymentMethod::factory()->for($workspace)->default()->create();

    postJson('/api/payment-methods', [
        'brand' => 'Mastercard', 'last4' => '5555', 'expMonth' => 1, 'expYear' => 2030, 'isDefault' => true,
    ])->assertCreated()->assertJsonPath('data.isDefault', true);

    expect($first->fresh()->is_default)->toBeFalse();
});

it('updates a card', function () {
    [, $workspace] = actingAsOwner();
    $card = PaymentMethod::factory()->for($workspace)->create();

    patchJson("/api/payment-methods/{$card->id}", ['last4' => '0001'])
        ->assertOk()
        ->assertJsonPath('data.last4', '0001');
});

it('deleting the default promotes another card', function () {
    [, $workspace] = actingAsOwner();
    $default = PaymentMethod::factory()->for($workspace)->default()->create();
    $other = PaymentMethod::factory()->for($workspace)->create();

    deleteJson("/api/payment-methods/{$default->id}")->assertNoContent();

    expect($other->fresh()->is_default)->toBeTrue();
});

it('rejects a non-4-digit last4', function () {
    actingAsOwner();

    postJson('/api/payment-methods', [
        'brand' => 'Visa', 'last4' => '42', 'expMonth' => 8, 'expYear' => 2029,
    ])->assertStatus(422)->assertJsonValidationErrors('last4');
});

it('forbids non-admin members from adding a card', function () {
    actingAsMember('waiter');

    postJson('/api/payment-methods', [
        'brand' => 'Visa', 'last4' => '4242', 'expMonth' => 8, 'expYear' => 2029,
    ])->assertForbidden();
});

it('cannot touch another workspace card', function () {
    actingAsOwner();
    $foreign = PaymentMethod::factory()->for(Workspace::factory())->create();

    patchJson("/api/payment-methods/{$foreign->id}", ['last4' => '0000'])->assertNotFound();
});
