<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Сериализует только публичные поля активной формы.
 *
 * @author Daniil S.
 */
final class PublicFormSchemaResource extends JsonResource
{
    /** Формирует схему без внутренних идентификаторов и настроек формы.
     *
     * @param  Request  $request  Текущий HTTP-запрос.
     * @return array<string, mixed> Публичное представление формы и активных полей.
     *
     * @author Daniil S.
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'fields' => $this->fields
                ->where('active', true)
                ->sortBy([['position', 'asc'], ['id', 'asc']])
                ->values()
                ->map(static fn ($field): array => [
                    'code' => $field->code,
                    'label' => $field->label,
                    'position' => $field->position,
                    'type' => $field->type->value,
                    'required' => $field->required,
                ]),
        ];
    }
}
