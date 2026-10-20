<?php

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Cache\WorkspaceCacheInvalidator;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Infrastructure\Repository\CachedWorkspaceRepository;
use App\Modules\Tenancy\Infrastructure\Repository\WorkspaceRepository;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

uses(DatabaseMigrations::class);

test('a cached list returns fresh workspace models without repeating the database query', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    $expected = $membership->workspace->getAttributes();
    DB::enableQueryLog();
    $first = $repository->accessibleToUser($membership->user_id);
    $first->first()->name = 'Changed only in memory';

    $cached = $repository->accessibleToUser($membership->user_id);

    expect(DB::getQueryLog())->toHaveCount(1);
    expect($cached)->toHaveCount(1);
    expect($cached->first())->toBeInstanceOf(Workspace::class);
    expect($cached->first()->getAttributes())->toBe($expected);
    expect($cached->first()->exists)->toBeTrue();
    expect($cached->first())->not->toBe($first->first());
});

test('empty lists are cached independently from other users lists', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $otherUser = UserFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    $repository->accessibleToUser($membership->user_id);
    $repository->accessibleToUser($otherUser->id);
    DB::enableQueryLog();

    $memberWorkspaces = $repository->accessibleToUser($membership->user_id);
    $otherWorkspaces = $repository->accessibleToUser($otherUser->id);

    expect($memberWorkspaces->modelKeys())->toBe([$membership->workspace_id]);
    expect($otherWorkspaces)->toBeEmpty();
    expect(DB::getQueryLog())->toBeEmpty();
});

test('cached data expires after ten minutes', function () {
    $this->freezeTime();
    $membership = WorkspaceMembershipFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    $originalName = $repository->accessibleToUser($membership->user_id)->first()->name;
    Workspace::query()->whereKey($membership->workspace_id)->update(['name' => 'Updated without model events']);
    $this->travel(599)->seconds();

    expect($repository->accessibleToUser($membership->user_id)->first()->name)->toBe($originalName);

    $this->travel(1)->seconds();

    expect($repository->accessibleToUser($membership->user_id)->first()->name)->toBe('Updated without model events');
});

test('transaction reads see local changes without replacing the previously cached list', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    $originalName = $repository->accessibleToUser($membership->user_id)->first()->name;
    DB::beginTransaction();
    try {
        $membership->workspace->update(['name' => 'Uncommitted name']);

        $insideTransaction = $repository->accessibleToUser($membership->user_id);

        expect($insideTransaction->first()->name)->toBe('Uncommitted name');
    } finally {
        DB::rollBack();
    }
    DB::enableQueryLog();

    $afterRollback = $repository->accessibleToUser($membership->user_id);

    expect($afterRollback->first()->name)->toBe($originalName);
    expect(DB::getQueryLog())->toBeEmpty();
});

test('a cold cache does not retain memberships read inside a rolled back transaction', function () {
    $user = UserFactory::new()->create();
    $repository = app(WorkspaceRepositoryInterface::class);
    DB::beginTransaction();
    try {
        $membership = WorkspaceMembershipFactory::new()->for($user)->create();

        expect($repository->accessibleToUser($user->id)->modelKeys())->toBe([$membership->workspace_id]);
    } finally {
        DB::rollBack();
    }

    $result = $repository->accessibleToUser($user->id);

    expect($result)->toBeEmpty();
    $this->assertDatabaseCount('workspace_memberships', 0);
});

test('invalidation during cache filling prevents later requests from reusing the obsolete result', function (string $scope) {
    $membership = WorkspaceMembershipFactory::new()->create();
    $source = app(WorkspaceRepository::class);
    $invalidator = app(WorkspaceCacheInvalidator::class);
    $repository = Mockery::mock(WorkspaceRepositoryInterface::class);
    $firstRead = true;
    $repository->shouldReceive('accessibleToUser')->with($membership->user_id)->twice()
        ->andReturnUsing(function (int $userId) use ($source, $invalidator, $membership, $scope, &$firstRead): Collection {
            $snapshot = $source->accessibleToUser($userId);
            if ($firstRead) {
                $firstRead = false;
                Workspace::query()->whereKey($membership->workspace_id)->update(['name' => 'Committed name']);
                if ($scope === 'user') {
                    $invalidator->forUser($userId);
                } else {
                    $invalidator->all();
                }
            }

            return $snapshot;
        });
    $cachedRepository = new CachedWorkspaceRepository($repository, app(CacheRepository::class));

    $obsolete = $cachedRepository->accessibleToUser($membership->user_id);
    $current = $cachedRepository->accessibleToUser($membership->user_id);
    $cached = $cachedRepository->accessibleToUser($membership->user_id);

    expect($obsolete->first()->name)->not->toBe('Committed name');
    expect($current->first()->name)->toBe('Committed name');
    expect($cached->first()->name)->toBe('Committed name');
})->with(['user', 'all']);
