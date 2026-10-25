<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Infrastructure\Repository;

use App\Modules\FormDefinition\Application\Contracts\Repository\ProjectWorkspaceRepositoryInterface;
use App\Modules\FormDefinition\Application\DTO\CreateProjectData;
use App\Modules\FormDefinition\Application\DTO\UpdateProjectData;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use Illuminate\Support\Collection;

/**
 * Репозиторий проектов пространств доступные пользователю
 */
final readonly class ProjectWorkspaceRepository implements ProjectWorkspaceRepositoryInterface
{
    /**
     * Возвращает все доступные проекты пространства
     */
    public function findByWorkspaceForUser(string $workspaceId, int $userId): Collection
    {
        return Project::query()
            ->where('workspace_id', $workspaceId)
            ->whereIn(
                'workspace_id',
                WorkspaceMembership::query()
                    ->select('workspace_id')
                    ->where('user_id', $userId)
            )
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * Создание проекта в указанном пространстве
     */
    public function createForWorkspace(CreateProjectData $data, string $workspaceId): Project
    {
        return Project::create([
            'workspace_id' => $workspaceId,
            'name' => $data->name,
            'description' => $data->description,
        ]);
    }

    /**
     * Обновление только переданных свойств проекта
     */
    public function update(Project $project, UpdateProjectData $data): Project
    {
        if ($data->name !== null) {
            $project->name = $data->name;
        }

        if ($data->description !== null) {
            $project->description = $data->description;
        }

        $project->save();

        return $project;
    }

    /**
     * Удаление проекта
     */
    public function delete(Project $project): void
    {
        $project->delete();
    }
}
