<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Repository;

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Репозиторий пространств с кешированием
 */
final readonly class CachedWorkspaceRepository implements WorkspaceRepositoryInterface
{
    public const string WORKSPACE_VERSION_KEY = 'tenancy:workspaces:version:v1';

    private const int CACHE_TTL = 600;

    public function __construct(
        protected WorkspaceRepositoryInterface $repository,
        protected CacheRepository $cache
    ) {}

    /**
     * Возвращает доступные пространства пользователя
     *
     * @return Collection<int, Workspace>
     */
    public function accessibleToUser(int $userId): Collection
    {
        if (DB::transactionLevel() > 0) {
            return $this->repository->accessibleToUser($userId);
        }

        $rows = $this->cache->remember(
            $this->dataCacheKeyForUser($userId),
            self::CACHE_TTL,
            fn (): array => $this->repository
                ->accessibleToUser($userId)
                ->map(
                    static fn (Workspace $workspace): array => $workspace->getAttributes(),
                )
                ->all(),
        );

        return Workspace::query()->hydrate($rows);
    }

    /**
     * Возвращает ключ версии кеша пользователя
     *
     * @param int $userId
     *
     * @return string
     */
    public static function userVersionKey(int $userId): string
    {
        return "tenancy:workspaces:user:{$userId}:version:v1";
    }

    /**
     * Возвращает ключ списка с учётом версий кеша
     *
     * @param int $userId
     *
     * @return string
     */
    private function dataCacheKeyForUser(int $userId): string
    {
        $workspaceVersion = $this->cache->rememberForever(
            self::WORKSPACE_VERSION_KEY,
            static fn (): string => (string) Str::uuid(),
        );

        $userVersion = $this->cache->rememberForever(
            self::userVersionKey($userId),
            static fn (): string => (string) Str::uuid(),
        );

        return "tenancy:workspaces:user:{$userId}:v2"
            .":{$workspaceVersion}:{$userVersion}";
    }
}
