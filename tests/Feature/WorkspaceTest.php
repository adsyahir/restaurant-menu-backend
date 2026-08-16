<?php

use App\Models\Workspace;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

it('shows the current workspace', function () {
    [, $workspace] = actingAsOwner();

    getJson('/api/workspace')
        ->assertOk()
        ->assertJsonPath('data.id', $workspace->id)
        ->assertJsonPath('data.slug', $workspace->slug);
});

it('updates workspace profile and plan', function () {
    [, $workspace] = actingAsOwner();

    putJson('/api/workspace', ['name' => 'New Name', 'plan' => 'pro', 'cuisine' => 'Thai'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.plan', 'pro')
        ->assertJsonPath('data.cuisine', 'Thai');

    $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'plan' => 'pro']);
});

it('rejects an invalid plan', function () {
    actingAsOwner();

    putJson('/api/workspace', ['plan' => 'enterprise'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('plan');
});

it('lists every workspace the user belongs to', function () {
    [$user, $first] = actingAsOwner();
    $second = Workspace::factory()->create(['owner_id' => $user->id]);
    $second->members()->attach($user->id, ['role' => 'admin']);
    Workspace::factory()->create(); // someone else's — must not appear

    getJson('/api/workspaces')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['uuid' => $first->uuid])
        ->assertJsonFragment(['uuid' => $second->uuid]);
});

it('switches to a workspace the user belongs to', function () {
    [$user] = actingAsOwner();
    $target = Workspace::factory()->create(['owner_id' => $user->id]);
    $target->members()->attach($user->id, ['role' => 'admin']);

    putJson("/api/workspaces/{$target->uuid}/current")
        ->assertOk()
        ->assertJsonPath('data.uuid', $target->uuid);

    $this->assertDatabaseHas('users', ['id' => $user->id, 'current_workspace_id' => $target->id]);
});

it('forbids switching to a workspace the user is not a member of', function () {
    [$user] = actingAsOwner();
    $foreign = Workspace::factory()->create(); // not a member

    putJson("/api/workspaces/{$foreign->uuid}/current")->assertForbidden();

    $this->assertDatabaseMissing('users', ['id' => $user->id, 'current_workspace_id' => $foreign->id]);
});
