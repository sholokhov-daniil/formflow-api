<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Проверяет верхний уровень JSON-запроса публичной отправки.
 *
 * @author Daniil S.
 */
final class StorePublicSubmissionRequest extends FormRequest
{
    private const int MAX_PUBLIC_VALUES = 100;

    private const int MAX_PAYLOAD_BYTES = 1048576;

    /** Публичный токен авторизуется middleware маршрута.
     *
     * @return bool Запрос разрешён после обработки middleware.
     *
     * @author Daniil S.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** Требует непустую карту значений.
     *
     * @return array<string, mixed> Правила верхнего уровня запроса.
     *
     * @author Daniil S.
     */
    public function rules(): array
    {
        return [
            'values' => ['required', 'array', 'min:1', 'max:'.self::MAX_PUBLIC_VALUES],
        ];
    }

    /** Отклоняет списки, которые не могут однозначно адресовать поля кодом.
     *
     * @return array<array-key, mixed> Правила проверки payload.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (strlen($this->getContent()) > self::MAX_PAYLOAD_BYTES) {
                $validator->errors()->add('values', 'The request body may not be greater than 1048576 bytes.');
            }

            $values = $this->validationData()['values'] ?? null;

            if (! is_array($values)) {
                return;
            }

            foreach (array_keys($values) as $code) {
                if (! is_string($code)) {
                    $validator->errors()->add('values', 'Values must be an object keyed by field code.');

                    return;
                }
            }
        }];
    }

    /** Ограничивает входные данные JSON-телом.
     *
     * @return array<array-key, mixed> JSON-данные запроса либо пустой массив.
     *
     * @author Daniil S.
     */
    public function validationData(): array
    {
        return $this->isJson() ? $this->json()->all() : [];
    }

    /** Возвращает проверенные значения, адресованные кодами полей.
     *
     * @return array<string, mixed> Значения из JSON-объекта `values`.
     *
     * @author Daniil S.
     */
    public function values(): array
    {
        return $this->validated('values');
    }
}
