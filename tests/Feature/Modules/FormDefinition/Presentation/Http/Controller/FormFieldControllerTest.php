<?php

use Database\Factories\FormFactory;
use Database\Factories\FormFieldFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/** @return array<string, mixed> */
function createFormFieldControllerFixture(): array
{
    $actor = WorkspaceMembershipFactory::new()->create();
    $workspace = $actor->workspace;
    $project = ProjectFactory::new()->for($workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    $resource = FormFieldFactory::new()->for($form)->create(['code' => 'original', 'label' => 'Original', 'position' => 1, 'active' => true]);
    $url = '/api/v1/workspaces/'.$workspace->id.'/projects/'.$project->id.'/forms/'.$form->id.'/fields';

    return compact('actor', 'workspace', 'project', 'form', 'resource', 'url');
}

test('FormField endpoints return 401 without Basic credentials', function (string $method, bool $detail): void {
    $fixture = createFormFieldControllerFixture();
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

test('FormField endpoints return 403 for non-members and preserve data', function (string $method, bool $detail): void {
    $fixture = createFormFieldControllerFixture();
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

test('FormField routes return 404 for a resource under the wrong parent', function (): void {
    $fixture = createFormFieldControllerFixture();
    extract($fixture);
    $other = createFormFieldControllerFixture();
    $attributes = $other['resource']->fresh()->getAttributes();

    $response = $this->withBasicAuth($actor->user->email, 'password')
        ->getJson($url.'/'.$other['resource']->id);

    $response->assertNotFound();
    expect($other['resource']->fresh()->getAttributes())->toBe($attributes);
});

test('FormField routes reject a wrong workspace earlier in the ancestor chain', function (): void {
    $fixture = createFormFieldControllerFixture();
    extract($fixture);
    $otherMembership = WorkspaceMembershipFactory::new()->for($actor->user)->create();
    $wrongUrl = str_replace($workspace->id, $otherMembership->workspace_id, $url);

    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($wrongUrl);

    $response->assertNotFound();
});

test('FormField list includes only resources from the requested parent', function (): void {
    $fixture = createFormFieldControllerFixture();
    extract($fixture);
    $other = createFormFieldControllerFixture();

    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($url);

    $response->assertOk()->assertJsonPath('data.0.id', $resource->id);
    expect(array_column($response->json('data'), 'id'))->toEqualCanonicalizing([$resource->id]);
});

test('FormField details expose the resource and timestamps', function (): void {
    $fixture = createFormFieldControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($url.'/'.$resource->id);

    $response->assertOk()->assertJsonPath('data.id', $resource->id);
    expect(array_keys($response->json('data')))->toEqualCanonicalizing(['id', 'form_id', 'code', 'label', 'position', 'type', 'active', 'required', 'created_at', 'updated_at']);
});

test('FormField creation uses bound parents and ignores server controlled fields', function (): void {
    $fixture = createFormFieldControllerFixture();
    extract($fixture);
    $payload = ['code' => 'new_code', 'label' => 'New label', 'position' => 0, 'type' => 'boolean', 'active' => false, 'required' => true];
    $payload['form_id'] = '019a0000-0000-7000-8000-000000000099';
    $payload['created_by'] = UserFactory::new()->create()->id;

    $response = $this->withBasicAuth($actor->user->email, 'password')->postJson($url, $payload);

    $response->assertCreated()->assertJsonPath('data.form_id', $form->id);
    $this->assertDatabaseHas($resource->getTable(), ['id' => $response->json('data.id'), 'form_id' => $form->id]);

});

test('FormField deletion removes the requested record and preserves the parent', function (): void {
    $fixture = createFormFieldControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->deleteJson($url.'/'.$resource->id);

    $response->assertNoContent();
    $this->assertModelMissing($resource);
    $this->assertModelExists($form);
});

test('FormField PUT persists editable fields and preserves parents', function (): void {
    $fixture = createFormFieldControllerFixture();
    extract($fixture);
    $payload = ['code' => 'changed', 'label' => 'Changed', 'position' => 0, 'type' => 'email', 'active' => false, 'required' => false];
    $payload['form_id'] = '019a0000-0000-7000-8000-000000000099';
    $payload['created_by'] = UserFactory::new()->create()->id;

    $response = $this->withBasicAuth($actor->user->email, 'password')->json('PUT', $url.'/'.$resource->id, $payload);

    $response->assertOk()->assertJsonPath('data.form_id', $form->id);
    $fresh = $resource->fresh();
    foreach (array_intersect_key($payload, array_flip(['code', 'label', 'position', 'type', 'active', 'required'])) as $key => $expected) {
        $response->assertJsonPath('data.'.$key, $expected);
        $actual = $fresh->{$key};
        expect($actual instanceof BackedEnum ? $actual->value : $actual)->toBe($expected);
    }
});

test('FormField PATCH persists editable fields and preserves parents', function (): void {
    $fixture = createFormFieldControllerFixture();
    extract($fixture);
    $payload = ['code' => 'changed', 'label' => 'Changed', 'position' => 0, 'type' => 'email', 'active' => false, 'required' => false];
    $payload['form_id'] = '019a0000-0000-7000-8000-000000000099';
    $payload['created_by'] = UserFactory::new()->create()->id;

    $response = $this->withBasicAuth($actor->user->email, 'password')->json('PATCH', $url.'/'.$resource->id, $payload);

    $response->assertOk()->assertJsonPath('data.form_id', $form->id);
    $fresh = $resource->fresh();
    foreach (array_intersect_key($payload, array_flip(['code', 'label', 'position', 'type', 'active', 'required'])) as $key => $expected) {
        $response->assertJsonPath('data.'.$key, $expected);
        $actual = $fresh->{$key};
        expect($actual instanceof BackedEnum ? $actual->value : $actual)->toBe($expected);
    }
});

test('FormField rejects empty updates with 422 and preserves data', function (): void {
    $fixture = createFormFieldControllerFixture();
    extract($fixture);
    $attributes = $resource->fresh()->getAttributes();

    $response = $this->withBasicAuth($actor->user->email, 'password')->patchJson($url.'/'.$resource->id, []);

    $response->assertUnprocessable();
    expect($resource->fresh()->getAttributes())->toBe($attributes);
});

test('FormField writes ignore query parameters and form data', function (): void {
    $fixture = createFormFieldControllerFixture();
    extract($fixture);
    $payload = ['code' => 'changed', 'label' => 'Changed', 'position' => 0, 'type' => 'email', 'active' => false, 'required' => false];
    $attributes = $resource->fresh()->getAttributes();

    $response = $this->withBasicAuth($actor->user->email, 'password')
        ->patchJson($url.'/'.$resource->id.'?'.http_build_query($payload), []);
    $formResponse = $this->patch($url.'/'.$resource->id, $payload, ['Accept' => 'application/json', 'Content-Type' => 'application/x-www-form-urlencoded']);

    $response->assertUnprocessable();
    $formResponse->assertUnprocessable();
    expect($resource->fresh()->getAttributes())->toBe($attributes);
});

test('field codes are unique within a form', function (): void {
    $fixture = createFormFieldControllerFixture();
    extract($fixture);
    $payload = ['code' => 'new_code', 'label' => 'New label', 'position' => 0, 'type' => 'boolean', 'active' => false, 'required' => true];
    $payload['code'] = $resource->code;

    $response = $this->withBasicAuth($actor->user->email, 'password')->postJson($url, $payload);

    $response->assertUnprocessable()->assertInvalid(['code']);
    $this->assertDatabaseCount('form_fields', 1);
});

test('field validation rejects unknown types and out of range positions', function (): void {
    $fixture = createFormFieldControllerFixture();
    extract($fixture);
    $payload = ['code' => 'new_code', 'label' => 'New label', 'position' => 0, 'type' => 'boolean', 'active' => false, 'required' => true];
    $payload['type'] = 'unknown';
    $payload['position'] = 2147483648;
    $payload['active'] = 'yes';

    $response = $this->withBasicAuth($actor->user->email, 'password')->postJson($url, $payload);

    $response->assertUnprocessable()->assertInvalid(['type', 'position', 'active']);
    $this->assertDatabaseCount('form_fields', 1);
});

test('FormField creation returns 422 for missing JSON fields', function (): void {
    $fixture = createFormFieldControllerFixture();
    $resource = $fixture['resource'];
    $count = $resource->newQuery()->count();

    $response = $this->withBasicAuth($fixture['actor']->user->email, 'password')->postJson($fixture['url'], []);

    $response->assertUnprocessable()->assertInvalid(['code', 'label', 'position', 'type', 'active', 'required']);
    $this->assertDatabaseCount($resource->getTable(), $count);
});
