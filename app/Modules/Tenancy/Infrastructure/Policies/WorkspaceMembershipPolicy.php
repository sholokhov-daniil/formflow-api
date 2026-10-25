<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Policies;

use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;

final readonly class WorkspaceMembershipPolicy
{
    public function __construct(private WorkspaceMembershipRepositoryInterface $memberships) {}

    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $this->memberships->exists($user->id, $workspace->id);
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $this->memberships->exists($user->id, $workspace->id);
    }

    public function view(User $user, WorkspaceMembership $membership): bool
    {
        return $this->memberships->exists($user->id, $membership->workspace_id);
    }

    public function update(User $user, WorkspaceMembership $membership): bool
    {
        return $this->memberships->exists($user->id, $membership->workspace_id);
    }

    public function delete(User $user, WorkspaceMembership $membership): bool
    {
        return $this->memberships->exists($user->id, $membership->workspace_id);
    }
}
