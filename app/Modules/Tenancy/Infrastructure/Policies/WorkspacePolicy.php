<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Policies;

use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;

/**
 * Политика прав доступа к пространству форм
 */
readonly class WorkspacePolicy
{
    public function __construct(
        private WorkspaceMembershipRepositoryInterface $memberships
    ) {}

    /**
     * Пользователь может запросить список доступных пространств
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Проверка, что пользователь может создать пространство проектов
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Проверка, что пользователь может посмотреть пространство групп
     */
    public function view(User $user, Workspace $workspace): bool
    {
        return $this->isMember($user, $workspace);
    }

    /**
     * Проверка, что пользовать может обновить пространства групп
     */
    public function update(User $user, Workspace $workspace): bool
    {
        return $this->isMember($user, $workspace);
    }

    /**
     * Проверка, что пользователь может удалить пространство групп
     */
    public function delete(User $user, Workspace $workspace): bool
    {
        return $this->isMember($user, $workspace);
    }

    /**
     * Проверяет, что пользователь является членом пространства групп
     */
    private function isMember(User $user, Workspace $workspace): bool
    {
        return $this->memberships->exists($user->id, $workspace->id);
    }
}
