<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => cache()->flush()) // reset rate-limiter counters between tests
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

use App\Models\User;
use App\Models\Workspace;
use Laravel\Sanctum\Sanctum;

/**
 * Create an owner user with a workspace, authenticate as them for API calls,
 * and return both. The workspace becomes the user's current workspace.
 *
 * @return array{0: User, 1: Workspace}
 */
function actingAsOwner(): array
{
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->members()->attach($user->id, ['role' => 'admin']);
    $user->update(['current_workspace_id' => $workspace->id]);

    Sanctum::actingAs($user);

    return [$user, $workspace];
}

/**
 * Authenticate as a non-owner member of a workspace with the given role
 * (e.g. 'waiter', 'kitchen'). Returns the user and the workspace.
 *
 * @return array{0: User, 1: Workspace}
 */
function actingAsMember(string $role = 'waiter'): array
{
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $workspace->members()->attach($owner->id, ['role' => 'admin']);

    $member = User::factory()->create();
    $workspace->members()->attach($member->id, ['role' => $role]);
    $member->update(['current_workspace_id' => $workspace->id]);

    Sanctum::actingAs($member);

    return [$member, $workspace];
}
