<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Controller;

use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Presentation\Http\Requests\StoreWorkspaceRequest;
use App\Modules\Tenancy\Presentation\Http\Requests\UpdateWorkspaceRequest;
use App\Modules\Tenancy\Presentation\Http\Resources\WorkspaceDetailResource;
use App\Modules\Tenancy\Presentation\Http\Resources\WorkspaceResource;

use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * API пространств проектов
 */
class WorkspaceController extends Controller
{
    /**
     * Возвращает доступные пространства текущего пользователя
     *
     * @param User                         $user
     * @param WorkspaceRepositoryInterface $repository
     *
     * @return AnonymousResourceCollection
     */
    public function index(
        #[CurrentUser] User $user,
        WorkspaceRepositoryInterface $repository
    ): AnonymousResourceCollection {
        return WorkspaceResource::collection(
            $repository->accessibleToUser($user->id)
        );
    }

    /**
     * Создание нового пространства проектов
     *
     * @param StoreWorkspaceRequest        $request
     * @param User                         $user
     * @param WorkspaceRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function store(
        StoreWorkspaceRequest $request,
        #[CurrentUser] User $user,
        WorkspaceRepositoryInterface $repository
    ): JsonResponse {
        $workspace = $repository->createForUser(
            data: $request->toData(),
            userId: $user->id
        );

        return (new WorkspaceDetailResource($workspace))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Детальная информация по пространству
     *
     * @param Workspace $workspace
     *
     * @return WorkspaceDetailResource
     */
    public function show(Workspace $workspace): WorkspaceDetailResource
    {
        return new WorkspaceDetailResource($workspace);
    }

    /**
     * Изменения пространства проектов
     *
     * @param UpdateWorkspaceRequest       $request
     * @param Workspace                    $workspace
     * @param WorkspaceRepositoryInterface $repository
     *
     * @return WorkspaceDetailResource
     */
    public function update(
        UpdateWorkspaceRequest $request,
        Workspace $workspace,
        WorkspaceRepositoryInterface $repository
    ): WorkspaceDetailResource {
        $workspace = $repository->update($workspace, $request->toData());
        return new WorkspaceDetailResource($workspace);
    }

    /**
     * Удаление пространства проектов
     *
     * @param Workspace                    $workspace
     * @param WorkspaceRepositoryInterface $repository
     *
     * @return Response
     */
    public function destroy(Workspace $workspace, WorkspaceRepositoryInterface $repository): Response
    {
        $repository->delete($workspace);
        return response()->noContent();
    }
}
