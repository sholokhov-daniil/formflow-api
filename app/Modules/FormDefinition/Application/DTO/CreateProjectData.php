<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\DTO;

final readonly class CreateProjectData
{
    public function __construct(
        public string $name,
        public string $description,
    ) {}
}
