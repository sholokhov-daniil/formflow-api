<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Application\Contracts\Repository;

use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use Illuminate\Support\Collection;

interface SubmissionRepositoryInterface
{
    /** @return Collection<int, Submission> */
    public function findByFormForUser(string $formId, int $userId): Collection;

    public function createForForm(string $formId): Submission;

    public function delete(Submission $submission): void;
}
