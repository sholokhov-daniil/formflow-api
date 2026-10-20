<?php

use App\Modules\Identity\Infrastructure\Eloquent\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Auth;

test('the user factory method instantiates the modular user', function () {
    $user = User::factory()->make();

    expect($user)->toBeInstanceOf(User::class);
});

test('the standalone factory instantiates the modular user', function () {
    $user = UserFactory::new()->make();

    expect($user)->toBeInstanceOf(User::class);
});

test('the configured guard authenticates a user with a hashed password', function () {
    $user = new User([
        'name' => 'Test User',
        'email' => 'user@example.com',
        'password' => 'secret-password',
    ]);
    $user->id = 1;
    $guard = Auth::guard('web');

    expect($guard->getProvider()->validateCredentials($user, ['password' => 'secret-password']))->toBeTrue();
    expect($guard->getProvider()->validateCredentials($user, ['password' => 'wrong-password']))->toBeFalse();

    $guard->login($user);

    $this->assertAuthenticatedAs($user, 'web');
});

test('serialized users hide the password and remember token', function () {
    $user = User::factory()->make([
        'email' => 'user@example.com',
        'remember_token' => 'secret-token',
    ]);

    $attributes = $user->toArray();

    expect($attributes)
        ->toHaveKey('email', 'user@example.com')
        ->not->toHaveKey('password')
        ->not->toHaveKey('remember_token');
});
