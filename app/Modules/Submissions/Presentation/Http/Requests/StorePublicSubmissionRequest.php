<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Проверяет верхний уровень JSON-запроса публичной отправки.
 *
 * @author Daniil S.
 */
final class StorePublicSubmissionRequest extends FormRequest
{
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
            'values' => ['required', 'array', 'min:1'],
        ];
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
