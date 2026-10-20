<?php

use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

test('memberships persist the user and workspace and resolve both relationships', function () {
    $user = UserFactory::new()->create();
    $workspace = WorkspaceFactory::new()->create();

    $membership = WorkspaceMembership::query()->create([
        'user_id' => $user->id,
        'workspace_id' => $workspace->id,
    ])->refresh();

    $this->assertModelExists($membership);
    expect($membership->id)->toBeUuid();
    expect($membership->user->is($user))->toBeTrue();
    expect($membership->workspace->is($workspace))->toBeTrue();
});

test('a user cannot join the same workspace twice', function () {
    $membership = WorkspaceMembershipFactory::new()->create();

    expect(fn () => DB::transaction(fn () => WorkspaceMembership::query()->create([
        'user_id' => $membership->user_id,
        'workspace_id' => $membership->workspace_id,
    ])))->toThrow(QueryException::class, 'workspace_memberships_user_workspace_unique');

    $this->assertDatabaseCount('workspace_memberships', 1);
});

test('a user can join multiple workspaces', function () {
    $user = UserFactory::new()->create();
    $personalWorkspace = WorkspaceFactory::new()->personal()->create();
    $teamWorkspace = WorkspaceFactory::new()->create();

    $personalMembership = WorkspaceMembershipFactory::new()->for($user)->for($personalWorkspace)->create();
    $teamMembership = WorkspaceMembershipFactory::new()->for($user)->for($teamWorkspace)->create();

    $this->assertModelExists($personalMembership);
    $this->assertModelExists($teamMembership);
    $this->assertDatabaseCount('workspace_memberships', 2);
});

test('multiple users can join the same workspace', function () {
    $workspace = WorkspaceFactory::new()->create();

    $memberships = WorkspaceMembershipFactory::new()->count(2)->for($workspace)->create();

    $this->assertModelExists($memberships);
    expect($memberships->pluck('user_id')->unique())->toHaveCount(2);
});

test('deleting a user removes only their memberships and preserves the workspace', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $workspace = $membership->workspace;
    $user = $membership->user;
    $otherMembership = WorkspaceMembershipFactory::new()->for($workspace)->create();

    $user->delete();

    $this->assertModelMissing($membership);
    $this->assertModelExists($workspace);
    $this->assertModelExists($otherMembership);
});
