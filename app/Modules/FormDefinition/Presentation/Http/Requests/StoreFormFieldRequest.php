<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Requests;

use App\Modules\FormDefinition\Application\DTO\CreateFormFieldData;
use App\Modules\FormDefinition\Domain\Form\FieldType;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $form = $this->route('form');

        return [
            'code' => ['required', 'string', 'max:100', Rule::unique(FormField::class, 'code')->where('form_id', $form->id)],
            'label' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'between:-2147483648,2147483647'],
            'type' => ['required', Rule::enum(FieldType::class)],
            'active' => ['required', 'boolean'],
            'required' => ['required', 'boolean'],
        ];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return $this->isJson() ? $this->json()->all() : [];
    }

    public function toData(): CreateFormFieldData
    {
        $validated = $this->validated();

        return new CreateFormFieldData(
            code: (string) $validated['code'],
            label: (string) $validated['label'],
            position: (int) $validated['position'],
            type: FieldType::from($validated['type']),
            active: (bool) $validated['active'],
            required: (bool) $validated['required'],
        );
    }
}
