<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Requests;

use App\Modules\FormDefinition\Application\DTO\UpdateFormFieldData;
use App\Modules\FormDefinition\Domain\Form\FieldType;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateFormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $form = $this->route('form');
        $presenceRules = $this->isMethod('PUT') ? ['required'] : ['sometimes', 'required'];

        return [
            'code' => [...$presenceRules, 'string', 'max:100', Rule::unique(FormField::class, 'code')->where('form_id', $form->id)->ignore($this->route('field'))],
            'label' => [...$presenceRules, 'string', 'max:255'],
            'position' => [...$presenceRules, 'integer', 'between:-2147483648,2147483647'],
            'type' => [...$presenceRules, Rule::enum(FieldType::class)],
            'active' => [...$presenceRules, 'boolean'],
            'required' => [...$presenceRules, 'boolean'],
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
                    && ! array_key_exists('code', $data)
                    && ! array_key_exists('label', $data)
                    && ! array_key_exists('position', $data)
                    && ! array_key_exists('type', $data)
                    && ! array_key_exists('active', $data)
                    && ! array_key_exists('required', $data)
                ) {
                    $validator->errors()->add('code', 'Provide at least one field to update.');
                }
            },
        ];
    }

    public function toData(): UpdateFormFieldData
    {
        $validated = $this->validated();

        return new UpdateFormFieldData(
            code: array_key_exists('code', $validated) ? (string) $validated['code'] : null,
            label: array_key_exists('label', $validated) ? (string) $validated['label'] : null,
            position: array_key_exists('position', $validated) ? (int) $validated['position'] : null,
            type: array_key_exists('type', $validated) ? FieldType::from($validated['type']) : null,
            active: array_key_exists('active', $validated) ? (bool) $validated['active'] : null,
            required: array_key_exists('required', $validated) ? (bool) $validated['required'] : null,
        );
    }
}
