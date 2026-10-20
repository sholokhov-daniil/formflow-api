<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Contracts\Repository;

/**
 * Репозиторий участников пространства
 */
interface WorkspaceMembershipRepositoryInterface
{
    /**
     * Проверяет участие пользователя в пространстве
     *
     * @param int    $userId
     * @param string $workspaceId
     *
     * @return bool
     */
    public function exists(int $userId, string $workspaceId): bool;
}
