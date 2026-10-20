<?php

use App\Modules\Identity\Infrastructure\Eloquent\User;
use Database\Factories\FormFactory;
use Database\Factories\FormFieldFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\SubmissionFactory;
use Database\Factories\SubmissionValueFactory;
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

test('workspace update with PUT replaces editable properties and returns details', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $workspace = WorkspaceFactory::new()->personal()->create(['name' => 'Personal space']);
    WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();

    $response = $this->withBasicAuth($user->email, 'correct-password')
        ->putJson('/api/v1/workspaces/'.$workspace->id, [
            'name' => 'Product team',
            'kind' => 'team',
        ]);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'kind',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJsonPath('data.id', $workspace->id)
        ->assertJsonPath('data.name', 'Product team')
        ->assertJsonPath('data.kind', 'team');

    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => 'Product team',
        'kind' => 'team',
    ]);
});

test('workspace update with PATCH changes only the supplied properties', function (array $payload, string $expectedName, string $expectedKind) {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $workspace = WorkspaceFactory::new()->create([
        'name' => 'Original workspace',
        'kind' => 'team',
    ]);
    WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();

    $response = $this->withBasicAuth($user->email, 'correct-password')
        ->patchJson('/api/v1/workspaces/'.$workspace->id, $payload);

    $response
        ->assertOk()
        ->assertJsonPath('data.name', $expectedName)
        ->assertJsonPath('data.kind', $expectedKind);

    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => $expectedName,
        'kind' => $expectedKind,
    ]);
})->with([
    'name only' => [['name' => 'Renamed workspace'], 'Renamed workspace', 'team'],
    'kind only' => [['kind' => 'personal'], 'Original workspace', 'personal'],
    'both properties' => [['name' => 'Personal space', 'kind' => 'personal'], 'Personal space', 'personal'],
    'unrecognized properties are ignored' => [
        ['name' => 'Renamed workspace', 'id' => '019a0000-0000-7000-8000-000000000099'],
        'Renamed workspace',
        'team',
    ],
]);

test('workspace update invalidates the cached workspace list', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $workspace = WorkspaceFactory::new()->create(['name' => 'Original workspace']);
    WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();

    $this->withBasicAuth($user->email, 'correct-password')
        ->getJson('/api/v1/workspaces')
        ->assertJsonPath('data.0.name', 'Original workspace');

    $this->withBasicAuth($user->email, 'correct-password')
        ->patchJson('/api/v1/workspaces/'.$workspace->id, ['name' => 'Renamed workspace'])
        ->assertOk();

    $this->withBasicAuth($user->email, 'correct-password')
        ->getJson('/api/v1/workspaces')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Renamed workspace');
});

test('workspace update with PUT returns 422 when a required property is missing', function (array $payload, array $invalidFields) {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $workspace = WorkspaceFactory::new()->create([
        'name' => 'Original workspace',
        'kind' => 'team',
    ]);
    WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();

    $response = $this->withBasicAuth($user->email, 'correct-password')
        ->putJson('/api/v1/workspaces/'.$workspace->id, $payload);

    $response->assertUnprocessable()->assertInvalid($invalidFields);
    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => 'Original workspace',
        'kind' => 'team',
    ]);
})->with([
    'both properties are missing' => [[], ['name', 'kind']],
    'name is missing' => [['kind' => 'personal'], ['name']],
    'kind is missing' => [['name' => 'Renamed workspace'], ['kind']],
]);

test('workspace update with PATCH returns 422 for invalid properties', function (array $payload, array $invalidFields) {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $workspace = WorkspaceFactory::new()->create([
        'name' => 'Original workspace',
        'kind' => 'team',
    ]);
    WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();

    $response = $this->withBasicAuth($user->email, 'correct-password')
        ->patchJson('/api/v1/workspaces/'.$workspace->id, $payload);

    $response->assertUnprocessable()->assertInvalid($invalidFields);
    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => 'Original workspace',
        'kind' => 'team',
    ]);
})->with([
    'name is not a string' => [['name' => 123], ['name']],
    'name is too long' => [['name' => str_repeat('a', 256)], ['name']],
    'name is empty' => [['name' => ''], ['name']],
    'name is null' => [['name' => null], ['name']],
    'kind is unsupported' => [['kind' => 'enterprise'], ['kind']],
    'kind is null' => [['kind' => null], ['kind']],
]);

test('workspace update with an empty PATCH body returns 422 and leaves the workspace unchanged', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $workspace = WorkspaceFactory::new()->create([
        'name' => 'Original workspace',
        'kind' => 'team',
    ]);
    WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();

    $response = $this->withBasicAuth($user->email, 'correct-password')
        ->patchJson('/api/v1/workspaces/'.$workspace->id, []);

    $response
        ->assertUnprocessable()
        ->assertInvalid(['name' => 'Provide at least one field to update.']);
    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => 'Original workspace',
        'kind' => 'team',
    ]);
});

test('workspace update ignores query parameters when fields are not in the JSON body', function (string $method, array $invalidFields) {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $workspace = WorkspaceFactory::new()->create([
        'name' => 'Original workspace',
        'kind' => 'team',
    ]);
    WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();

    $response = $this->withBasicAuth($user->email, 'correct-password')->json(
        $method,
        '/api/v1/workspaces/'.$workspace->id.'?name=Query+name&kind=personal',
        [],
    );

    $response->assertUnprocessable()->assertInvalid($invalidFields);
    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => 'Original workspace',
        'kind' => 'team',
    ]);
})->with([
    'PATCH requires at least one body property' => ['PATCH', ['name' => 'Provide at least one field to update.']],
    'PUT requires both body properties' => ['PUT', ['name', 'kind']],
]);

test('workspace update with form data returns 422 and leaves the workspace unchanged', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $workspace = WorkspaceFactory::new()->create(['name' => 'Original workspace']);
    WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();

    $response = $this->withBasicAuth($user->email, 'correct-password')->patch(
        '/api/v1/workspaces/'.$workspace->id,
        ['name' => 'Form data name'],
        ['Accept' => 'application/json', 'Content-Type' => 'application/x-www-form-urlencoded'],
    );

    $response
        ->assertUnprocessable()
        ->assertInvalid(['name' => 'Provide at least one field to update.']);
    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => 'Original workspace',
    ]);
});

test('workspace update returns 401 without Basic credentials', function () {
    $workspace = WorkspaceFactory::new()->create();

    $response = $this->patchJson('/api/v1/workspaces/'.$workspace->id, ['name' => 'Renamed workspace']);

    $response->assertUnauthorized()->assertExactJson(['message' => 'Invalid credentials.']);
    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => $workspace->name,
    ]);
});

test('workspace update returns 403 for a non-member and leaves the workspace unchanged', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $workspace = WorkspaceFactory::new()->create(['name' => 'Original workspace']);
    WorkspaceMembershipFactory::new()->for($workspace)->create();

    $response = $this->withBasicAuth($user->email, 'correct-password')
        ->patchJson('/api/v1/workspaces/'.$workspace->id, ['name' => 'Renamed workspace']);

    $response->assertForbidden()->assertExactJson(['message' => 'This action is unauthorized.']);
    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => 'Original workspace',
    ]);
});

test('workspace update returns 404 for an unknown workspace UUID', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);

    $response = $this->withBasicAuth($user->email, 'correct-password')
        ->patchJson('/api/v1/workspaces/019a0000-0000-7000-8000-000000000099', ['name' => 'Renamed workspace']);

    $response->assertNotFound()->assertExactJson(['message' => 'The route or resource was not found.']);
});

test('workspace deletion returns 204 and cascades through workspace data', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $anotherMember = UserFactory::new()->create();
    $workspace = WorkspaceFactory::new()->create();
    $membership = WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();
    $anotherMembership = WorkspaceMembershipFactory::new()->for($anotherMember)->for($workspace)->create();
    $project = ProjectFactory::new()->create(['workspace_id' => $workspace->id]);
    $form = FormFactory::new()->create([
        'project_id' => $project->id,
        'created_by' => $user->id,
    ]);
    $field = FormFieldFactory::new()->create(['form_id' => $form->id]);
    $submission = SubmissionFactory::new()->create(['form_id' => $form->id]);
    $submissionValue = SubmissionValueFactory::new()
        ->forSubmission($submission)
        ->create(['form_field_id' => $field->id]);
    $this->withBasicAuth($user->email, 'correct-password')
        ->getJson('/api/v1/workspaces')
        ->assertJsonPath('data.0.id', $workspace->id);

    $response = $this->deleteJson('/api/v1/workspaces/'.$workspace->id);

    $response->assertNoContent();
    $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
    $this->assertDatabaseMissing('workspace_memberships', ['id' => $membership->id]);
    $this->assertDatabaseMissing('workspace_memberships', ['id' => $anotherMembership->id]);
    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    $this->assertDatabaseMissing('forms', ['id' => $form->id]);
    $this->assertDatabaseMissing('form_fields', ['id' => $field->id]);
    $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
    $this->assertDatabaseMissing('submission_values', ['id' => $submissionValue->id]);
    $this->assertModelExists($user);
    $this->assertModelExists($anotherMember);

    $this->getJson('/api/v1/workspaces')->assertOk()->assertExactJson(['data' => []]);
});

test('workspace deletion returns 401 without Basic credentials', function () {
    $workspace = WorkspaceFactory::new()->create();

    $response = $this->deleteJson('/api/v1/workspaces/'.$workspace->id);

    $response->assertUnauthorized()->assertExactJson(['message' => 'Invalid credentials.']);
    $this->assertModelExists($workspace);
});

test('workspace deletion returns 403 for a non-member and leaves the workspace intact', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $workspace = WorkspaceFactory::new()->create();
    WorkspaceMembershipFactory::new()->for($workspace)->create();

    $response = $this->withBasicAuth($user->email, 'correct-password')
        ->deleteJson('/api/v1/workspaces/'.$workspace->id);

    $response->assertForbidden()->assertExactJson(['message' => 'This action is unauthorized.']);
    $this->assertModelExists($workspace);
});

test('workspace deletion returns 404 for an unknown workspace UUID', function () {
    $user = UserFactory::new()->create(['password' => 'correct-password']);

    $response = $this->withBasicAuth($user->email, 'correct-password')
        ->deleteJson('/api/v1/workspaces/019a0000-0000-7000-8000-000000000099');

    $response->assertNotFound()->assertExactJson(['message' => 'The route or resource was not found.']);
});
