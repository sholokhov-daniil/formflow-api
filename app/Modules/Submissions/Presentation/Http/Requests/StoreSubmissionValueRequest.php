<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Presentation\Http\Requests;

use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;
use App\Modules\Submissions\Application\DTO\CreateSubmissionValueData;
use App\Modules\Submissions\Infrastructure\Eloquent\SubmissionValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSubmissionValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $submission = $this->route('submission');

        return [
            'form_field_id' => ['required', 'uuid', Rule::exists(FormField::class, 'id')->where('form_id', $submission->form_id), Rule::unique(SubmissionValue::class, 'form_field_id')->where('submission_id', $submission->id)],
            'value' => ['present', 'nullable'],
        ];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return $this->isJson() ? $this->json()->all() : [];
    }

    public function toData(): CreateSubmissionValueData
    {
        $validated = $this->validated();

        return new CreateSubmissionValueData(
            formFieldId: (string) $validated['form_field_id'],
            value: $validated['value'],
        );
    }
}
