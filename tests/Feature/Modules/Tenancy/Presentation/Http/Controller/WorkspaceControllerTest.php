<?php

use App\Modules\Identity\Infrastructure\Eloquent\User;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['app.debug' => false]);
});

test('the workspace list returns 401 JSON without Basic credentials even when HTML is requested', function () {
    $response = $this->get('/api/v1/workspaces', ['Accept' => 'text/html']);

    $response
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json')
        ->assertHeader('WWW-Authenticate', 'Basic')
        ->assertExactJson(['message' => 'Invalid credentials.']);
});

test('the workspace list returns 401 for invalid Basic credentials', function (string $email, string $password) {
    UserFactory::new()->create([
        'email' => 'member@example.test',
        'password' => 'correct-password',
    ]);

    $response = $this->withBasicAuth($email, $password)->getJson('/api/v1/workspaces');

    $response
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Invalid credentials.']);
})->with([
    'unknown email' => ['unknown@example.test', 'correct-password'],
    'incorrect password' => ['member@example.test', 'incorrect-password'],
]);

test('the workspace list returns only the current users workspaces and public fields', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $workspace = WorkspaceFactory::new()->create(['name' => 'Product team']);
    WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();
    WorkspaceMembershipFactory::new()->for($workspace)->create();
    WorkspaceMembershipFactory::new()->create();

    $response = $this->withBasicAuth($user->email, 'correct-password')->getJson('/api/v1/workspaces');

    $response
        ->assertOk()
        ->assertExactJson([
            'data' => [
                ['id' => $workspace->id, 'name' => 'Product team', 'kind' => 'team'],
            ],
        ]);
});

test('the workspace list returns an empty array for a user without memberships', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    WorkspaceMembershipFactory::new()->create();

    $response = $this->withBasicAuth($user->email, 'correct-password')->getJson('/api/v1/workspaces');

    $response->assertOk()->assertExactJson(['data' => []]);
});

test('the workspace list returns 403 when the viewAny ability is denied', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    Gate::before(fn (User $user, string $ability): ?bool => $ability === 'viewAny' ? false : null);

    $response = $this->withBasicAuth($user->email, 'correct-password')->getJson('/api/v1/workspaces');

    $response->assertForbidden()->assertExactJson(['message' => 'This action is unauthorized.']);
});
