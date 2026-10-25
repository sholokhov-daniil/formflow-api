<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Application\DTO;

final readonly class UpdateSubmissionValueData
{
    public function __construct(
        public mixed $value,
    ) {}
}
