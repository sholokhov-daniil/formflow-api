<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\Contracts\Repository;

interface CurrentFormFieldVersionLookupInterface
{
    public function findCurrentVersionId(string $formId, string $fieldId): ?string;
}
