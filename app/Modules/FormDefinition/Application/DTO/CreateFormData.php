<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\DTO;

final readonly class CreateFormData
{
    public function __construct(
        public string $name,
        public ?array $settings,
        public bool $active,
    ) {}
}
