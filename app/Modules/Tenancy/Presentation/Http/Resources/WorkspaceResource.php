<?php

namespace App\Modules\Tenancy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Данные пространства для API
 */
class WorkspaceResource extends JsonResource
{
    /**
     * Возвращает публичные поля пространства
     *
     * @return array{id: string, name: string, kind: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'kind' => $this->kind,
        ];
    }
}
