<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Infrastructure\Policies;

use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use App\Modules\Submissions\Infrastructure\Eloquent\SubmissionValue;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;

final readonly class SubmissionValuePolicy
{
    public function __construct(private WorkspaceMembershipRepositoryInterface $memberships) {}

    public function viewAny(User $user, Submission $submission): bool
    {
        return $this->memberships->exists($user->id, $submission->form->project->workspace_id);
    }

    public function create(User $user, Submission $submission): bool
    {
        return $this->memberships->exists($user->id, $submission->form->project->workspace_id);
    }

    public function view(User $user, SubmissionValue $value): bool
    {
        return $this->memberships->exists($user->id, $value->submission->form->project->workspace_id);
    }

    public function update(User $user, SubmissionValue $value): bool
    {
        return $this->memberships->exists($user->id, $value->submission->form->project->workspace_id);
    }

    public function delete(User $user, SubmissionValue $value): bool
    {
        return $this->memberships->exists($user->id, $value->submission->form->project->workspace_id);
    }
}
