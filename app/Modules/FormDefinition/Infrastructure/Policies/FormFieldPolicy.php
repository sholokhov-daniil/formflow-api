<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Infrastructure\Policies;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;
use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;

final readonly class FormFieldPolicy
{
    public function __construct(private WorkspaceMembershipRepositoryInterface $memberships) {}

    public function viewAny(User $user, Form $form): bool
    {
        return $this->memberships->exists($user->id, $form->project->workspace_id);
    }

    public function create(User $user, Form $form): bool
    {
        return $this->memberships->exists($user->id, $form->project->workspace_id);
    }

    public function view(User $user, FormField $field): bool
    {
        return $this->memberships->exists($user->id, $field->form->project->workspace_id);
    }

    public function update(User $user, FormField $field): bool
    {
        return $this->memberships->exists($user->id, $field->form->project->workspace_id);
    }

    public function delete(User $user, FormField $field): bool
    {
        return $this->memberships->exists($user->id, $field->form->project->workspace_id);
    }
}
