<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\patchJson;
use function Pest\Laravel\putJson;

it('updates the signed-in user name and email', function () {
    [$user] = actingAsOwner();

    patchJson('/api/user', ['name' => 'Updated Name', 'email' => 'updated@example.com'])
        ->assertOk()
        ->assertJsonPath('name', 'Updated Name')
        ->assertJsonPath('email', 'updated@example.com');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

it('lets the user keep their own email', function () {
    [$user] = actingAsOwner();

    patchJson('/api/user', ['name' => 'Same Email', 'email' => $user->email])
        ->assertOk();
});

it('rejects an email already used by another user', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    actingAsOwner();

    patchJson('/api/user', ['email' => 'taken@example.com'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('requires authentication to update the profile', function () {
    patchJson('/api/user', ['name' => 'Nope'])->assertUnauthorized();
});

it('changes the password with the correct current password', function () {
    [$user] = actingAsOwner();

    putJson('/api/user/password', [
        'current_password' => 'password',
        'password' => 'new-secret-pass',
        'password_confirmation' => 'new-secret-pass',
    ])->assertOk();

    expect(Hash::check('new-secret-pass', $user->fresh()->password))->toBeTrue();
});

it('rejects a wrong current password', function () {
    actingAsOwner();

    putJson('/api/user/password', [
        'current_password' => 'wrong-password',
        'password' => 'new-secret-pass',
        'password_confirmation' => 'new-secret-pass',
    ])->assertStatus(422)->assertJsonValidationErrors('current_password');
});

it('rejects a new password that is not confirmed', function () {
    actingAsOwner();

    putJson('/api/user/password', [
        'current_password' => 'password',
        'password' => 'new-secret-pass',
        'password_confirmation' => 'different',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});
