<?php

namespace App\Providers;

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use App\Modules\Tenancy\Infrastructure\Observers\WorkspaceMembershipObserver;
use App\Modules\Tenancy\Infrastructure\Observers\WorkspaceObserver;
use App\Modules\Tenancy\Infrastructure\Policies\WorkspacePolicy;
use App\Modules\Tenancy\Infrastructure\Repository\CachedWorkspaceRepository;
use App\Modules\Tenancy\Infrastructure\Repository\WorkspaceMembershipRepository;
use App\Modules\Tenancy\Infrastructure\Repository\WorkspaceRepository;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
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

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);
        WorkspaceMembership::observe(WorkspaceMembershipObserver::class);
        Workspace::observe(WorkspaceObserver::class);
    }
}
