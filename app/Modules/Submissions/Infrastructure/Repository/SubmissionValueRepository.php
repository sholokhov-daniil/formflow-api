<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Infrastructure\Repository;

use App\Modules\Submissions\Application\Contracts\Repository\SubmissionValueRepositoryInterface;
use App\Modules\Submissions\Application\DTO\CreateSubmissionValueData;
use App\Modules\Submissions\Application\DTO\UpdateSubmissionValueData;
use App\Modules\Submissions\Infrastructure\Eloquent\SubmissionValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class SubmissionValueRepository implements SubmissionValueRepositoryInterface
{
    public function findBySubmissionForUser(string $submissionId, int $userId): Collection
    {
        return SubmissionValue::query()
            ->where('submission_id', $submissionId)
            ->whereHas('submission.form.project.workspace.memberships', static fn (Builder $query): Builder => $query->where('user_id', $userId))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function createForSubmission(CreateSubmissionValueData $data, string $submissionId, string $formId): SubmissionValue
    {
        return SubmissionValue::create([
            'submission_id' => $submissionId,
            'form_id' => $formId,
            'form_field_id' => $data->formFieldId,
            'value' => $data->value,
        ]);
    }

    public function update(SubmissionValue $value, UpdateSubmissionValueData $data): SubmissionValue
    {
        $value->value = $data->value;
        $value->save();

        return $value;
    }

    public function delete(SubmissionValue $value): void
    {
        $value->delete();
    }
}
