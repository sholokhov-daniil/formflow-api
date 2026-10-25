<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\DTO;

use App\Modules\FormDefinition\Domain\Form\FieldType;

final readonly class UpdateFormFieldData
{
    public function __construct(
        public ?string $code = null,
        public ?string $label = null,
        public ?int $position = null,
        public ?FieldType $type = null,
        public ?bool $active = null,
        public ?bool $required = null,
    ) {}
}
