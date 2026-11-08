<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Infrastructure\Repository;

use App\Modules\FormDefinition\Application\Contracts\Repository\PublicFormFieldLookupInterface;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;

/** Разрешает публичные коды только среди активных полей указанной формы.
 *
 * @author Daniil S.
 */
final readonly class PublicFormFieldRepository implements PublicFormFieldLookupInterface
{
    /** {@inheritDoc}
     *
     * @author Daniil S.
     */
    public function findActiveIdsByCodes(string $formId, array $codes): array
    {
        return FormField::query()
            ->where('form_id', $formId)
            ->where('active', true)
            ->whereIn('code', $codes)
            ->pluck('id', 'code')
            ->all();
    }
}
