<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return $this->isJson() ? $this->json()->all() : [];
    }
}
