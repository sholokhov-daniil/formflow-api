<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\DTO;

final readonly class UpdateFormData
{
    public function __construct(
        public ?string $name = null,
        public ?array $settings = null,
        public ?bool $active = null,
        public bool $hasSettings = false,
    ) {}
}
