<?php

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
