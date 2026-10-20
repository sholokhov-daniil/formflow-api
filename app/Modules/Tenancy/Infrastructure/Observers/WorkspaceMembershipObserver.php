<?php

namespace App\Modules\Tenancy\Infrastructure\Observers;

use App\Modules\Tenancy\Infrastructure\Cache\WorkspaceCacheInvalidator;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;

use Illuminate\Support\Facades\DB;

/**
 * Сброс кеша при изменении участников
 */
final readonly class WorkspaceMembershipObserver
{
    public function __construct(
        private WorkspaceCacheInvalidator $invalidator,
    ) {}

    /**
     * Сбрасывает кеш пользователя после добавления участника
     *
     * @param WorkspaceMembership $membership
     *
     * @return void
     */
    public function created(WorkspaceMembership $membership): void
    {
        $this->invalidateAfterCommit([$membership->user_id]);
    }

    /**
     * Сбрасывает кеш пользователя после удаления участника
     *
     * @param WorkspaceMembership $membership
     *
     * @return void
     */
    public function deleted(WorkspaceMembership $membership): void
    {
        $this->invalidateAfterCommit([$membership->user_id]);
    }

    /**
     * Сбрасывает кеш при смене пользователя или пространства
     *
     * @param WorkspaceMembership $membership
     *
     * @return void
     */
    public function updated(WorkspaceMembership $membership): void
    {
        if (! $membership->wasChanged(['user_id', 'workspace_id'])) {
            return;
        }

        $this->invalidateAfterCommit([
            (int) $membership->getRawOriginal('user_id'),
            $membership->user_id,
        ]);
    }

    /**
     * Откладывает сброс кеша до сохранения транзакции
     *
     * @param  array<int, int>  $userIds
     */
    private function invalidateAfterCommit(array $userIds): void
    {
        DB::afterCommit(function () use ($userIds): void {
            $this->invalidator->forUsers($userIds);
        });
    }
}
