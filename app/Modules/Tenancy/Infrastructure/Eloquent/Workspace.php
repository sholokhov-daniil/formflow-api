<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Infrastructure\Eloquent;

use DateTime;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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
}
