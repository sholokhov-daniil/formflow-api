<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Contracts\Repository;

use App\Modules\Tenancy\Application\DTO\CreateWorkspaceData;
use App\Modules\Tenancy\Application\DTO\UpdateWorkspaceData;
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

    /**
     * Создание пространства на основе пользователя
     *
     * @param CreateWorkspaceData $data
     * @param int                 $userId
     *
     * @return Workspace
     */
    public function createForUser(CreateWorkspaceData $data, int $userId): Workspace;

    /**
     * Удаление пространства
     *
     * @param Workspace $workspace
     *
     * @return void
     */
    public function delete(Workspace $workspace): void;

    /**
     * Обновление пространства проектов
     */
    public function update(Workspace $workspace, UpdateWorkspaceData $data): Workspace;
}
