<?php

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Repository\CachedWorkspaceRepository;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

uses(DatabaseMigrations::class);

test('creating a membership with a string user ID invalidates only that users cached list', function () {
    $user = UserFactory::new()->create();
    $otherMembership = WorkspaceMembershipFactory::new()->create();
    $workspace = WorkspaceFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    expect($repository->accessibleToUser($user->id))->toBeEmpty();
    $repository->accessibleToUser($otherMembership->user_id);

    $membership = WorkspaceMembershipFactory::new()->for($workspace)->create(['user_id' => (string) $user->id]);

    $this->assertModelExists($membership);
    expect($membership->user_id)->toBe($user->id);
    expect($repository->accessibleToUser($user->id)->modelKeys())->toBe([$workspace->id]);
    DB::enableQueryLog();
    expect($repository->accessibleToUser($otherMembership->user_id)->modelKeys())->toBe([$otherMembership->workspace_id]);
    expect(DB::getQueryLog())->toBeEmpty();
});

test('deleting a membership removes the workspace from the cached list after commit', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    $repository->accessibleToUser($membership->user_id);

    DB::transaction(fn () => $membership->delete());

    $this->assertModelMissing($membership);
    expect($repository->accessibleToUser($membership->user_id))->toBeEmpty();
});

test('transferring a membership invalidates the old and new users after commit', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $oldUserId = $membership->user_id;
    $newUser = UserFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    $repository->accessibleToUser($oldUserId);
    $repository->accessibleToUser($newUser->id);

    DB::transaction(fn () => $membership->update(['user_id' => (string) $newUser->id]));

    expect($repository->accessibleToUser($oldUserId))->toBeEmpty();
    expect($repository->accessibleToUser($newUser->id)->modelKeys())->toBe([$membership->workspace_id]);
    $this->assertDatabaseHas('workspace_memberships', ['id' => $membership->id, 'user_id' => $newUser->id]);
});

test('moving a membership to another workspace refreshes the users list', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $workspace = WorkspaceFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    $repository->accessibleToUser($membership->user_id);

    $membership->update(['workspace_id' => $workspace->id]);

    expect($repository->accessibleToUser($membership->user_id)->modelKeys())->toBe([$workspace->id]);
    $this->assertDatabaseHas('workspace_memberships', ['id' => $membership->id, 'workspace_id' => $workspace->id]);
});

test('multiple membership changes in one transaction invalidate every affected user', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $firstUserId = $membership->user_id;
    $secondUser = UserFactory::new()->create();
    $thirdUser = UserFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    foreach ([$firstUserId, $secondUser->id, $thirdUser->id] as $userId) {
        $repository->accessibleToUser($userId);
    }

    DB::transaction(function () use ($membership, $secondUser, $thirdUser) {
        $membership->update(['user_id' => $secondUser->id]);
        $membership->update(['user_id' => $thirdUser->id]);
    });

    expect($repository->accessibleToUser($firstUserId))->toBeEmpty();
    expect($repository->accessibleToUser($secondUser->id))->toBeEmpty();
    expect($repository->accessibleToUser($thirdUser->id)->modelKeys())->toBe([$membership->workspace_id]);
});

test('rolling back a membership deletion preserves the cached list without another query', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    $repository->accessibleToUser($membership->user_id);
    DB::beginTransaction();
    try {
        $membership->delete();
    } finally {
        DB::rollBack();
    }
    $this->assertDatabaseHas('workspace_memberships', ['id' => $membership->id]);
    DB::enableQueryLog();

    $result = $repository->accessibleToUser($membership->user_id);

    expect($result->modelKeys())->toBe([$membership->workspace_id]);
    expect(DB::getQueryLog())->toBeEmpty();
});

test('updating membership timestamps does not invalidate the cached list', function () {
    $this->freezeTime();
    $membership = WorkspaceMembershipFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    $repository->accessibleToUser($membership->user_id);
    $this->travel(1)->minute();

    $membership->touch();

    $this->assertDatabaseHas('workspace_memberships', ['id' => $membership->id, 'updated_at' => now()]);
    DB::enableQueryLog();
    expect($repository->accessibleToUser($membership->user_id)->modelKeys())->toBe([$membership->workspace_id]);
    expect(DB::getQueryLog())->toBeEmpty();
});

test('membership invalidation waits for commit and retains the user ID captured at each change', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $oldUserId = $membership->user_id;
    $newUser = UserFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    $repository->accessibleToUser($oldUserId);
    $repository->accessibleToUser($newUser->id);
    $cache = app(CacheRepository::class);
    $oldVersion = $cache->get(CachedWorkspaceRepository::userVersionKey($oldUserId));
    $newVersion = $cache->get(CachedWorkspaceRepository::userVersionKey($newUser->id));
    DB::beginTransaction();
    try {
        $membership->update(['user_id' => $newUser->id]);

        expect($cache->get(CachedWorkspaceRepository::userVersionKey($oldUserId)))->toBe($oldVersion);
        expect($cache->get(CachedWorkspaceRepository::userVersionKey($newUser->id)))->toBe($newVersion);

        DB::commit();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }

    expect($repository->accessibleToUser($oldUserId))->toBeEmpty();
    expect($repository->accessibleToUser($newUser->id)->modelKeys())->toBe([$membership->workspace_id]);
});
