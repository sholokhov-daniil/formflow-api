<?php

namespace App\Modules\Tenancy\Domain\Workspace;

/**
 * Тип пространства проектов
 */
enum WorkspaceKind: string
{
    case Personal = 'personal';
    case Team = 'team';
}
