<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\Contracts\Repository;

use App\Modules\FormDefinition\Application\DTO\CreateProjectData;
use App\Modules\FormDefinition\Application\DTO\UpdateProjectData;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;

use Illuminate\Support\Collection;

/**
 * Хранилище проектов
 */
interface ProjectWorkspaceRepositoryInterface
{
    /**
     * Возвращает список проектов по пространству доступных пользователю
     *
     * @return Collection<int, Project>
     */
    public function findByWorkspaceForUser(string $workspaceId, int $userId): Collection;

    /**
     * Удаление проекта
     *
     * @param Project $project
     *
     * @return void
     */
    public function delete(Project $project): void;

    /**
     * Создание нового проекта
     *
     * @param CreateProjectData $data
     * @param string            $workspaceId
     *
     * @return Project
     */
    public function createForWorkspace(CreateProjectData $data, string $workspaceId): Project;

    /**
     * Обновление проекта
     *
     * @param Project           $project
     * @param UpdateProjectData $data
     *
     * @return Project
     */
    public function update(Project $project, UpdateProjectData $data): Project;
}
