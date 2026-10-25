<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Contracts\Repository;

use App\Modules\Tenancy\Application\DTO\CreateWorkspaceMembershipData;
use App\Modules\Tenancy\Application\DTO\UpdateWorkspaceMembershipData;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use Illuminate\Support\Collection;

interface WorkspaceMembershipRepositoryInterface
{
    public function exists(int $userId, string $workspaceId): bool;

    /** @return Collection<int, WorkspaceMembership> */
    public function findByWorkspaceForUser(string $workspaceId, int $userId): Collection;

    public function createForWorkspace(CreateWorkspaceMembershipData $data, string $workspaceId): WorkspaceMembership;

    public function update(WorkspaceMembership $membership, UpdateWorkspaceMembershipData $data): WorkspaceMembership;

    public function delete(WorkspaceMembership $membership): void;
}
