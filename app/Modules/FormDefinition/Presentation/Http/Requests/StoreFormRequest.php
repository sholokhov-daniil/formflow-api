<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Requests;

use App\Modules\FormDefinition\Application\DTO\CreateFormData;
use Illuminate\Foundation\Http\FormRequest;

final class StoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
            'settings' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return $this->isJson() ? $this->json()->all() : [];
    }

    public function toData(): CreateFormData
    {
        $validated = $this->validated();

        return new CreateFormData(
            name: (string) $validated['name'],
            settings: $validated['settings'] ?? null,
            active: (bool) $validated['active'],
        );
    }
}
