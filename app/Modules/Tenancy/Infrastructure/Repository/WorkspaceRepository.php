<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Repository;

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use Illuminate\Support\Collection;

/**
 * Репозиторий пространств проектов
 */
class WorkspaceRepository implements WorkspaceRepositoryInterface
{
    /**
     * Возвращает доступные пространства пользователя
     *
     * @return Collection<int, Workspace>
     */
    public function accessibleToUser(int $userId): Collection
    {
        return Workspace::query()
            ->whereIn(
                'id',
                WorkspaceMembership::query()
                    ->select('workspace_id')
                    ->where('user_id', $userId),
            )
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }
}
