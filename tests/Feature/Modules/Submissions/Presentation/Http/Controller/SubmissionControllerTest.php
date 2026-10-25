<?php

use Database\Factories\FormFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\SubmissionFactory;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/** @return array<string, mixed> */
function createSubmissionControllerFixture(): array
{
    $actor = WorkspaceMembershipFactory::new()->create();
    $workspace = $actor->workspace;
    $project = ProjectFactory::new()->for($workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    $resource = SubmissionFactory::new()->for($form)->create();
    $url = '/api/v1/workspaces/'.$workspace->id.'/projects/'.$project->id.'/forms/'.$form->id.'/submissions';

    return compact('actor', 'workspace', 'project', 'form', 'resource', 'url');
}

test('Submission endpoints return 401 without Basic credentials', function (string $method, bool $detail): void {
    $fixture = createSubmissionControllerFixture();
    extract($fixture);
    $attributes = $resource->fresh()->getAttributes();

    $response = $this->json($method, $url.($detail ? '/'.$resource->id : ''), []);

    $response->assertUnauthorized()->assertHeader('WWW-Authenticate', 'Basic');
    expect($resource->fresh()->getAttributes())->toBe($attributes);
})->with([
    'index' => ['GET', false],
    'store' => ['POST', false],
    'show' => ['GET', true],
    'destroy' => ['DELETE', true],
]);

test('Submission endpoints return 403 for non-members and preserve data', function (string $method, bool $detail): void {
    $fixture = createSubmissionControllerFixture();
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
    'destroy' => ['DELETE', true],
]);

test('Submission routes return 404 for a resource under the wrong parent', function (): void {
    $fixture = createSubmissionControllerFixture();
    extract($fixture);
    $other = createSubmissionControllerFixture();
    $attributes = $other['resource']->fresh()->getAttributes();

    $response = $this->withBasicAuth($actor->user->email, 'password')
        ->getJson($url.'/'.$other['resource']->id);

    $response->assertNotFound();
    expect($other['resource']->fresh()->getAttributes())->toBe($attributes);
});

test('Submission routes reject a wrong workspace earlier in the ancestor chain', function (): void {
    $fixture = createSubmissionControllerFixture();
    extract($fixture);
    $otherMembership = WorkspaceMembershipFactory::new()->for($actor->user)->create();
    $wrongUrl = str_replace($workspace->id, $otherMembership->workspace_id, $url);

    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($wrongUrl);

    $response->assertNotFound();
});

test('Submission list includes only resources from the requested parent', function (): void {
    $fixture = createSubmissionControllerFixture();
    extract($fixture);
    $other = createSubmissionControllerFixture();

    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($url);

    $response->assertOk()->assertJsonPath('data.0.id', $resource->id);
    expect(array_column($response->json('data'), 'id'))->toEqualCanonicalizing([$resource->id]);
});

test('Submission details expose the resource and timestamps', function (): void {
    $fixture = createSubmissionControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->getJson($url.'/'.$resource->id);

    $response->assertOk()->assertJsonPath('data.id', $resource->id);
    expect(array_keys($response->json('data')))->toEqualCanonicalizing(['id', 'form_id', 'created_at', 'updated_at']);
});

test('Submission creation uses bound parents and ignores server controlled fields', function (): void {
    $fixture = createSubmissionControllerFixture();
    extract($fixture);
    $payload = [];
    $payload['form_id'] = '019a0000-0000-7000-8000-000000000099';
    $payload['created_by'] = UserFactory::new()->create()->id;

    $response = $this->withBasicAuth($actor->user->email, 'password')->postJson($url, $payload);

    $response->assertCreated()->assertJsonPath('data.form_id', $form->id);
    $this->assertDatabaseHas($resource->getTable(), ['id' => $response->json('data.id'), 'form_id' => $form->id]);

});

test('Submission deletion removes the requested record and preserves the parent', function (): void {
    $fixture = createSubmissionControllerFixture();
    extract($fixture);
    $response = $this->withBasicAuth($actor->user->email, 'password')->deleteJson($url.'/'.$resource->id);

    $response->assertNoContent();
    $this->assertModelMissing($resource);
    $this->assertModelExists($form);
});
