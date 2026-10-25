<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Application\Contracts\Repository;

use App\Modules\Submissions\Application\DTO\CreateSubmissionValueData;
use App\Modules\Submissions\Application\DTO\UpdateSubmissionValueData;
use App\Modules\Submissions\Infrastructure\Eloquent\SubmissionValue;
use Illuminate\Support\Collection;

interface SubmissionValueRepositoryInterface
{
    /** @return Collection<int, SubmissionValue> */
    public function findBySubmissionForUser(string $submissionId, int $userId): Collection;

    public function createForSubmission(CreateSubmissionValueData $data, string $submissionId, string $formId): SubmissionValue;

    public function update(SubmissionValue $value, UpdateSubmissionValueData $data): SubmissionValue;

    public function delete(SubmissionValue $value): void;
}
