<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Controller;

use App\Modules\FormDefinition\Application\Contracts\Repository\ProjectWorkspaceRepositoryInterface;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\FormDefinition\Presentation\Http\Requests\StoreProjectRequest;
use App\Modules\FormDefinition\Presentation\Http\Requests\UpdateProjectRequest;
use App\Modules\FormDefinition\Presentation\Http\Resources\ProjectDetailResource;
use App\Modules\FormDefinition\Presentation\Http\Resources\ProjectResource;
use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;

use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * API проектов пространства
 */
class ProjectController extends Controller
{
    /**
     * Возвращает доступные проекты пространства
     *
     * @param User                                $user
     * @param Workspace                           $workspace
     * @param ProjectWorkspaceRepositoryInterface $repository
     *
     * @return AnonymousResourceCollection
     */
    public function index(
        #[CurrentUser] User $user,
        Workspace $workspace,
        ProjectWorkspaceRepositoryInterface $repository
    ): AnonymousResourceCollection {
        return ProjectResource::collection(
            $repository->findByWorkspaceForUser($workspace->id, $user->id)
        );
    }

    /**
     * Создание нового проекта пространства
     *
     * @param StoreProjectRequest                 $request
     * @param Workspace                           $workspace
     * @param ProjectWorkspaceRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function store(
        StoreProjectRequest $request,
        Workspace $workspace,
        ProjectWorkspaceRepositoryInterface $repository
    ): JsonResponse {
        $project = $repository->createForWorkspace($request->toData(), $workspace->id);

        return (new ProjectDetailResource($project))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Возвращает детальную информацию по проекту
     *
     * @param Workspace $workspace
     * @param Project   $project
     *
     * @return ProjectDetailResource
     */
    public function show(Workspace $workspace, Project $project): ProjectDetailResource
    {
        return new ProjectDetailResource($project);
    }

    /**
     * Изменение проекта пространства
     *
     * @param UpdateProjectRequest                $request
     * @param Workspace                           $workspace
     * @param Project                             $project
     * @param ProjectWorkspaceRepositoryInterface $repository
     *
     * @return ProjectDetailResource
     */
    public function update(
        UpdateProjectRequest $request,
        Workspace $workspace,
        Project $project,
        ProjectWorkspaceRepositoryInterface $repository
    ): ProjectDetailResource {
        $project = $repository->update($project, $request->toData());

        return new ProjectDetailResource($project);
    }

    /**
     * Удаление проекта
     *
     * @param Workspace                           $workspace
     * @param Project                             $project
     * @param ProjectWorkspaceRepositoryInterface $repository
     *
     * @return Response
     */
    public function destroy(
        Workspace $workspace,
        Project $project,
        ProjectWorkspaceRepositoryInterface $repository
    ): Response {
        $repository->delete($project);

        return response()->noContent();
    }
}
