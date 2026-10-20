<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Infrastructure\Policies;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;

final readonly class ProjectPolicy
{
    public function __construct(
        private WorkspaceMembershipRepositoryInterface $memberships
    ) {}

    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $this->isWorkspaceMember($user, $workspace->id);
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $this->isWorkspaceMember($user, $workspace->id);
    }

    public function view(User $user, Project $project): bool
    {
        return $this->isWorkspaceMember($user, $project->workspace_id);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->isWorkspaceMember($user, $project->workspace_id);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->isWorkspaceMember($user, $project->workspace_id);
    }

    private function isWorkspaceMember(User $user, string $workspaceId): bool
    {
        return $this->memberships->exists($user->id, $workspaceId);
    }
}
