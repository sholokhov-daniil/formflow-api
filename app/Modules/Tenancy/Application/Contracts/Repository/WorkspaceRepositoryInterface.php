<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Contracts\Repository;

use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Illuminate\Support\Collection;

/**
 * Репозиторий пространств проектов
 */
interface WorkspaceRepositoryInterface
{
    /**
     * Возвращает доступные пространства пользователя
     *
     * @return Collection<int, Workspace>
     */
    public function accessibleToUser(int $userId): Collection;
}
