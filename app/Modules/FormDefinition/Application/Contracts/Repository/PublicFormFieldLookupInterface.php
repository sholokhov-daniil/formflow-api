<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\Contracts\Repository;

use App\Modules\FormDefinition\Application\DTO\PublicFormFieldDefinition;

/** Публичный прикладной контракт для разрешения кодов активных полей формы.
 *
 * @author Daniil S.
 */
interface PublicFormFieldLookupInterface
{
    /** Возвращает определения активных полей указанной формы.
     *
     * @param  string  $formId  Идентификатор формы.
     * @return array<string, PublicFormFieldDefinition> Соответствие кода его публичному определению.
     *
     * @author Daniil S.
     */
    public function findActiveForForm(string $formId): array;
}
