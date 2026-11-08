<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Application\Actions;

use App\Modules\FormDefinition\Application\Contracts\Repository\PublicFormFieldLookupInterface;
use App\Modules\Submissions\Application\Contracts\Repository\SubmissionRepositoryInterface;
use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use Illuminate\Validation\ValidationException;

/** Координирует создание публичного ответа по кодам активных полей формы.
 *
 * @author Daniil S.
 */
final readonly class CreatePublicSubmissionAction
{
    /** Создаёт отправку после разрешения переданных кодов в поля формы.
     *
     * @param  PublicFormFieldLookupInterface  $fields  Контракт FormDefinition для проверки полей.
     * @param  SubmissionRepositoryInterface  $submissions  Репозиторий отправок.
     *
     * @author Daniil S.
     */
    public function __construct(
        private PublicFormFieldLookupInterface $fields,
        private SubmissionRepositoryInterface $submissions,
    ) {}

    /** Сохраняет отправку с активными полями, соответствующими переданным кодам.
     *
     * @param  string  $formId  Идентификатор формы из публичного токена.
     * @param  array<string, mixed>  $values  Значения по кодам полей.
     * @return Submission Созданная отправка.
     *
     * @throws ValidationException Если передан неизвестный или неактивный код.
     *
     * @author Daniil S.
     */
    public function handle(string $formId, array $values): Submission
    {
        $fieldIdsByCode = $this->fields->findActiveIdsByCodes($formId, array_keys($values));

        if (count($fieldIdsByCode) !== count($values)) {
            throw ValidationException::withMessages([
                'values' => 'One or more field codes are unavailable.',
            ]);
        }

        return $this->submissions->createPublicForForm($formId, $values, $fieldIdsByCode);
    }
}
