<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Infrastructure\Repository;

use App\Modules\FormDefinition\Application\Contracts\Repository\PublicFormFieldLookupInterface;
use App\Modules\FormDefinition\Application\DTO\PublicFormFieldDefinition;
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
    public function findActiveForForm(string $formId): array
    {
        return FormField::query()
            ->where('form_id', $formId)
            ->where('active', true)
            ->get(['id', 'code', 'type', 'required'])
            ->mapWithKeys(static fn (FormField $field): array => [
                $field->code => new PublicFormFieldDefinition(
                    id: $field->id,
                    code: $field->code,
                    type: $field->type,
                    required: $field->required,
                ),
            ])
            ->all();
    }
}
