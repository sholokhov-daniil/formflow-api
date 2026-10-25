<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Infrastructure\Eloquent;

use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Проект пространства
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property string|null $description
 */
#[Fillable(['workspace_id', 'name', 'description'])]
final class Project extends Model
{
    use HasUuids;

    /**
     * Возвращает пространство проектов
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id', 'id');
    }

    public function forms(): HasMany
    {
        return $this->hasMany(Form::class, 'project_id', 'id');
    }
}
