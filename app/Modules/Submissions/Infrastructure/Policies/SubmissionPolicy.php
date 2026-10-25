<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Infrastructure\Policies;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;

final readonly class SubmissionPolicy
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

    public function view(User $user, Submission $submission): bool
    {
        return $this->memberships->exists($user->id, $submission->form->project->workspace_id);
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $this->memberships->exists($user->id, $submission->form->project->workspace_id);
    }
}
