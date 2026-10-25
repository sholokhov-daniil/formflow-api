<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FormFieldResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'form_id' => $this->form_id,
            'code' => $this->code,
            'label' => $this->label,
            'position' => $this->position,
            'type' => $this->type->value,
            'active' => $this->active,
            'required' => $this->required,
        ];
    }
}
