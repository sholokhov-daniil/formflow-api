<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\DTO;

use App\Modules\Tenancy\Domain\Workspace\WorkspaceKind;

/**
 * Данные обновления пространства проектов
 */
final readonly class UpdateWorkspaceData
{
    public function __construct(
        public ?string $name = null,
        public ?WorkspaceKind $kind = null,
    ) {}
}
