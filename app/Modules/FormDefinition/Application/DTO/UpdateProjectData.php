<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\DTO;

final readonly class UpdateProjectData
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
    ) {}
}
