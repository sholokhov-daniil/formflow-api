<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\Contracts\Repository;

use App\Modules\FormDefinition\Application\DTO\CreateFormFieldData;
use App\Modules\FormDefinition\Application\DTO\UpdateFormFieldData;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;
use Illuminate\Support\Collection;

interface FormFieldRepositoryInterface
{
    /** @return Collection<int, FormField> */
    public function findByFormForUser(string $formId, int $userId): Collection;

    public function createForForm(CreateFormFieldData $data, string $formId): FormField;

    public function update(FormField $field, UpdateFormFieldData $data): FormField;

    public function delete(FormField $field): void;
}
