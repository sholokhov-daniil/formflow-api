<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\DTO;

use App\Modules\FormDefinition\Domain\Form\FieldType;

/** Снимок публичных правил активного поля формы. */
final readonly class PublicFormFieldDefinition
{
    public function __construct(
        public string $id,
        public string $versionId,
        public string $code,
        public FieldType $type,
        public bool $required,
    ) {}
}
