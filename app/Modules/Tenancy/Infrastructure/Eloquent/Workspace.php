<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Eloquent;

use DateTime;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Пространство проектов
 *
 * @property string $id
 * @property string $name
 * @property string $kind
 * @property DateTime|null $create_at
 * @property DateTime|null $updated_at
 */
#[Fillable(['name', 'kind', 'created_at', 'updated_at'])]
final class Workspace extends Model
{
    use HasUuids;

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'workspace_id', 'id');
    }
}
