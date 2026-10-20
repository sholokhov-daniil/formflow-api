<?php

use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

test('authenticated users may list and create workspaces without any membership', function (string $ability) {
    $user = UserFactory::new()->make();

    $allowed = Gate::forUser($user)->allows($ability, Workspace::class);

    expect($allowed)->toBeTrue();
})->with(['viewAny', 'create']);

test('workspace members may view update and delete their workspace', function (string $ability) {
    $membership = WorkspaceMembershipFactory::new()->create();

    $allowed = Gate::forUser($membership->user)->allows($ability, $membership->workspace);

    expect($allowed)->toBeTrue();
})->with(['view', 'update', 'delete']);

test('another users membership does not grant access to a workspace', function (string $ability) {
    $membership = WorkspaceMembershipFactory::new()->create();
    $otherUser = UserFactory::new()->create();

    $allowed = Gate::forUser($otherUser)->allows($ability, $membership->workspace);

    expect($allowed)->toBeFalse();
})->with(['view', 'update', 'delete']);

test('membership in another workspace does not grant access to this workspace', function (string $ability) {
    $membership = WorkspaceMembershipFactory::new()->create();
    $otherWorkspace = WorkspaceFactory::new()->create();

    $allowed = Gate::forUser($membership->user)->allows($ability, $otherWorkspace);

    expect($allowed)->toBeFalse();
})->with(['view', 'update', 'delete']);
