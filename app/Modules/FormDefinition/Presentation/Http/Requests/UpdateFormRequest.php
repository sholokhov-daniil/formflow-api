<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Requests;

use App\Modules\FormDefinition\Application\DTO\UpdateFormData;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $presenceRules = $this->isMethod('PUT') ? ['required'] : ['sometimes', 'required'];

        return [
            'name' => [...$presenceRules, 'string', 'max:255'],
            'active' => [...$presenceRules, 'boolean'],
            'settings' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return $this->isJson() ? $this->json()->all() : [];
    }

    /** @return array<int, Closure(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $data = $this->validationData();

                if (
                    $this->isMethod('PATCH')
                    && $validator->errors()->isEmpty()
                    && ! array_key_exists('name', $data)
                    && ! array_key_exists('settings', $data)
                    && ! array_key_exists('active', $data)
                ) {
                    $validator->errors()->add('name', 'Provide at least one field to update.');
                }
            },
        ];
    }

    public function toData(): UpdateFormData
    {
        $validated = $this->validated();

        return new UpdateFormData(
            name: array_key_exists('name', $validated) ? (string) $validated['name'] : null,
            settings: $validated['settings'] ?? null,
            active: array_key_exists('active', $validated) ? (bool) $validated['active'] : null,
            hasSettings: array_key_exists('settings', $validated),
        );
    }
}
