<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Infrastructure\Repository;

use App\Modules\Submissions\Application\Contracts\Repository\SubmissionRepositoryInterface;
use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class SubmissionRepository implements SubmissionRepositoryInterface
{
    public function findByFormForUser(string $formId, int $userId): Collection
    {
        return Submission::query()
            ->where('form_id', $formId)
            ->whereHas('form.project.workspace.memberships', static fn (Builder $query): Builder => $query->where('user_id', $userId))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function createForForm(string $formId): Submission
    {
        return Submission::create([
            'form_id' => $formId,
        ]);
    }

    public function delete(Submission $submission): void
    {
        $submission->delete();
    }
}
