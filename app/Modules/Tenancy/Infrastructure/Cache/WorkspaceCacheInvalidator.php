<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Cache;

use App\Modules\Tenancy\Infrastructure\Repository\CachedWorkspaceRepository;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;

/**
 * Производит очистку кеша по ключам
 */
final readonly class WorkspaceCacheInvalidator
{
    public function __construct(
        private CacheRepository $cache,
    ) {}

    public function forUser(int $userId): void
    {
        $this->cache->forever(
            CachedWorkspaceRepository::userVersionKey($userId),
            (string) Str::uuid(),
        );
    }

    /**
     * @param array<int, int> $userIds
     */
    public function forUsers(array $userIds): void
    {
        foreach (array_unique($userIds) as $userId) {
            $this->forUser($userId);
        }
    }

    public function all(): void
    {
        $this->cache->forever(
            CachedWorkspaceRepository::WORKSPACE_VERSION_KEY,
            (string) Str::uuid(),
        );
    }
}
