<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FormResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'settings' => $this->settings,
            'active' => $this->active,
            'created_by' => $this->created_by,
        ];
    }
}
