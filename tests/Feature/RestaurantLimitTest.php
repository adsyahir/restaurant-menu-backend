<?php

use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Support\Carbon;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function newRestaurantPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Second Branch',
        'country_code' => 'MY',
        'cuisine' => 'Malaysian',
    ], $overrides);
}

it('blocks a free user from creating a second restaurant', function () {
    [$user] = actingAsOwner(); // free, already owns 1

    postJson('/api/workspaces', newRestaurantPayload())
        ->assertStatus(403);

    expect($user->ownedWorkspaces()->count())->toBe(1);
});

it('lets a pro user create additional restaurants', function () {
    [$user] = actingAsOwner();
    $user->update(['plan' => 'pro', 'subscription_status' => 'active']);

    postJson('/api/workspaces', newRestaurantPayload())
        ->assertCreated()
        ->assertJsonPath('data.name', 'Second Branch');

    expect($user->fresh()->ownedWorkspaces()->count())->toBe(2)
        ->and($user->fresh()->current_workspace_id)->not->toBeNull();
});

it('blocks writes to a restaurant beyond the plan limit', function () {
    [$user, $first] = actingAsOwner(); // free, limit 1

    // A second owned workspace, made current — it's beyond the free limit.
    $second = Workspace::factory()->create(['owner_id' => $user->id]);
    $second->members()->attach($user->id, ['role' => 'admin']);
    $user->update(['current_workspace_id' => $second->id]);

    // Write is refused...
    postJson('/api/categories', ['name' => 'Drinks'])->assertStatus(403);
    // ...but reading still works.
    getJson('/api/categories')->assertOk();

    // The first (within-limit) workspace stays writable.
    $user->update(['current_workspace_id' => $first->id]);
    postJson('/api/categories', ['name' => 'Drinks'])->assertCreated();
});

it('makes the restaurant read-only once the free trial expires', function () {
    [$user, $workspace] = actingAsOwner();
    $user->update([
        'plan' => 'free',
        'subscription_status' => 'trialing',
        'trial_ends_at' => Carbon::now()->subDay(),
    ]);

    postJson('/api/categories', ['name' => 'Drinks'])->assertStatus(403);
    getJson('/api/categories')->assertOk();

    // Upgrading lifts the lock.
    $user->update(['plan' => 'pro', 'subscription_status' => 'active', 'trial_ends_at' => null]);
    postJson('/api/categories', ['name' => 'Drinks'])->assertCreated();

    expect(Category::where('workspace_id', $workspace->id)->count())->toBe(1);
});
