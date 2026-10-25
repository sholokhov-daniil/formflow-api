<?php

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

test('membership creation makes a workspace visible after a cached empty list', function (): void {
    $actor = WorkspaceMembershipFactory::new()->create();
    $user = UserFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    expect($repository->accessibleToUser($user->id))->toBeEmpty();

    $response = $this->withBasicAuth($actor->user->email, 'password')->postJson(
        '/api/v1/workspaces/'.$actor->workspace_id.'/memberships',
        ['user_id' => $user->id],
    );

    $response->assertCreated();
    expect($repository->accessibleToUser($user->id)->modelKeys())->toBe([$actor->workspace_id]);
});

test('membership replacement refreshes workspace visibility for both users', function (): void {
    $actor = WorkspaceMembershipFactory::new()->create();
    $membership = WorkspaceMembershipFactory::new()->for($actor->workspace)->create();
    $replacement = UserFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    expect($repository->accessibleToUser($membership->user_id)->modelKeys())->toBe([$actor->workspace_id]);
    expect($repository->accessibleToUser($replacement->id))->toBeEmpty();

    $response = $this->withBasicAuth($actor->user->email, 'password')->patchJson(
        '/api/v1/workspaces/'.$actor->workspace_id.'/memberships/'.$membership->id,
        ['user_id' => $replacement->id],
    );

    $response->assertOk();
    expect($repository->accessibleToUser($membership->user_id))->toBeEmpty();
    expect($repository->accessibleToUser($replacement->id)->modelKeys())->toBe([$actor->workspace_id]);
});

test('membership deletion revokes access and removes the cached workspace', function (): void {
    $actor = WorkspaceMembershipFactory::new()->create();
    $membership = WorkspaceMembershipFactory::new()->for($actor->workspace)->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    expect($repository->accessibleToUser($membership->user_id)->modelKeys())->toBe([$actor->workspace_id]);

    $response = $this->withBasicAuth($actor->user->email, 'password')->deleteJson(
        '/api/v1/workspaces/'.$actor->workspace_id.'/memberships/'.$membership->id,
    );

    $response->assertNoContent();
    $this->assertModelMissing($membership);
    expect($repository->accessibleToUser($membership->user_id))->toBeEmpty();
});
