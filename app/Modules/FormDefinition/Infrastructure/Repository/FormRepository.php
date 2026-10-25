<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Infrastructure\Repository;

use App\Modules\FormDefinition\Application\Contracts\Repository\FormRepositoryInterface;
use App\Modules\FormDefinition\Application\DTO\CreateFormData;
use App\Modules\FormDefinition\Application\DTO\UpdateFormData;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class FormRepository implements FormRepositoryInterface
{
    public function findByProjectForUser(string $projectId, int $userId): Collection
    {
        return Form::query()
            ->where('project_id', $projectId)
            ->whereHas('project.workspace.memberships', static fn (Builder $query): Builder => $query->where('user_id', $userId))
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function createForProject(CreateFormData $data, string $projectId, int $userId): Form
    {
        return Form::create([
            'project_id' => $projectId,
            'name' => $data->name,
            'settings' => $data->settings,
            'active' => $data->active,
            'created_by' => $userId,
        ]);
    }

    public function update(Form $form, UpdateFormData $data): Form
    {
        if ($data->name !== null) {
            $form->name = $data->name;
        }

        if ($data->hasSettings) {
            $form->settings = $data->settings;
        }

        if ($data->active !== null) {
            $form->active = $data->active;
        }

        $form->save();

        return $form;
    }

    public function delete(Form $form): void
    {
        $form->delete();
    }
}
