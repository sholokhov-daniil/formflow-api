<?php

use Database\Factories\FormFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/** @return array<string, mixed> */
function createFormControllerFixture(): array
{
    $actor = WorkspaceMembershipFactory::new()->create();
    $workspace = $actor->workspace;
    $project = ProjectFactory::new()->for($workspace)->create();
    $resource = FormFactory::new()->for($project)->create(['name' => 'Original', 'active' => true, 'settings' => ['original' => true]]);
    $url = '/api/v1/workspaces/'.$workspace->id.'/projects/'.$project->id.'/forms';

    return compact('actor', 'workspace', 'project', 'resource', 'url');
}

test('Form endpoints return 401 without Basic credentials', function (string $method, bool $detail): void {
    $fixture = createFormControllerFixture();
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

test('Form endpoints return 403 for non-members and preserve data', function (string $method, bool $detail): void {
    $fixture = createFormControllerFixture();
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

test('Form routes return 404 for a resource under the wrong parent', function (): void {
    $fixture = createFormControllerFixture();
    extract($fixture);
    $other = createFormControllerFixture();
    $attributes = $other['resource']->fresh()->getAttributes();

    $response = $this->withBasicAuth($actor->user->email, 'password')
        ->getJson($url.'/'.$other['resource']->id);

    $response->assertNotFound();
    expect($other['resource']->fresh()->getAttributes())->toBe($attributes);
});

test('Form routes reject a wrong workspace earlier in the ancestor chain', function (): void {
    $fixture = createFormControllerFixture();
    extract($fixture);
    $otherMembership = WorkspaceMembershipFactory::new()->for($actor->user)->create();
    $wrongUrl = str_replace($workspace->id, $otherMembership->workspace_id, $url);

    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($wrongUrl);

    $response->assertNotFound();
});

test('Form list includes only resources from the requested parent', function (): void {
    $fixture = createFormControllerFixture();
    extract($fixture);
    $other = createFormControllerFixture();

    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($url);

    $response->assertOk()->assertJsonPath('data.0.id', $resource->id);
    expect(array_column($response->json('data'), 'id'))->toEqualCanonicalizing([$resource->id]);
});

test('Form details expose the resource and timestamps', function (): void {
    $fixture = createFormControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($url.'/'.$resource->id);

    $response->assertOk()->assertJsonPath('data.id', $resource->id);
    expect(array_keys($response->json('data')))->toEqualCanonicalizing(['id', 'project_id', 'name', 'settings', 'active', 'created_by', 'created_at', 'updated_at']);
});

test('Form creation uses bound parents and ignores server controlled fields', function (): void {
    $fixture = createFormControllerFixture();
    extract($fixture);
    $payload = ['name' => 'New form', 'active' => false, 'settings' => ['theme' => 'blue']];
    $payload['project_id'] = '019a0000-0000-7000-8000-000000000099';
    $payload['created_by'] = UserFactory::new()->create()->id;

    $response = $this->withBasicAuth($actor->user->email, 'password')->postJson($url, $payload);

    $response->assertCreated()->assertJsonPath('data.project_id', $project->id);
    $this->assertDatabaseHas($resource->getTable(), ['id' => $response->json('data.id'), 'project_id' => $project->id]);
    $response->assertJsonPath('data.created_by', $actor->user_id)->assertJsonPath('data.active', false);
});

test('Form deletion removes the requested record and preserves the parent', function (): void {
    $fixture = createFormControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->deleteJson($url.'/'.$resource->id);

    $response->assertNoContent();
    $this->assertModelMissing($resource);
    $this->assertModelExists($project);
});

test('Form PUT persists editable fields and preserves parents', function (): void {
    $fixture = createFormControllerFixture();
    extract($fixture);
    $payload = ['name' => 'Changed', 'active' => false, 'settings' => null];
    $payload['project_id'] = '019a0000-0000-7000-8000-000000000099';
    $payload['created_by'] = UserFactory::new()->create()->id;

    $response = $this->withBasicAuth($actor->user->email, 'password')->json('PUT', $url.'/'.$resource->id, $payload);

    $response->assertOk()->assertJsonPath('data.project_id', $project->id);
    $fresh = $resource->fresh();
    foreach (array_intersect_key($payload, array_flip(['name', 'settings', 'active'])) as $key => $expected) {
        $response->assertJsonPath('data.'.$key, $expected);
        $actual = $fresh->{$key};
        expect($actual instanceof BackedEnum ? $actual->value : $actual)->toBe($expected);
    }
});

test('Form PATCH persists editable fields and preserves parents', function (): void {
    $fixture = createFormControllerFixture();
    extract($fixture);
    $payload = ['name' => 'Changed', 'active' => false, 'settings' => null];
    $payload['project_id'] = '019a0000-0000-7000-8000-000000000099';
    $payload['created_by'] = UserFactory::new()->create()->id;

    $response = $this->withBasicAuth($actor->user->email, 'password')->json('PATCH', $url.'/'.$resource->id, $payload);

    $response->assertOk()->assertJsonPath('data.project_id', $project->id);
    $fresh = $resource->fresh();
    foreach (array_intersect_key($payload, array_flip(['name', 'settings', 'active'])) as $key => $expected) {
        $response->assertJsonPath('data.'.$key, $expected);
        $actual = $fresh->{$key};
        expect($actual instanceof BackedEnum ? $actual->value : $actual)->toBe($expected);
    }
});

test('Form rejects empty updates with 422 and preserves data', function (): void {
    $fixture = createFormControllerFixture();
    extract($fixture);
    $attributes = $resource->fresh()->getAttributes();

    $response = $this->withBasicAuth($actor->user->email, 'password')->patchJson($url.'/'.$resource->id, []);

    $response->assertUnprocessable();
    expect($resource->fresh()->getAttributes())->toBe($attributes);
});

test('Form writes ignore query parameters and form data', function (): void {
    $fixture = createFormControllerFixture();
    extract($fixture);
    $payload = ['name' => 'Changed', 'active' => false, 'settings' => null];
    $attributes = $resource->fresh()->getAttributes();

    $response = $this->withBasicAuth($actor->user->email, 'password')
        ->patchJson($url.'/'.$resource->id.'?'.http_build_query($payload), []);
    $formResponse = $this->patch($url.'/'.$resource->id, $payload, ['Accept' => 'application/json', 'Content-Type' => 'application/x-www-form-urlencoded']);

    $response->assertUnprocessable();
    $formResponse->assertUnprocessable();
    expect($resource->fresh()->getAttributes())->toBe($attributes);
});

test('form PATCH preserves omitted settings and can clear settings explicitly', function (): void {
    $fixture = createFormControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->patchJson($url.'/'.$resource->id, ['active' => false]);

    $response->assertOk()->assertJsonPath('data.settings.original', true)->assertJsonPath('data.active', false);
    expect($resource->fresh()->settings)->toBe(['original' => true]);
});

test('form replacement requires name and active', function (): void {
    $fixture = createFormControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->putJson($url.'/'.$resource->id, ['name' => 'Changed']);

    $response->assertUnprocessable()->assertInvalid(['active']);
    expect($resource->fresh()->name)->toBe('Original');
});

test('Form creation returns 422 for missing JSON fields', function (): void {
    $fixture = createFormControllerFixture();
    $resource = $fixture['resource'];
    $count = $resource->newQuery()->count();

    $response = $this->withBasicAuth($fixture['actor']->user->email, 'password')->postJson($fixture['url'], []);

    $response->assertUnprocessable()->assertInvalid(['name', 'active']);
    $this->assertDatabaseCount($resource->getTable(), $count);
});

test('form settings reject scalar input without changing stored settings', function (): void {
    $fixture = createFormControllerFixture();

    $response = $this->withBasicAuth($fixture['actor']->user->email, 'password')
        ->patchJson($fixture['url'].'/'.$fixture['resource']->id, ['settings' => 'invalid']);

    $response->assertUnprocessable()->assertInvalid(['settings']);
    expect($fixture['resource']->fresh()->settings)->toBe(['original' => true]);
});
