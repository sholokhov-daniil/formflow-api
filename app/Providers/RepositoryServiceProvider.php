<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Repository\CachedWorkspaceRepository;
use App\Modules\Tenancy\Infrastructure\Repository\WorkspaceMembershipRepository;
use App\Modules\Tenancy\Infrastructure\Repository\WorkspaceRepository;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            WorkspaceMembershipRepositoryInterface::class,
            WorkspaceMembershipRepository::class,
        );

        $this->app->bind(
            WorkspaceRepositoryInterface::class,
            function (Application $app): CachedWorkspaceRepository {
                return new CachedWorkspaceRepository(
                    repository: $app->make(WorkspaceRepository::class),
                    cache: $app->make(CacheRepository::class)
                );
            }
        );
    }

    public function boot(): void {}
}
