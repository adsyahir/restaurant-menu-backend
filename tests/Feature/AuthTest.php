<?php

use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function registerPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'restaurant_name' => 'Warung Test',
        'slug' => 'warung-test',
        'country_code' => 'MY',
        'plan' => 'free',
        'terms' => true,
    ], $overrides);
}

it('registers an owner, workspace and returns a token', function () {
    $response = postJson('/api/register', registerPayload());

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'current_workspace']]);

    expect(User::where('email', 'owner@example.com')->exists())->toBeTrue();
    expect($response->json('user.current_workspace.slug'))->toBe('warung-test');
});

it('rejects registration with a duplicate email', function () {
    postJson('/api/register', registerPayload());

    postJson('/api/register', registerPayload(['slug' => 'another-slug']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('logs in with valid credentials', function () {
    postJson('/api/register', registerPayload());

    postJson('/api/login', ['email' => 'owner@example.com', 'password' => 'Password123!'])
        ->assertOk()
        ->assertJsonStructure(['token', 'user']);
});

it('rejects login with wrong password', function () {
    postJson('/api/register', registerPayload());

    postJson('/api/login', ['email' => 'owner@example.com', 'password' => 'wrong'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('blocks the user endpoint without a token', function () {
    getJson('/api/user')->assertUnauthorized();
});

it('returns the authenticated user with a token', function () {
    [$user] = actingAsOwner();

    getJson('/api/user')->assertOk()->assertJsonPath('id', $user->id);
});
