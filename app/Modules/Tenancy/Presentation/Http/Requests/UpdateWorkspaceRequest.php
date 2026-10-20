<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Requests;

use App\Modules\Tenancy\Application\DTO\UpdateWorkspaceData;
use App\Modules\Tenancy\Domain\Workspace\WorkspaceKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Illuminate\Validation\Validator;

/**
 * Запрос на обновление пространства проектов
 */
final class UpdateWorkspaceRequest extends FormRequest
{
    /**
     * Право обновления проверяет middleware can:update,workspace.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации изменяемых свойств для PUT и PATCH.
     *
     * @return array{
     *     name: array<int, string>,
     *     kind: array<int, string|EnumRule>
     * }
     */
    public function rules(): array
    {
        $presenceRules = $this->isMethod('PUT')
            ? ['required']
            : ['sometimes', 'required'];

        return [
            'name' => [...$presenceRules, 'string', 'max:255'],
            'kind' => [...$presenceRules, Rule::enum(WorkspaceKind::class)],
        ];
    }

    /**
     * Возвращает данные только из JSON-тела запроса, без query string и form data.
     *
     * @return array<array-key, mixed>
     */
    public function validationData(): array
    {
        return $this->isJson() ? $this->json()->all() : [];
    }

    /**
     * Добавляет проверку, что PATCH-запрос содержит хотя бы одно изменяемое свойство.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $data = $this->validationData();

                if (
                    $this->isMethod('PATCH')
                    && $validator->errors()->count() === 0
                    && ! array_key_exists('name', $data)
                    && ! array_key_exists('kind', $data)
                ) {
                    $validator->errors()->add('name', 'Provide at least one field to update.');
                }
            },
        ];
    }

    /**
     * Преобразует проверенные данные запроса в DTO обновления пространства.
     * Отсутствующие в PATCH-запросе свойства остаются null.
     */
    public function toData(): UpdateWorkspaceData
    {
        $validated = $this->validated();

        return new UpdateWorkspaceData(
            name: array_key_exists('name', $validated)
                ? (string) $validated['name']
                : null,
            kind: array_key_exists('kind', $validated)
                ? WorkspaceKind::from((string) $validated['kind'])
                : null,
        );
    }
}
