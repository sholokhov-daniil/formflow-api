<?php

use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use Database\Factories\ProjectFactory;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

test('workspace members may list and create projects in their workspace', function (string $ability): void {
    $membership = WorkspaceMembershipFactory::new()->create();

    $allowed = Gate::forUser($membership->user)->allows($ability, [Project::class, $membership->workspace]);

    expect($allowed)->toBeTrue();
})->with(['viewAny', 'create']);

test('workspace members may view update and delete projects in their workspace', function (string $ability): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();

    $allowed = Gate::forUser($membership->user)->allows($ability, $project);

    expect($allowed)->toBeTrue();
})->with(['view', 'update', 'delete']);

test('users without membership may not list or create projects in a workspace', function (string $ability): void {
    $user = UserFactory::new()->create();
    $workspace = WorkspaceFactory::new()->create();

    $allowed = Gate::forUser($user)->allows($ability, [Project::class, $workspace]);

    expect($allowed)->toBeFalse();
})->with(['viewAny', 'create']);

test('users without membership may not view update or delete projects', function (string $ability): void {
    $user = UserFactory::new()->create();
    $project = ProjectFactory::new()->create();

    $allowed = Gate::forUser($user)->allows($ability, $project);

    expect($allowed)->toBeFalse();
})->with(['view', 'update', 'delete']);

test('membership in another workspace does not grant access to its projects', function (string $ability): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->create();

    $allowed = Gate::forUser($membership->user)->allows($ability, $project);

    expect($allowed)->toBeFalse();
})->with(['view', 'update', 'delete']);
