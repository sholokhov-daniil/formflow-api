<?php

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Repository\CachedWorkspaceRepository;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

uses(DatabaseMigrations::class);

test('updating a workspace invalidates every members list after commit', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $otherMembership = WorkspaceMembershipFactory::new()->for($membership->workspace)->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    $repository->accessibleToUser($membership->user_id);
    $repository->accessibleToUser($otherMembership->user_id);

    DB::transaction(fn () => $membership->workspace->update(['name' => 'Renamed workspace', 'kind' => 'personal']));

    foreach ([$membership->user_id, $otherMembership->user_id] as $userId) {
        $workspace = $repository->accessibleToUser($userId)->first();
        expect($workspace->name)->toBe('Renamed workspace');
        expect($workspace->kind)->toBe('personal');
    }
    $this->assertDatabaseHas('workspaces', ['id' => $membership->workspace_id, 'name' => 'Renamed workspace']);
});

test('deleting a workspace invalidates lists despite database cascade bypassing membership observers', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    $repository->accessibleToUser($membership->user_id);

    DB::transaction(fn () => $membership->workspace->delete());

    expect($repository->accessibleToUser($membership->user_id))->toBeEmpty();
    $this->assertModelMissing($membership);
    $this->assertDatabaseMissing('workspaces', ['id' => $membership->workspace_id]);
});

test('workspace invalidation is deferred until the outermost transaction commits', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    $repository->accessibleToUser($membership->user_id);
    $cache = app(CacheRepository::class);
    $before = $cache->get(CachedWorkspaceRepository::WORKSPACE_VERSION_KEY);
    DB::beginTransaction();
    try {
        DB::transaction(fn () => $membership->workspace->update(['name' => 'Committed later']));

        expect($cache->get(CachedWorkspaceRepository::WORKSPACE_VERSION_KEY))->toBe($before);

        DB::commit();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }

    expect($repository->accessibleToUser($membership->user_id)->first()->name)->toBe('Committed later');
});
