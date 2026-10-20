<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Contracts\Repository;

use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Illuminate\Support\Collection;

/**
 * Репозиторий участников пространства проектов
 */
interface WorkspaceRepositoryInterface
{
    /**
     * Возвращает все доступные пространства проектов для пользователя
     *
     *
     * @return Collection<int, Workspace>
     */
    public function accessibleToUser(int $userId): Collection;
}
