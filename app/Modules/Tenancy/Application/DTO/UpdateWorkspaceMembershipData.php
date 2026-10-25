<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\DTO;

final readonly class UpdateWorkspaceMembershipData
{
    public function __construct(
        public ?int $userId = null,
    ) {}
}
