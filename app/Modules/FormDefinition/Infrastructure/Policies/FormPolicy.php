<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Infrastructure\Policies;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;

final readonly class FormPolicy
{
    public function __construct(private WorkspaceMembershipRepositoryInterface $memberships) {}

    public function viewAny(User $user, Project $project): bool
    {
        return $this->memberships->exists($user->id, $project->workspace_id);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->memberships->exists($user->id, $project->workspace_id);
    }

    public function view(User $user, Form $form): bool
    {
        return $this->memberships->exists($user->id, $form->project->workspace_id);
    }

    public function update(User $user, Form $form): bool
    {
        return $this->memberships->exists($user->id, $form->project->workspace_id);
    }

    public function delete(User $user, Form $form): bool
    {
        return $this->memberships->exists($user->id, $form->project->workspace_id);
    }
}
