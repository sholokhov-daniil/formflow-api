<?php

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('membership lookups recognize an existing user and workspace pair', function () {
    $membership = WorkspaceMembershipFactory::new()->create();

    $result = app(WorkspaceMembershipRepositoryInterface::class)->exists($membership->user_id, $membership->workspace_id);

    expect($result)->toBeTrue();
});

test('membership lookups do not accept another users membership', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $otherUser = UserFactory::new()->create();

    $result = app(WorkspaceMembershipRepositoryInterface::class)->exists($otherUser->id, $membership->workspace_id);

    expect($result)->toBeFalse();
});

test('membership lookups do not accept membership in another workspace', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $otherWorkspace = WorkspaceFactory::new()->create();

    $result = app(WorkspaceMembershipRepositoryInterface::class)->exists($membership->user_id, $otherWorkspace->id);

    expect($result)->toBeFalse();
});

test('membership lookups reject a pair when no memberships exist', function () {
    $user = UserFactory::new()->create();
    $workspace = WorkspaceFactory::new()->create();

    $result = app(WorkspaceMembershipRepositoryInterface::class)->exists($user->id, $workspace->id);

    expect($result)->toBeFalse();
});
