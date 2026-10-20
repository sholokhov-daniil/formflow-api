<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Cache;

use App\Modules\Tenancy\Infrastructure\Repository\CachedWorkspaceRepository;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;

/**
 * Сброс кеша списков пространств
 */
final readonly class WorkspaceCacheInvalidator
{
    public function __construct(
        private CacheRepository $cache,
    ) {
    }

    /**
     * Сбрасывает кеш пространств пользователя
     *
     * @param int $userId
     *
     * @return void
     */
    public function forUser(int $userId): void
    {
        $this->cache->forever(
            CachedWorkspaceRepository::userVersionKey($userId),
            (string)Str::uuid(),
        );
    }

    /**
     * Сбрасывает кеш пространств указанных пользователей
     *
     * @param array<int, int> $userIds
     */
    public function forUsers(array $userIds): void
    {
        foreach (array_unique($userIds) as $userId) {
            $this->forUser($userId);
        }
    }

    /**
     * Сбрасывает кеш всех списков пространств
     *
     * @return void
     */
    public function all(): void
    {
        $this->cache->forever(
            CachedWorkspaceRepository::WORKSPACE_VERSION_KEY,
            (string)Str::uuid(),
        );
    }
}
