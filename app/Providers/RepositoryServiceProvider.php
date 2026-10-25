<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\FormDefinition\Application\Contracts\Repository\FormFieldRepositoryInterface;
use App\Modules\FormDefinition\Application\Contracts\Repository\FormRepositoryInterface;
use App\Modules\FormDefinition\Application\Contracts\Repository\ProjectWorkspaceRepositoryInterface;
use App\Modules\FormDefinition\Infrastructure\Repository\FormFieldRepository;
use App\Modules\FormDefinition\Infrastructure\Repository\FormRepository;
use App\Modules\FormDefinition\Infrastructure\Repository\ProjectWorkspaceRepository;
use App\Modules\Submissions\Application\Contracts\Repository\SubmissionRepositoryInterface;
use App\Modules\Submissions\Application\Contracts\Repository\SubmissionValueRepositoryInterface;
use App\Modules\Submissions\Infrastructure\Repository\SubmissionRepository;
use App\Modules\Submissions\Infrastructure\Repository\SubmissionValueRepository;
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
        $this->app->bind(FormRepositoryInterface::class, FormRepository::class);
        $this->app->bind(FormFieldRepositoryInterface::class, FormFieldRepository::class);
        $this->app->bind(SubmissionRepositoryInterface::class, SubmissionRepository::class);
        $this->app->bind(SubmissionValueRepositoryInterface::class, SubmissionValueRepository::class);

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

        $this->app->bind(
            ProjectWorkspaceRepositoryInterface::class,
            ProjectWorkspaceRepository::class,
        );
    }

    public function boot(): void {}
}
