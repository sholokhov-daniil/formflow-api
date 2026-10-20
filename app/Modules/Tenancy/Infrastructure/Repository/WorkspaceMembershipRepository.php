<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Repository;

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;

/**
 * Репозиторий участников пространства
 */
class WorkspaceMembershipRepository implements WorkspaceMembershipRepositoryInterface
{
    /**
     * Проверяет участие пользователя в пространстве
     *
     * @param int    $userId
     * @param string $workspaceId
     *
     * @return bool
     */
    public function exists(int $userId, string $workspaceId): bool
    {
        return WorkspaceMembership::query()
            ->where('user_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->exists();
    }
}
