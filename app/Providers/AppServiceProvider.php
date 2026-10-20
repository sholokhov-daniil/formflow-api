<?php

namespace App\Providers;

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Infrastructure\Policies\WorkspacePolicy;
use App\Modules\Tenancy\Infrastructure\Repository\WorkspaceMembershipRepository;
use App\Modules\Tenancy\Infrastructure\Repository\WorkspaceRepository;
use Illuminate\Support\Facades\Gate;
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
            WorkspaceRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);
    }
}
