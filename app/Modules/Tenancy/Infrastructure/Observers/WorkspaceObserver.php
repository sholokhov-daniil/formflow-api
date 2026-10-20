<?php

namespace App\Modules\Tenancy\Infrastructure\Observers;

use App\Modules\Tenancy\Infrastructure\Cache\WorkspaceCacheInvalidator;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final readonly class WorkspaceObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private WorkspaceCacheInvalidator $cacheInvalidator,
    ) {}


    public function updated(Workspace $workspace): void
    {
        $this->cacheInvalidator->all();
    }

    public function deleted(Workspace $workspace): void
    {
        $this->cacheInvalidator->all();
    }
}
