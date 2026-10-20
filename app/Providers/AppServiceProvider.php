<?php

namespace App\Providers;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\FormDefinition\Infrastructure\Policies\ProjectPolicy;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use App\Modules\Tenancy\Infrastructure\Observers\WorkspaceMembershipObserver;
use App\Modules\Tenancy\Infrastructure\Observers\WorkspaceObserver;
use App\Modules\Tenancy\Infrastructure\Policies\WorkspacePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Workspace::class, WorkspacePolicy::class);
        WorkspaceMembership::observe(WorkspaceMembershipObserver::class);
        Workspace::observe(WorkspaceObserver::class);
    }
}
