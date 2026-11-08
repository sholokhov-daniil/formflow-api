<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Infrastructure\Repository;

use App\Modules\FormDefinition\Application\Contracts\Repository\CurrentFormFieldVersionLookupInterface;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;

final readonly class CurrentFormFieldVersionRepository implements CurrentFormFieldVersionLookupInterface
{
    public function findCurrentVersionId(string $formId, string $fieldId): ?string
    {
        $versionId = FormField::query()
            ->where('form_id', $formId)
            ->whereKey($fieldId)
            ->value('current_version_id');

        return $versionId === null ? null : (string) $versionId;
    }
}
