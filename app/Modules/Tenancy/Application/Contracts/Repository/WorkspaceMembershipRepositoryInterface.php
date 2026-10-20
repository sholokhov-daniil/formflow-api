<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Contracts\Repository;

/**
 * Репозиторий участников пространства проектов
 */
interface WorkspaceMembershipRepositoryInterface
{
    /**
     * Проверяет, что пользователь входит в пространство
     */
    public function exists(int $userId, string $workspaceId): bool;
}
