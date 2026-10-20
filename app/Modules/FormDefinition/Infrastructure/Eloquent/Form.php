<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Infrastructure\Eloquent;

use DateTime;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Форма проекта
 *
 * @final
 *
 * @property string $id
 * @property string $project_id
 * @property string $name
 * @property int $created_by
 * @property array|null $settings
 * @property bool $active
 * @property DateTime|null $create_at
 * @property DateTime|null $update_at
 */
#[Fillable(['project_id', 'name', 'created_by', 'settings', 'active', 'created_at', 'updated_at'])]
class Form extends Model
{
    use HasUuids;

    /**
     * @return string[]
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'active' => 'boolean',
        ];
    }
}
