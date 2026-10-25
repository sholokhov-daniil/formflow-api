<?php

use Database\Factories\FormFactory;
use Database\Factories\FormFieldFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\SubmissionFactory;
use Database\Factories\SubmissionValueFactory;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/** @return array<string, mixed> */
function createSubmissionValueControllerFixture(): array
{
    $actor = WorkspaceMembershipFactory::new()->create();
    $workspace = $actor->workspace;
    $project = ProjectFactory::new()->for($workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    $submission = SubmissionFactory::new()->for($form)->create();
    $resource = SubmissionValueFactory::new()->forSubmission($submission)->create(['value' => 'Original']);
    $url = '/api/v1/workspaces/'.$workspace->id.'/projects/'.$project->id.'/forms/'.$form->id.'/submissions/'.$submission->id.'/values';

    return compact('actor', 'workspace', 'project', 'form', 'submission', 'resource', 'url');
}

test('SubmissionValue endpoints return 401 without Basic credentials', function (string $method, bool $detail): void {
    $fixture = createSubmissionValueControllerFixture();
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

test('SubmissionValue endpoints return 403 for non-members and preserve data', function (string $method, bool $detail): void {
    $fixture = createSubmissionValueControllerFixture();
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

test('SubmissionValue routes return 404 for a resource under the wrong parent', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    extract($fixture);
    $other = createSubmissionValueControllerFixture();
    $attributes = $other['resource']->fresh()->getAttributes();

    $response = $this->withBasicAuth($actor->user->email, 'password')
        ->getJson($url.'/'.$other['resource']->id);

    $response->assertNotFound();
    expect($other['resource']->fresh()->getAttributes())->toBe($attributes);
});

test('SubmissionValue routes reject a wrong workspace earlier in the ancestor chain', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    extract($fixture);
    $otherMembership = WorkspaceMembershipFactory::new()->for($actor->user)->create();
    $wrongUrl = str_replace($workspace->id, $otherMembership->workspace_id, $url);

    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($wrongUrl);

    $response->assertNotFound();
});

test('SubmissionValue list includes only resources from the requested parent', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    extract($fixture);
    $other = createSubmissionValueControllerFixture();

    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($url);

    $response->assertOk()->assertJsonPath('data.0.id', $resource->id);
    expect(array_column($response->json('data'), 'id'))->toEqualCanonicalizing([$resource->id]);
});

test('SubmissionValue details expose the resource and timestamps', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($url.'/'.$resource->id);

    $response->assertOk()->assertJsonPath('data.id', $resource->id);
    expect(array_keys($response->json('data')))->toEqualCanonicalizing(['id', 'submission_id', 'form_id', 'form_field_id', 'value', 'created_at', 'updated_at']);
});

test('SubmissionValue creation uses bound parents and ignores server controlled fields', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    extract($fixture);
    $payload = ['form_field_id' => FormFieldFactory::new()->for($form)->create()->id, 'value' => false];
    $payload['submission_id'] = '019a0000-0000-7000-8000-000000000099';
    $payload['created_by'] = UserFactory::new()->create()->id;
    $payload['form_id'] = '019a0000-0000-7000-8000-000000000099';

    $response = $this->withBasicAuth($actor->user->email, 'password')->postJson($url, $payload);

    $response->assertCreated()->assertJsonPath('data.submission_id', $submission->id);
    $this->assertDatabaseHas($resource->getTable(), ['id' => $response->json('data.id'), 'submission_id' => $submission->id]);
    $response->assertJsonPath('data.form_id', $form->id)->assertJsonPath('data.value', false);
});

test('SubmissionValue deletion removes the requested record and preserves the parent', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->deleteJson($url.'/'.$resource->id);

    $response->assertNoContent();
    $this->assertModelMissing($resource);
    $this->assertModelExists($submission);
});

test('SubmissionValue PUT persists editable fields and preserves parents', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    extract($fixture);
    $payload = ['value' => null];
    $payload['submission_id'] = '019a0000-0000-7000-8000-000000000099';
    $payload['created_by'] = UserFactory::new()->create()->id;

    $response = $this->withBasicAuth($actor->user->email, 'password')->json('PUT', $url.'/'.$resource->id, $payload);

    $response->assertOk()->assertJsonPath('data.submission_id', $submission->id);
    $fresh = $resource->fresh();
    foreach (array_intersect_key($payload, array_flip(['value'])) as $key => $expected) {
        $response->assertJsonPath('data.'.$key, $expected);
        $actual = $fresh->{$key};
        expect($actual instanceof BackedEnum ? $actual->value : $actual)->toBe($expected);
    }
});

test('SubmissionValue PATCH persists editable fields and preserves parents', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    extract($fixture);
    $payload = ['value' => null];
    $payload['submission_id'] = '019a0000-0000-7000-8000-000000000099';
    $payload['created_by'] = UserFactory::new()->create()->id;

    $response = $this->withBasicAuth($actor->user->email, 'password')->json('PATCH', $url.'/'.$resource->id, $payload);

    $response->assertOk()->assertJsonPath('data.submission_id', $submission->id);
    $fresh = $resource->fresh();
    foreach (array_intersect_key($payload, array_flip(['value'])) as $key => $expected) {
        $response->assertJsonPath('data.'.$key, $expected);
        $actual = $fresh->{$key};
        expect($actual instanceof BackedEnum ? $actual->value : $actual)->toBe($expected);
    }
});

test('SubmissionValue rejects empty updates with 422 and preserves data', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    extract($fixture);
    $attributes = $resource->fresh()->getAttributes();

    $response = $this->withBasicAuth($actor->user->email, 'password')->patchJson($url.'/'.$resource->id, []);

    $response->assertUnprocessable();
    expect($resource->fresh()->getAttributes())->toBe($attributes);
});

test('SubmissionValue writes ignore query parameters and form data', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    extract($fixture);
    $payload = ['value' => null];
    $attributes = $resource->fresh()->getAttributes();

    $response = $this->withBasicAuth($actor->user->email, 'password')
        ->patchJson($url.'/'.$resource->id.'?'.http_build_query($payload), []);
    $formResponse = $this->patch($url.'/'.$resource->id, $payload, ['Accept' => 'application/json', 'Content-Type' => 'application/x-www-form-urlencoded']);

    $response->assertUnprocessable();
    $formResponse->assertUnprocessable();
    expect($resource->fresh()->getAttributes())->toBe($attributes);
});

test('values reject a field belonging to a different form', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    extract($fixture);
    $field = FormFieldFactory::new()->create();

    $response = $this->withBasicAuth($actor->user->email, 'password')->postJson($url, ['form_field_id' => $field->id, 'value' => 'Answer']);

    $response->assertUnprocessable()->assertInvalid(['form_field_id']);
    $this->assertDatabaseCount('submission_values', 1);
});

test('values reject duplicate answers for the same field', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->postJson($url, ['form_field_id' => $resource->form_field_id, 'value' => 'Answer']);

    $response->assertUnprocessable()->assertInvalid(['form_field_id']);
    $this->assertDatabaseCount('submission_values', 1);
});

test('value updates cannot move the answer to another field or form', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    extract($fixture);
    $field = FormFieldFactory::new()->create();

    $response = $this->withBasicAuth($actor->user->email, 'password')->patchJson($url.'/'.$resource->id, ['value' => 0, 'form_field_id' => $field->id, 'form_id' => $field->form_id]);

    $response->assertOk()->assertJsonPath('data.form_field_id', $resource->form_field_id)->assertJsonPath('data.value', 0);
    $this->assertDatabaseHas('submission_values', ['id' => $resource->id, 'form_field_id' => $resource->form_field_id, 'form_id' => $form->id]);
});

test('SubmissionValue creation returns 422 for missing JSON fields', function (): void {
    $fixture = createSubmissionValueControllerFixture();
    $resource = $fixture['resource'];
    $count = $resource->newQuery()->count();

    $response = $this->withBasicAuth($fixture['actor']->user->email, 'password')->postJson($fixture['url'], []);

    $response->assertUnprocessable()->assertInvalid(['form_field_id', 'value']);
    $this->assertDatabaseCount($resource->getTable(), $count);
});

test('values preserve JSON types during updates', function (mixed $answer): void {
    $fixture = createSubmissionValueControllerFixture();

    $response = $this->withBasicAuth($fixture['actor']->user->email, 'password')
        ->patchJson($fixture['url'].'/'.$fixture['resource']->id, ['value' => $answer]);

    $response->assertOk()->assertJsonPath('data.value', $answer);
    expect($fixture['resource']->fresh()->value)->toBe($answer);
})->with([
    'null' => [null],
    'false' => [false],
    'zero' => [0],
    'string' => ['Answer'],
    'list' => [['first', 'second']],
    'object' => [['selected' => true]],
]);
