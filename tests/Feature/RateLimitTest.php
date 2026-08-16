<?php

use function Pest\Laravel\postJson;

it('throttles repeated failed login attempts', function () {
    $payload = ['email' => 'victim@example.com', 'password' => 'wrong-guess'];

    // The auth limiter allows 5 attempts per minute keyed by email+IP.
    for ($i = 0; $i < 5; $i++) {
        postJson('/api/login', $payload)->assertStatus(422); // invalid credentials
    }

    postJson('/api/login', $payload)->assertStatus(429); // too many requests
});
