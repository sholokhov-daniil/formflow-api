<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Policies;

use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;

/**
 * Права доступа к пространству
 */
readonly class WorkspacePolicy
{
    public function __construct(
        private WorkspaceMembershipRepositoryInterface $memberships
    ) {}

    /**
     * Разрешает запрос списка пространств
     *
     * @param User $user
     *
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Разрешает создание пространства
     *
     * @param User $user
     *
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Проверяет доступ к просмотру пространства
     *
     * @param User      $user
     * @param Workspace $workspace
     *
     * @return bool
     */
    public function view(User $user, Workspace $workspace): bool
    {
        return $this->isMember($user, $workspace);
    }

    /**
     * Проверяет доступ к изменению пространства
     *
     * @param User      $user
     * @param Workspace $workspace
     *
     * @return bool
     */
    public function update(User $user, Workspace $workspace): bool
    {
        return $this->isMember($user, $workspace);
    }

    /**
     * Проверяет доступ к удалению пространства
     *
     * @param User      $user
     * @param Workspace $workspace
     *
     * @return bool
     */
    public function delete(User $user, Workspace $workspace): bool
    {
        return $this->isMember($user, $workspace);
    }

    /**
     * Проверяет участие пользователя в пространстве
     *
     * @param User      $user
     * @param Workspace $workspace
     *
     * @return bool
     */
    private function isMember(User $user, Workspace $workspace): bool
    {
        return $this->memberships->exists($user->id, $workspace->id);
    }
}
