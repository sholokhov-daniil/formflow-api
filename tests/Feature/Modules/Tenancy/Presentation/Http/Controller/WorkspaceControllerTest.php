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

test('workspace creation returns the detailed resource and creates membership for its creator', function (string $kind) {
    $user = UserFactory::new()->create(['password' => 'correct-password']);

    $response = $this->withBasicAuth($user->email, 'correct-password')->postJson('/api/v1/workspaces', [
        'name' => 'Product team',
        'kind' => $kind,
    ]);

    $response
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'kind',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJsonPath('data.name', 'Product team')
        ->assertJsonPath('data.kind', $kind);

    $workspaceId = $response->json('data.id');

    expect($workspaceId)->toBeUuid();
    $this->assertDatabaseHas('workspaces', [
        'id' => $workspaceId,
        'name' => 'Product team',
        'kind' => $kind,
    ]);
    $this->assertDatabaseHas('workspace_memberships', [
        'workspace_id' => $workspaceId,
        'user_id' => $user->id,
    ]);
})->with(['personal', 'team']);

test('workspace creation returns 401 without Basic credentials', function () {
    $response = $this->postJson('/api/v1/workspaces', [
        'name' => 'Product team',
        'kind' => 'team',
    ]);

    $response->assertUnauthorized()->assertExactJson(['message' => 'Invalid credentials.']);
    $this->assertDatabaseCount('workspaces', 0);
    $this->assertDatabaseCount('workspace_memberships', 0);
});

test('workspace creation returns 403 when the create ability is denied', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    Gate::before(fn (User $user, string $ability): ?bool => $ability === 'create' ? false : null);

    $response = $this->withBasicAuth($user->email, 'correct-password')->postJson('/api/v1/workspaces', [
        'name' => 'Product team',
        'kind' => 'team',
    ]);

    $response->assertForbidden()->assertExactJson(['message' => 'This action is unauthorized.']);
    $this->assertDatabaseCount('workspaces', 0);
    $this->assertDatabaseCount('workspace_memberships', 0);
});

test('workspace creation returns 422 for invalid input', function (array $payload, array $invalidFields) {
    $user = UserFactory::new()->create(['password' => 'correct-password']);

    $response = $this->withBasicAuth($user->email, 'correct-password')
        ->postJson('/api/v1/workspaces', $payload);

    $response->assertUnprocessable()->assertInvalid($invalidFields);
    $this->assertDatabaseCount('workspaces', 0);
    $this->assertDatabaseCount('workspace_memberships', 0);
})->with([
    'missing both fields' => [[], ['name', 'kind']],
    'missing name' => [['kind' => 'team'], ['name']],
    'missing kind' => [['name' => 'Product team'], ['kind']],
    'non-string name' => [['name' => 123, 'kind' => 'team'], ['name']],
    'name exceeds maximum length' => [['name' => str_repeat('a', 256), 'kind' => 'team'], ['name']],
    'unsupported kind' => [['name' => 'Product team', 'kind' => 'enterprise'], ['kind']],
]);

test('workspace details return 401 JSON without Basic credentials even when HTML is requested', function () {
    $workspace = WorkspaceFactory::new()->create();

    $response = $this->get('/api/v1/workspaces/'.$workspace->id, ['Accept' => 'text/html']);

    $response
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json')
        ->assertHeader('WWW-Authenticate', 'Basic')
        ->assertExactJson(['message' => 'Invalid credentials.']);
});

test('workspace details return 401 for invalid Basic credentials', function (string $email, string $password) {
    $user = UserFactory::new()->create([
        'email' => 'member@example.test',
        'password' => 'correct-password',
    ]);
    $workspace = WorkspaceFactory::new()->create();
    WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();

    $response = $this->withBasicAuth($email, $password)->getJson('/api/v1/workspaces/'.$workspace->id);

    $response
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Basic')
        ->assertExactJson(['message' => 'Invalid credentials.']);
})->with([
    'unknown email' => ['unknown@example.test', 'correct-password'],
    'incorrect password' => ['member@example.test', 'incorrect-password'],
]);

test('workspace details return the requested workspace and timestamps for a member', function (string $kind) {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $workspace = WorkspaceFactory::new()->create([
        'name' => 'Product workspace',
        'kind' => $kind,
        'created_at' => '2026-10-19 10:00:00',
        'updated_at' => '2026-10-20 12:30:00',
    ]);
    WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();
    WorkspaceMembershipFactory::new()->for($user)->create();

    $response = $this->withBasicAuth($user->email, 'correct-password')->getJson('/api/v1/workspaces/'.$workspace->id);

    $response->assertOk()->assertExactJson([
        'data' => [
            'id' => $workspace->id,
            'name' => 'Product workspace',
            'kind' => $kind,
            'created_at' => '2026-10-19T10:00:00.000000Z',
            'updated_at' => '2026-10-20T12:30:00.000000Z',
        ],
    ]);
})->with(['personal', 'team']);

test('workspace details return 403 for a member of another workspace', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    WorkspaceMembershipFactory::new()->for($user)->create();
    $membership = WorkspaceMembershipFactory::new()->create();

    $response = $this->withBasicAuth($user->email, 'correct-password')->getJson('/api/v1/workspaces/'.$membership->workspace_id);

    $response->assertForbidden()->assertExactJson(['message' => 'This action is unauthorized.']);
});

test('workspace details return 404 for an unknown workspace UUID', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);

    $response = $this->withBasicAuth($user->email, 'correct-password')
        ->getJson('/api/v1/workspaces/019a0000-0000-7000-8000-000000000099');

    $response->assertNotFound()->assertExactJson(['message' => 'The route or resource was not found.']);
});

test('workspace details return 429 after three failed authentication attempts', function () {
    $this->freezeTime();
    $workspace = WorkspaceFactory::new()->create();
    $path = '/api/v1/workspaces/'.$workspace->id;
    $this->getJson($path)->assertUnauthorized();
    $this->getJson($path)->assertUnauthorized();

    $response = $this->getJson($path);

    $response
        ->assertTooManyRequests()
        ->assertHeader('Retry-After', '10800')
        ->assertExactJson([
            'message' => 'Too many failed authentication attempts.',
            'retry_after' => 10800,
        ]);
});
