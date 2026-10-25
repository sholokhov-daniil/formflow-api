<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\Contracts\Repository;

use App\Modules\FormDefinition\Application\DTO\CreateFormData;
use App\Modules\FormDefinition\Application\DTO\UpdateFormData;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use Illuminate\Support\Collection;

interface FormRepositoryInterface
{
    /** @return Collection<int, Form> */
    public function findByProjectForUser(string $projectId, int $userId): Collection;

    public function createForProject(CreateFormData $data, string $projectId, int $userId): Form;

    public function update(Form $form, UpdateFormData $data): Form;

    public function delete(Form $form): void;
}
