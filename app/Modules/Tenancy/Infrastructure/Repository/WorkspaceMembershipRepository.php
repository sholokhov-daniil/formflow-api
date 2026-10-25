<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Repository;

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;
use App\Modules\Tenancy\Application\DTO\CreateWorkspaceMembershipData;
use App\Modules\Tenancy\Application\DTO\UpdateWorkspaceMembershipData;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Репозиторий участников пространства
 */
class WorkspaceMembershipRepository implements WorkspaceMembershipRepositoryInterface
{
    /**
     * Проверяет участие пользователя в пространстве
     */
    public function exists(int $userId, string $workspaceId): bool
    {
        return WorkspaceMembership::query()
            ->where('user_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->exists();
    }

    public function findByWorkspaceForUser(string $workspaceId, int $userId): Collection
    {
        return WorkspaceMembership::query()
            ->where('workspace_id', $workspaceId)
            ->whereHas('workspace.memberships', static fn (Builder $query): Builder => $query->where('user_id', $userId))
            ->orderBy('user_id')
            ->orderBy('id')
            ->get();
    }

    public function createForWorkspace(CreateWorkspaceMembershipData $data, string $workspaceId): WorkspaceMembership
    {
        return WorkspaceMembership::create([
            'workspace_id' => $workspaceId,
            'user_id' => $data->userId,
        ]);
    }

    public function update(WorkspaceMembership $membership, UpdateWorkspaceMembershipData $data): WorkspaceMembership
    {
        if ($data->userId !== null) {
            $membership->user_id = $data->userId;
        }

        $membership->save();

        return $membership;
    }

    public function delete(WorkspaceMembership $membership): void
    {
        $membership->delete();
    }
}
