<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\DTO;

use App\Modules\FormDefinition\Domain\Form\FieldType;

final readonly class CreateFormFieldData
{
    public function __construct(
        public string $code,
        public string $label,
        public int $position,
        public FieldType $type,
        public bool $active,
        public bool $required,
    ) {}
}
