<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Application\DTO;

final readonly class CreateSubmissionValueData
{
    public function __construct(
        public string $formFieldId,
        public mixed $value,
    ) {}
}
