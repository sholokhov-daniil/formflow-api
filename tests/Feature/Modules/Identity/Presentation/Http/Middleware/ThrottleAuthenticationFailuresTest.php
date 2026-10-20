<?php

use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Identity\Presentation\Http\Middleware\ThrottleAuthenticationFailures;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['app.debug' => false]);
});

test('the third authentication failure returns 429 and blocks valid credentials for three hours', function () {
    $this->freezeTime();
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $this->withBasicAuth($user->email, 'incorrect-password');
    $this->getJson('/api/v1/workspaces')->assertUnauthorized();
    $this->getJson('/api/v1/workspaces')->assertUnauthorized();

    $response = $this->getJson('/api/v1/workspaces');

    $response->assertTooManyRequests()
        ->assertHeader('Retry-After', '10800')
        ->assertExactJson([
            'message' => 'Too many failed authentication attempts.',
            'retry_after' => 10800,
        ]);

    $this->travel(10799)->seconds();
    $this->withBasicAuth($user->email, 'correct-password')
        ->getJson('/api/v1/workspaces')
        ->assertTooManyRequests()
        ->assertHeader('Retry-After', '1')
        ->assertJsonPath('retry_after', 1);
    $this->assertGuest();

    $this->travel(1)->seconds();
    $this->getJson('/api/v1/workspaces')->assertOk()->assertExactJson(['data' => []]);
    $this->assertAuthenticatedAs($user);
});

test('authentication blocks are isolated by both client IP and Basic username', function (string $email, string $ip) {
    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1'])
        ->withBasicAuth('blocked@example.test', 'incorrect-password');
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $this->getJson('/api/v1/workspaces');
    }

    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->withBasicAuth($email, 'incorrect-password')
        ->getJson('/api/v1/workspaces');

    $response->assertUnauthorized()->assertExactJson(['message' => 'Invalid credentials.']);
})->with([
    'different IP' => ['blocked@example.test', '192.0.2.2'],
    'different username' => ['other@example.test', '192.0.2.1'],
]);

test('successful authentication resets failures after an allowed request', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $this->withBasicAuth($user->email, 'incorrect-password');
    $this->getJson('/api/v1/workspaces')->assertUnauthorized();
    $this->getJson('/api/v1/workspaces')->assertUnauthorized();

    $response = $this->withBasicAuth($user->email, 'correct-password')->getJson('/api/v1/workspaces');

    $response->assertOk();

    Auth::logout();
    Auth::forgetGuards();
    $this->withBasicAuth($user->email, 'incorrect-password');
    $this->getJson('/api/v1/workspaces')->assertUnauthorized();
    $this->getJson('/api/v1/workspaces')->assertUnauthorized();
    $this->getJson('/api/v1/workspaces')->assertTooManyRequests();
});

test('successful authentication resets failures even when authorization returns 403', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $this->withBasicAuth($user->email, 'incorrect-password');
    $this->getJson('/api/v1/workspaces')->assertUnauthorized();
    $this->getJson('/api/v1/workspaces')->assertUnauthorized();
    Gate::before(fn (User $user, string $ability): ?bool => $ability === 'viewAny' ? false : null);

    $response = $this->withBasicAuth($user->email, 'correct-password')->getJson('/api/v1/workspaces');

    $response->assertForbidden();

    Auth::logout();
    Auth::forgetGuards();
    $this->withBasicAuth($user->email, 'incorrect-password');
    $this->getJson('/api/v1/workspaces')->assertUnauthorized();
    $this->getJson('/api/v1/workspaces')->assertUnauthorized();
    $this->getJson('/api/v1/workspaces')->assertTooManyRequests();
});

test('missing credentials are counted as failed authentication attempts', function () {
    $this->getJson('/api/v1/workspaces')->assertUnauthorized();
    $this->getJson('/api/v1/workspaces')->assertUnauthorized();

    $response = $this->getJson('/api/v1/workspaces');

    $response->assertTooManyRequests();
});

test('unauthenticated responses other than 401 do not count as authentication failures', function () {
    Route::get('/api/test-authentication-failures', fn () => response()->json(['message' => 'Unavailable.'], 503))
        ->middleware(ThrottleAuthenticationFailures::class);
    for ($attempt = 0; $attempt < 4; $attempt++) {
        $this->getJson('/api/test-authentication-failures')->assertServiceUnavailable();
    }

    $response = $this->getJson('/api/v1/workspaces');

    $response->assertUnauthorized();
});

test('an authenticated user receiving 401 from application logic is not counted as a failed login', function () {
    $user = UserFactory::new()->create();
    $this->actingAs($user);
    Route::get('/api/test-authenticated-error', fn () => response()->json(['message' => 'Application error.'], 401))
        ->middleware(ThrottleAuthenticationFailures::class);

    for ($attempt = 0; $attempt < 4; $attempt++) {
        $this->getJson('/api/test-authenticated-error')
            ->assertUnauthorized()->assertExactJson(['message' => 'Application error.']);
    }

    Auth::logout();
    Auth::forgetGuards();
    $this->getJson('/api/v1/workspaces')->assertUnauthorized();
});
