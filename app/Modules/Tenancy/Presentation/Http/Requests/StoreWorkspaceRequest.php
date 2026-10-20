<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Requests;

use App\Modules\Tenancy\Application\DTO\CreateWorkspaceData;
use App\Modules\Tenancy\Domain\Workspace\WorkspaceKind;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос на создание нового пространства
 *
 * @final
 */
final class StoreWorkspaceRequest extends FormRequest
{
    /**
     * Право на создание уже проверяет middleware маршрута
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации запроса
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::enum(WorkspaceKind::class)],
        ];
    }

    /**
     * Преобразовывает данные в DTO объект
     *
     * @return CreateWorkspaceData
     */
    public function toData(): CreateWorkspaceData
    {
        $validated = $this->validated();

        return new CreateWorkspaceData(
            name: (string) $validated['name'],
            kind: WorkspaceKind::from((string) $validated['kind']),
        );
    }
}
