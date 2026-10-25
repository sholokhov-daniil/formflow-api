<?php

use Database\Factories\UserFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/** @return array<string, mixed> */
function createWorkspaceMembershipControllerFixture(): array
{
    $actor = WorkspaceMembershipFactory::new()->create();
    $workspace = $actor->workspace;
    $resource = WorkspaceMembershipFactory::new()->for($workspace)->create();
    $url = '/api/v1/workspaces/'.$workspace->id.'/memberships';

    return compact('actor', 'workspace', 'resource', 'url');
}

test('WorkspaceMembership endpoints return 401 without Basic credentials', function (string $method, bool $detail): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    extract($fixture);
    $attributes = $resource->fresh()->getAttributes();

    $response = $this->json($method, $url.($detail ? '/'.$resource->id : ''), []);

    $response->assertUnauthorized()->assertHeader('WWW-Authenticate', 'Basic');
    expect($resource->fresh()->getAttributes())->toBe($attributes);
})->with([
    'index' => ['GET', false],
    'store' => ['POST', false],
    'show' => ['GET', true],
    'replace' => ['PUT', true],
    'patch' => ['PATCH', true],
    'destroy' => ['DELETE', true],
]);

test('WorkspaceMembership endpoints return 403 for non-members and preserve data', function (string $method, bool $detail): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    extract($fixture);
    $outsider = UserFactory::new()->create();
    $attributes = $resource->fresh()->getAttributes();
    $count = $resource->newQuery()->count();

    $response = $this->withBasicAuth($outsider->email, 'password')
        ->json($method, $url.($detail ? '/'.$resource->id : ''), []);

    $response->assertForbidden();
    expect($resource->fresh()->getAttributes())->toBe($attributes);
    $this->assertDatabaseCount($resource->getTable(), $count);
})->with([
    'index' => ['GET', false],
    'store' => ['POST', false],
    'show' => ['GET', true],
    'replace' => ['PUT', true],
    'patch' => ['PATCH', true],
    'destroy' => ['DELETE', true],
]);

test('WorkspaceMembership routes return 404 for a resource under the wrong parent', function (): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    extract($fixture);
    $other = createWorkspaceMembershipControllerFixture();
    $attributes = $other['resource']->fresh()->getAttributes();

    $response = $this->withBasicAuth($actor->user->email, 'password')
        ->getJson($url.'/'.$other['resource']->id);

    $response->assertNotFound();
    expect($other['resource']->fresh()->getAttributes())->toBe($attributes);
});

test('WorkspaceMembership list includes only resources from the requested parent', function (): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    extract($fixture);
    $other = createWorkspaceMembershipControllerFixture();

    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($url);

    $response->assertOk()->assertJsonPath('data.0.id', $actor->id);
    expect(array_column($response->json('data'), 'id'))->toEqualCanonicalizing([$actor->id, $resource->id]);
});

test('WorkspaceMembership details expose the resource and timestamps', function (): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($url.'/'.$resource->id);

    $response->assertOk()->assertJsonPath('data.id', $resource->id);
    expect(array_keys($response->json('data')))->toEqualCanonicalizing(['id', 'workspace_id', 'user_id', 'created_at', 'updated_at']);
});

test('WorkspaceMembership creation uses bound parents and ignores server controlled fields', function (): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    extract($fixture);
    $payload = ['user_id' => UserFactory::new()->create()->id];
    $payload['workspace_id'] = '019a0000-0000-7000-8000-000000000099';
    $payload['created_by'] = UserFactory::new()->create()->id;

    $response = $this->withBasicAuth($actor->user->email, 'password')->postJson($url, $payload);

    $response->assertCreated()->assertJsonPath('data.workspace_id', $workspace->id);
    $this->assertDatabaseHas($resource->getTable(), ['id' => $response->json('data.id'), 'workspace_id' => $workspace->id]);

});

test('WorkspaceMembership deletion removes the requested record and preserves the parent', function (): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->deleteJson($url.'/'.$resource->id);

    $response->assertNoContent();
    $this->assertModelMissing($resource);
    $this->assertModelExists($workspace);
});

test('WorkspaceMembership PUT persists editable fields and preserves parents', function (): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    extract($fixture);
    $payload = ['user_id' => UserFactory::new()->create()->id];
    $payload['workspace_id'] = '019a0000-0000-7000-8000-000000000099';
    $payload['created_by'] = UserFactory::new()->create()->id;

    $response = $this->withBasicAuth($actor->user->email, 'password')->json('PUT', $url.'/'.$resource->id, $payload);

    $response->assertOk()->assertJsonPath('data.workspace_id', $workspace->id);
    $fresh = $resource->fresh();
    foreach (array_intersect_key($payload, array_flip(['user_id'])) as $key => $expected) {
        $response->assertJsonPath('data.'.$key, $expected);
        $actual = $fresh->{$key};
        expect($actual instanceof BackedEnum ? $actual->value : $actual)->toBe($expected);
    }
});

test('WorkspaceMembership PATCH persists editable fields and preserves parents', function (): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    extract($fixture);
    $payload = ['user_id' => UserFactory::new()->create()->id];
    $payload['workspace_id'] = '019a0000-0000-7000-8000-000000000099';
    $payload['created_by'] = UserFactory::new()->create()->id;

    $response = $this->withBasicAuth($actor->user->email, 'password')->json('PATCH', $url.'/'.$resource->id, $payload);

    $response->assertOk()->assertJsonPath('data.workspace_id', $workspace->id);
    $fresh = $resource->fresh();
    foreach (array_intersect_key($payload, array_flip(['user_id'])) as $key => $expected) {
        $response->assertJsonPath('data.'.$key, $expected);
        $actual = $fresh->{$key};
        expect($actual instanceof BackedEnum ? $actual->value : $actual)->toBe($expected);
    }
});

test('WorkspaceMembership rejects empty updates with 422 and preserves data', function (): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    extract($fixture);
    $attributes = $resource->fresh()->getAttributes();

    $response = $this->withBasicAuth($actor->user->email, 'password')->patchJson($url.'/'.$resource->id, []);

    $response->assertUnprocessable();
    expect($resource->fresh()->getAttributes())->toBe($attributes);
});

test('WorkspaceMembership writes ignore query parameters and form data', function (): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    extract($fixture);
    $payload = ['user_id' => UserFactory::new()->create()->id];
    $attributes = $resource->fresh()->getAttributes();

    $response = $this->withBasicAuth($actor->user->email, 'password')
        ->patchJson($url.'/'.$resource->id.'?'.http_build_query($payload), []);
    $formResponse = $this->patch($url.'/'.$resource->id, $payload, ['Accept' => 'application/json', 'Content-Type' => 'application/x-www-form-urlencoded']);

    $response->assertUnprocessable();
    $formResponse->assertUnprocessable();
    expect($resource->fresh()->getAttributes())->toBe($attributes);
});

test('memberships reject a duplicate user in the workspace', function (): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->postJson($url, ['user_id' => $actor->user_id]);

    $response->assertUnprocessable()->assertInvalid(['user_id']);
    $this->assertDatabaseCount('workspace_memberships', 2);
});

test('memberships reject users that do not exist', function (): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->patchJson($url.'/'.$resource->id, ['user_id' => 2147483647]);

    $response->assertUnprocessable()->assertInvalid(['user_id']);
    $this->assertDatabaseHas('workspace_memberships', ['id' => $resource->id, 'user_id' => $resource->user_id]);
});

test('WorkspaceMembership creation returns 422 for missing JSON fields', function (): void {
    $fixture = createWorkspaceMembershipControllerFixture();
    $resource = $fixture['resource'];
    $count = $resource->newQuery()->count();

    $response = $this->withBasicAuth($fixture['actor']->user->email, 'password')->postJson($fixture['url'], []);

    $response->assertUnprocessable()->assertInvalid(['user_id']);
    $this->assertDatabaseCount($resource->getTable(), $count);
});
