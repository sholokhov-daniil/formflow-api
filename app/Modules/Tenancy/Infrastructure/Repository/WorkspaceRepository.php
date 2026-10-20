<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Repository;

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use App\Modules\Tenancy\Application\DTO\CreateWorkspaceData;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Репозиторий пространств проектов
 */
class WorkspaceRepository implements WorkspaceRepositoryInterface
{
    /**
     * Возвращает доступные пространства пользователя
     *
     * @return Collection<int, Workspace>
     */
    public function accessibleToUser(int $userId): Collection
    {
        return Workspace::query()
            ->whereIn(
                'id',
                WorkspaceMembership::query()
                    ->select('workspace_id')
                    ->where('user_id', $userId),
            )
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * Создание новой области на основе пользователя
     *
     * @param CreateWorkspaceData $data
     * @param int                 $userId
     *
     * @return Workspace
     * @throws Throwable
     */
    public function createForUser(CreateWorkspaceData $data, int $userId): Workspace
    {
        return DB::transaction(function () use ($data, $userId): Workspace {
            $workspace = Workspace::create([
                'name' => $data->name,
                'kind' => $data->kind->value,
            ]);

            WorkspaceMembership::create([
                'workspace_id' => $workspace->id,
                'user_id' => $userId,
            ]);

            return $workspace;
        });
    }
}
