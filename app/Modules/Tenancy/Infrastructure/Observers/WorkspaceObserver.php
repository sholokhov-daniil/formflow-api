<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Observers;

use App\Modules\Tenancy\Infrastructure\Cache\WorkspaceCacheInvalidator;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Сброс кеша при изменении пространства
 */
final readonly class WorkspaceObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private WorkspaceCacheInvalidator $cacheInvalidator,
    ) {}

    /**
     * Сбрасывает кеш после обновления пространства
     *
     * @param Workspace $workspace
     *
     * @return void
     */
    public function updated(Workspace $workspace): void
    {
        $this->cacheInvalidator->all();
    }

    /**
     * Сбрасывает кеш после удаления пространства
     *
     * @param Workspace $workspace
     *
     * @return void
     */
    public function deleted(Workspace $workspace): void
    {
        $this->cacheInvalidator->all();
    }
}
