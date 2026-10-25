<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Presentation\Http\Requests;

use App\Modules\Submissions\Application\DTO\UpdateSubmissionValueData;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSubmissionValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'value' => ['present', 'nullable'],
        ];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return $this->isJson() ? $this->json()->all() : [];
    }

    public function toData(): UpdateSubmissionValueData
    {
        $validated = $this->validated();

        return new UpdateSubmissionValueData(
            value: $validated['value'],
        );
    }
}
