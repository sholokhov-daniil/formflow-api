<?php

namespace App\Modules\Tenancy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Данные пространства для API
 */
class WorkspaceDetailResource extends JsonResource
{
    /**
     * Возвращает публичные поля пространства
     *
     * @return array{
     *     id: string,
     *     name: string,
     *     kind: string,
     *     created_at: \DateTimeInterface|null,
     *     updated_at: \DateTimeInterface|null
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'kind' => $this->kind,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
