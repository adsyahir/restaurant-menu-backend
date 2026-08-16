<?php

use App\Models\User;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('lists workspace members', function () {
    actingAsOwner();

    getJson('/api/staff')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.role', 'admin');
});

it('attaches an existing user as staff', function () {
    [, $workspace] = actingAsOwner();
    $waiter = User::factory()->create();

    postJson('/api/staff', ['email' => $waiter->email, 'role' => 'waiter'])
        ->assertCreated()
        ->assertJsonPath('data.role', 'waiter');

    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $workspace->id,
        'user_id' => $waiter->id,
        'role' => 'waiter',
    ]);
});

it('rejects attaching a non-existent user', function () {
    actingAsOwner();

    postJson('/api/staff', ['email' => 'ghost@example.com', 'role' => 'waiter'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('rejects a duplicate membership', function () {
    [$owner] = actingAsOwner();

    postJson('/api/staff', ['email' => $owner->email, 'role' => 'waiter'])
        ->assertStatus(422);
});

it('updates a member role', function () {
    [, $workspace] = actingAsOwner();
    $waiter = User::factory()->create();
    $workspace->members()->attach($waiter->id, ['role' => 'waiter']);

    putJson("/api/staff/{$waiter->id}", ['role' => 'kitchen'])
        ->assertOk()
        ->assertJsonPath('data.role', 'kitchen');
});

it('toggles a member active state', function () {
    [, $workspace] = actingAsOwner();
    $waiter = User::factory()->create();
    $workspace->members()->attach($waiter->id, ['role' => 'waiter', 'is_active' => true]);

    putJson("/api/staff/{$waiter->id}", ['isActive' => false])
        ->assertOk()
        ->assertJsonPath('data.isActive', false)
        ->assertJsonPath('data.role', 'waiter');

    $this->assertDatabaseHas('workspace_user', [
        'user_id' => $waiter->id,
        'is_active' => false,
    ]);
});

it('removes a member but never the owner', function () {
    [$owner, $workspace] = actingAsOwner();
    $waiter = User::factory()->create();
    $workspace->members()->attach($waiter->id, ['role' => 'waiter']);

    deleteJson("/api/staff/{$waiter->id}")->assertNoContent();
    $this->assertDatabaseMissing('workspace_user', ['user_id' => $waiter->id]);

    deleteJson("/api/staff/{$owner->id}")->assertStatus(422);
});
