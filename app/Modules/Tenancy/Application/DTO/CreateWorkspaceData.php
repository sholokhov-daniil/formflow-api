<?php

namespace App\Modules\Tenancy\Application\DTO;

use App\Modules\Tenancy\Domain\Workspace\WorkspaceKind;

final readonly class CreateWorkspaceData
{
    public function __construct(
        public string $name,
        public WorkspaceKind $kind
    )
    {
    }
}
