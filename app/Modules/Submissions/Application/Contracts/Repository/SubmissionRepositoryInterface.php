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

    /** Сохраняет публичную отправку и её значения одной транзакцией.
     *
     * @param  string  $formId  Идентификатор формы.
     * @param  array<string, mixed>  $values  Значения по кодам полей.
     * @param  array<string, string>  $fieldIdsByCode  Соответствие кода поля его ID.
     * @param  array<string, string>  $fieldVersionIdsByCode  Соответствие кода поля принятой версии определения.
     * @return Submission Созданная отправка.
     *
     * @author Daniil S.
     */
    public function createPublicForForm(string $formId, array $values, array $fieldIdsByCode, array $fieldVersionIdsByCode): Submission;

    public function delete(Submission $submission): void;
}
