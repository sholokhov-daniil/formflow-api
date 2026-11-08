<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Application\Actions;

use App\Modules\FormDefinition\Application\Contracts\Repository\PublicFormFieldLookupInterface;
use App\Modules\FormDefinition\Application\DTO\PublicFormFieldDefinition;
use App\Modules\FormDefinition\Domain\Form\FieldType;
use App\Modules\Submissions\Application\Contracts\Repository\SubmissionRepositoryInterface;
use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use Illuminate\Validation\ValidationException;

/** Координирует создание публичного ответа по кодам активных полей формы.
 *
 * @author Daniil S.
 */
final readonly class CreatePublicSubmissionAction
{
    private const int MAX_VALUE_LENGTH = 10000;

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

    /** Сохраняет отправку с активными полями после проверки обязательности и типов.
     *
     * @param  string  $formId  Идентификатор формы из публичного токена.
     * @param  array<string, mixed>  $values  Значения по кодам полей.
     * @return Submission Созданная отправка.
     *
     * @throws ValidationException Если схема или значение не удовлетворяют правилам публикации.
     *
     * @author Daniil S.
     */
    public function handle(string $formId, array $values): Submission
    {
        $activeFieldsByCode = $this->fields->findActiveForForm($formId);
        $fieldDefinitions = array_intersect_key($activeFieldsByCode, $values);

        if (count($fieldDefinitions) !== count($values)) {
            $this->throwUnavailableFields($values, $activeFieldsByCode);
        }

        $errors = [];

        foreach ($fieldDefinitions as $code => $definition) {
            $this->validateField($definition, $values[$code], $errors);
        }

        foreach ($activeFieldsByCode as $definition) {
            if ($definition->required && ! array_key_exists($definition->code, $values)) {
                $errors['values.'.$definition->code][] = 'This field is required.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $fieldIdsByCode = array_map(
            static fn (PublicFormFieldDefinition $definition): string => $definition->id,
            $fieldDefinitions,
        );

        return $this->submissions->createPublicForForm($formId, $values, $fieldIdsByCode);
    }

    /** @param array<string, mixed> $values
     * @param  array<string, PublicFormFieldDefinition>  $definitions
     */
    private function throwUnavailableFields(array $values, array $definitions): never
    {
        $errors = [];

        foreach (array_keys($values) as $code) {
            if (! isset($definitions[$code])) {
                $errors['values.'.$code][] = 'This field is unavailable.';
            }
        }

        throw ValidationException::withMessages($errors);
    }

    /** @param array<string, list<string>> $errors */
    private function validateField(PublicFormFieldDefinition $field, mixed $value, array &$errors): void
    {
        $attribute = 'values.'.$field->code;

        if ($value === null) {
            $errors[$attribute][] = 'Null values are not supported.';

            return;
        }

        if (in_array($field->type, [FieldType::Text, FieldType::Textarea, FieldType::Email], true)) {
            if (! is_string($value)) {
                $errors[$attribute][] = 'This field must be a string.';

                return;
            }

            if (trim($value) === '') {
                $errors[$attribute][] = 'This field cannot be empty.';

                return;
            }

            if (mb_strlen($value) > self::MAX_VALUE_LENGTH) {
                $errors[$attribute][] = 'This field may not be greater than 10000 characters.';

                return;
            }

            if ($field->type === FieldType::Email && (mb_strlen($value) > 254 || filter_var($value, FILTER_VALIDATE_EMAIL) === false)) {
                $errors[$attribute][] = 'This field must be a valid email address of at most 254 characters.';
            }

            return;
        }

        if ($field->type === FieldType::Number) {
            if (! is_int($value) && (! is_float($value) || ! is_finite($value))) {
                $errors[$attribute][] = 'This field must be a finite JSON number.';
            }

            return;
        }

        if ($field->type === FieldType::Boolean) {
            if (! is_bool($value)) {
                $errors[$attribute][] = 'This field must be a JSON boolean.';
            }

            return;
        }

        $errors[$attribute][] = 'This field type is not supported for public submissions.';
    }
}
