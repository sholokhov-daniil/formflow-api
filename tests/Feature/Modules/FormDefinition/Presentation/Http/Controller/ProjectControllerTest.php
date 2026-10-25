<?php

use Database\Factories\FormFactory;
use Database\Factories\FormFieldFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('project routes return 401 without Basic credentials', function (): void {
    $workspace = WorkspaceFactory::new()->create();

    $this->getJson('/api/v1/workspaces/'.$workspace->id.'/projects')->assertUnauthorized();
});

test('project routes return 403 when the user is not a member of the workspace', function (string $method, string $path): void {
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    $workspace = WorkspaceFactory::new()->create();
    $project = ProjectFactory::new()->for($workspace)->create();
    $path = str_replace(['{workspace}', '{project}'], [$workspace->id, $project->id], $path);

    $response = match ($method) {
        'GET' => $this->withBasicAuth($user->email, 'correct-password')->getJson($path),
        'POST' => $this->withBasicAuth($user->email, 'correct-password')->postJson($path, []),
        'PUT' => $this->withBasicAuth($user->email, 'correct-password')->putJson($path, []),
        'DELETE' => $this->withBasicAuth($user->email, 'correct-password')->deleteJson($path),
    };

    $response->assertForbidden();
})->with([
    'list' => ['GET', '/api/v1/workspaces/{workspace}/projects'],
    'create' => ['POST', '/api/v1/workspaces/{workspace}/projects'],
    'view' => ['GET', '/api/v1/workspaces/{workspace}/projects/{project}'],
    'update' => ['PUT', '/api/v1/workspaces/{workspace}/projects/{project}'],
    'delete' => ['DELETE', '/api/v1/workspaces/{workspace}/projects/{project}'],
]);

test('project routes return 404 when the project belongs to another workspace', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->create();

    $response = $this->withBasicAuth($membership->user->email, 'password')
        ->getJson('/api/v1/workspaces/'.$membership->workspace_id.'/projects/'.$project->id);

    $response->assertNotFound();
});

test('project endpoints return 401 without Basic credentials', function (string $method, bool $hasProject): void {
    $project = ProjectFactory::new()->create();
    $path = '/api/v1/workspaces/'.$project->workspace_id.'/projects'.($hasProject ? '/'.$project->id : '');

    $response = $this->json($method, $path, ['name' => 'Updated', 'description' => 'Updated description']);

    $response->assertUnauthorized()->assertHeader('WWW-Authenticate', 'Basic');
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => $project->name]);
    $this->assertDatabaseCount('projects', 1);
})->with([
    'create' => ['POST', false],
    'view' => ['GET', true],
    'replace' => ['PUT', true],
    'patch' => ['PATCH', true],
    'delete' => ['DELETE', true],
]);

test('the project list contains only projects in the requested workspace sorted by name and UUID', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $otherMembership = WorkspaceMembershipFactory::new()->for($membership->user)->create();
    ProjectFactory::new()->for($otherMembership->workspace)->create();
    ProjectFactory::new()->create();
    $last = ProjectFactory::new()->for($membership->workspace)->create(['name' => 'Zulu', 'description' => 'Last project']);
    $first = ProjectFactory::new()->for($membership->workspace)->create(['name' => 'Alpha', 'description' => 'First project']);

    $response = $this->withBasicAuth($membership->user->email, 'password')
        ->getJson('/api/v1/workspaces/'.$membership->workspace_id.'/projects');

    $response->assertOk()->assertExactJson([
        'data' => [
            ['id' => $first->id, 'workspace_id' => $membership->workspace_id, 'name' => 'Alpha', 'description' => 'First project'],
            ['id' => $last->id, 'workspace_id' => $membership->workspace_id, 'name' => 'Zulu', 'description' => 'Last project'],
        ],
    ]);
});

test('the project list returns an empty data array for a workspace without projects', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();

    $response = $this->withBasicAuth($membership->user->email, 'password')
        ->getJson('/api/v1/workspaces/'.$membership->workspace_id.'/projects');

    $response->assertOk()->assertExactJson(['data' => []]);
});

test('project creation uses the workspace from the route and returns details', function (): void {
    $this->travelTo(now()->startOfSecond());
    $membership = WorkspaceMembershipFactory::new()->create();
    $otherWorkspace = WorkspaceFactory::new()->create();

    $response = $this->withBasicAuth($membership->user->email, 'password')
        ->postJson('/api/v1/workspaces/'.$membership->workspace_id.'/projects', [
            'name' => 'Customer feedback',
            'description' => 'Feedback forms',
            'workspace_id' => $otherWorkspace->id,
            'id' => '019a0000-0000-7000-8000-000000000099',
        ]);

    $response->assertCreated()->assertExactJson([
        'data' => [
            'id' => $response->json('data.id'),
            'workspace_id' => $membership->workspace_id,
            'name' => 'Customer feedback',
            'description' => 'Feedback forms',
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ],
    ]);
    expect($response->json('data.id'))->toBeUuid()->not->toBe('019a0000-0000-7000-8000-000000000099');
    $this->assertDatabaseHas('projects', [
        'id' => $response->json('data.id'),
        'workspace_id' => $membership->workspace_id,
        'name' => 'Customer feedback',
        'description' => 'Feedback forms',
    ]);
    $this->assertDatabaseCount('projects', 1);
    $this->assertDatabaseCount('workspace_memberships', 1);
});

test('project creation returns 422 for invalid properties without creating a project', function (array $payload, array $errors): void {
    $membership = WorkspaceMembershipFactory::new()->create();

    $response = $this->withBasicAuth($membership->user->email, 'password')
        ->postJson('/api/v1/workspaces/'.$membership->workspace_id.'/projects', $payload);

    $response->assertUnprocessable()->assertInvalid($errors);
    $this->assertDatabaseCount('projects', 0);
})->with([
    'missing fields' => [[], ['name' => 'The name field is required.', 'description' => 'The description field is required.']],
    'invalid name type' => [['name' => 123, 'description' => 'Valid'], ['name' => 'The name field must be a string.']],
    'long name' => [['name' => str_repeat('a', 256), 'description' => 'Valid'], ['name' => 'The name field must not be greater than 255 characters.']],
    'invalid description type' => [['name' => 'Valid', 'description' => []], ['description' => 'The description field is required.']],
    'long description' => [['name' => 'Valid', 'description' => str_repeat('a', 256)], ['description' => 'The description field must not be greater than 255 characters.']],
    'null description' => [['name' => 'Valid', 'description' => null], ['description' => 'The description field is required.']],
]);

test('project details return the requested project and timestamps', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create([
        'name' => 'Customer feedback',
        'description' => 'Feedback forms',
        'created_at' => '2026-10-20 10:00:00',
        'updated_at' => '2026-10-21 12:30:00',
    ]);

    $response = $this->withBasicAuth($membership->user->email, 'password')
        ->getJson('/api/v1/workspaces/'.$membership->workspace_id.'/projects/'.$project->id);

    $response->assertOk()->assertExactJson([
        'data' => [
            'id' => $project->id,
            'workspace_id' => $membership->workspace_id,
            'name' => 'Customer feedback',
            'description' => 'Feedback forms',
            'created_at' => '2026-10-20T10:00:00.000000Z',
            'updated_at' => '2026-10-21T12:30:00.000000Z',
        ],
    ]);
});

test('project updates persist supplied editable properties and ignore ownership changes', function (string $method, array $payload, string $name, string $description): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create(['name' => 'Original', 'description' => 'Original description']);
    $otherWorkspace = WorkspaceFactory::new()->create();

    $response = $this->withBasicAuth($membership->user->email, 'password')->json(
        $method,
        '/api/v1/workspaces/'.$membership->workspace_id.'/projects/'.$project->id,
        [...$payload, 'workspace_id' => $otherWorkspace->id, 'id' => '019a0000-0000-7000-8000-000000000099'],
    );

    $response->assertOk()
        ->assertJsonStructure(['data' => ['id', 'workspace_id', 'name', 'description', 'created_at', 'updated_at']])
        ->assertJsonPath('data.id', $project->id)
        ->assertJsonPath('data.workspace_id', $membership->workspace_id)
        ->assertJsonPath('data.name', $name)
        ->assertJsonPath('data.description', $description);
    $this->assertDatabaseHas('projects', [
        'id' => $project->id, 'workspace_id' => $membership->workspace_id, 'name' => $name, 'description' => $description,
    ]);
})->with([
    'replace' => ['PUT', ['name' => 'Renamed', 'description' => 'New description'], 'Renamed', 'New description'],
    'patch name' => ['PATCH', ['name' => 'Renamed'], 'Renamed', 'Original description'],
    'patch description' => ['PATCH', ['description' => 'New description'], 'Original', 'New description'],
    'same name is allowed' => ['PATCH', ['name' => 'Original'], 'Original', 'Original description'],
]);

test('project updates return 422 for invalid properties and leave the project unchanged', function (string $method, array $payload, array $errors): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create(['name' => 'Original', 'description' => 'Original description']);

    $response = $this->withBasicAuth($membership->user->email, 'password')
        ->json($method, '/api/v1/workspaces/'.$membership->workspace_id.'/projects/'.$project->id, $payload);

    $response->assertUnprocessable()->assertInvalid($errors);
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Original', 'description' => 'Original description']);
})->with([
    'empty PUT' => ['PUT', [], ['name' => 'The name field is required.', 'description' => 'The description field is required.']],
    'PUT without description' => ['PUT', ['name' => 'Renamed'], ['description' => 'The description field is required.']],
    'PUT without name' => ['PUT', ['description' => 'New description'], ['name' => 'The name field is required.']],
    'empty PATCH' => ['PATCH', [], ['name' => 'Provide at least one field to update.']],
    'unknown PATCH properties' => ['PATCH', ['workspace_id' => 'ignored'], ['name' => 'Provide at least one field to update.']],
    'empty name' => ['PATCH', ['name' => '  '], ['name' => 'The name field is required.']],
    'null name' => ['PATCH', ['name' => null], ['name' => 'The name field is required.']],
    'invalid name type' => ['PATCH', ['name' => 123], ['name' => 'The name field must be a string.']],
    'long name' => ['PATCH', ['name' => str_repeat('a', 256)], ['name' => 'The name field must not be greater than 255 characters.']],
    'null description' => ['PATCH', ['description' => null], ['description' => 'The description field is required.']],
    'invalid description type' => ['PATCH', ['description' => 123], ['description' => 'The description field must be a string.']],
    'long description' => ['PATCH', ['description' => str_repeat('a', 256)], ['description' => 'The description field must not be greater than 255 characters.']],
]);

test('project names must be unique within their workspace', function (string $method): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    ProjectFactory::new()->for($membership->workspace)->create(['name' => 'Existing']);
    $project = ProjectFactory::new()->for($membership->workspace)->create(['name' => 'Original']);
    $path = '/api/v1/workspaces/'.$membership->workspace_id.'/projects'.($method === 'POST' ? '' : '/'.$project->id);

    $response = $this->withBasicAuth($membership->user->email, 'password')
        ->json($method, $path, ['name' => 'Existing', 'description' => 'Changed']);

    $response->assertUnprocessable()->assertInvalid(['name' => 'The name has already been taken.']);
    $this->assertDatabaseCount('projects', 2);
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Original', 'description' => $project->description]);
})->with(['POST', 'PUT', 'PATCH']);

test('project names may be reused in a different workspace', function (string $method): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    ProjectFactory::new()->create(['name' => 'Existing']);
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $path = '/api/v1/workspaces/'.$membership->workspace_id.'/projects'.($method === 'POST' ? '' : '/'.$project->id);

    $response = $this->withBasicAuth($membership->user->email, 'password')
        ->json($method, $path, ['name' => 'Existing', 'description' => 'Feedback forms']);

    $response->assertSuccessful()->assertJsonPath('data.name', 'Existing');
    $this->assertDatabaseHas('projects', ['id' => $response->json('data.id'), 'workspace_id' => $membership->workspace_id, 'name' => 'Existing']);
})->with(['POST', 'PUT', 'PATCH']);

test('project writes return 404 for a project in another workspace and do not change it', function (string $method): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $otherMembership = WorkspaceMembershipFactory::new()->for($membership->user)->create();
    $project = ProjectFactory::new()->for($otherMembership->workspace)->create();

    $response = $this->withBasicAuth($membership->user->email, 'password')
        ->json($method, '/api/v1/workspaces/'.$membership->workspace_id.'/projects/'.$project->id, ['name' => 'Changed', 'description' => 'Changed']);

    $response->assertNotFound();
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => $project->name, 'description' => $project->description]);
})->with(['PUT', 'PATCH', 'DELETE']);

test('project deletion returns 204 and removes dependent forms while preserving other projects', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->create(['project_id' => $project->id]);
    $field = FormFieldFactory::new()->create(['form_id' => $form->id]);
    $otherProject = ProjectFactory::new()->for($membership->workspace)->create();

    $response = $this->withBasicAuth($membership->user->email, 'password')
        ->deleteJson('/api/v1/workspaces/'.$membership->workspace_id.'/projects/'.$project->id);

    $response->assertNoContent();
    $this->assertModelMissing($project);
    $this->assertModelMissing($form);
    $this->assertModelMissing($field);
    $this->assertModelExists($otherProject);
    $this->assertModelExists($membership->workspace);
});

test('project writes ignore query properties outside the JSON body', function (string $method): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $path = '/api/v1/workspaces/'.$membership->workspace_id.'/projects'.($method === 'POST' ? '' : '/'.$project->id);

    $response = $this->withBasicAuth($membership->user->email, 'password')
        ->json($method, $path.'?name=Query+name&description=Query+description', []);

    $response->assertUnprocessable()->assertInvalid(['name']);
    $this->assertDatabaseCount('projects', 1);
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => $project->name, 'description' => $project->description]);
})->with(['POST', 'PUT', 'PATCH']);

test('project writes return 422 for form data without changing persisted projects', function (string $method): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $path = '/api/v1/workspaces/'.$membership->workspace_id.'/projects'.($method === 'POST' ? '' : '/'.$project->id);

    $requestMethod = strtolower($method);

    $response = $this->withBasicAuth($membership->user->email, 'password')->{$requestMethod}(
        $path,
        ['name' => 'Form name', 'description' => 'Form description'],
        ['Accept' => 'application/json', 'Content-Type' => 'application/x-www-form-urlencoded'],
    );

    $response->assertUnprocessable()->assertInvalid(['name']);
    $this->assertDatabaseCount('projects', 1);
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => $project->name, 'description' => $project->description]);
})->with(['POST', 'PUT', 'PATCH']);

test('project PATCH returns 403 for a non-member and leaves the project unchanged', function (): void {
    $user = UserFactory::new()->create();
    $project = ProjectFactory::new()->create();

    $response = $this->withBasicAuth($user->email, 'password')
        ->patchJson('/api/v1/workspaces/'.$project->workspace_id.'/projects/'.$project->id, ['name' => 'Changed']);

    $response->assertForbidden();
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => $project->name]);
});
