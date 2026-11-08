<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\Contracts\Repository;

/** Публичный прикладной контракт для разрешения кодов активных полей формы.
 *
 * @author Daniil S.
 */
interface PublicFormFieldLookupInterface
{
    /** Возвращает идентификаторы активных полей, принадлежащих указанной форме.
     *
     * @param  string  $formId  Идентификатор формы.
     * @param  array<int, string>  $codes  Коды полей из публичного запроса.
     * @return array<string, string> Соответствие кода поля его идентификатору.
     *
     * @author Daniil S.
     */
    public function findActiveIdsByCodes(string $formId, array $codes): array;
}
