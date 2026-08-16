<?php

use App\Models\RestaurantTable;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('creates a table', function () {
    [, $workspace] = actingAsOwner();

    postJson('/api/tables', ['label' => 'T1', 'seatingCapacity' => 4])
        ->assertCreated()
        ->assertJsonPath('data.label', 'T1')
        ->assertJsonPath('data.seatingCapacity', 4)
        ->assertJsonPath('data.status', 'available');

    $this->assertDatabaseHas('restaurant_tables', ['label' => 'T1', 'workspace_id' => $workspace->id]);
});

it('rejects an invalid status', function () {
    actingAsOwner();

    postJson('/api/tables', ['label' => 'T2', 'status' => 'flying'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});

it('updates a table status', function () {
    [, $workspace] = actingAsOwner();
    $table = RestaurantTable::factory()->create(['workspace_id' => $workspace->id]);

    putJson("/api/tables/{$table->id}", ['status' => 'occupied'])
        ->assertOk()
        ->assertJsonPath('data.status', 'occupied');
});

it('lists and deletes tables scoped to workspace', function () {
    [, $workspace] = actingAsOwner();
    RestaurantTable::factory()->count(3)->create(['workspace_id' => $workspace->id]);
    RestaurantTable::factory()->create();

    getJson('/api/tables')->assertOk()->assertJsonCount(3, 'data');

    $foreign = RestaurantTable::factory()->create();
    deleteJson("/api/tables/{$foreign->id}")->assertNotFound();
});
