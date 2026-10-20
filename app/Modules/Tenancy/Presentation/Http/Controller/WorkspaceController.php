<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Controller;

use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Presentation\Http\Resources\WorkspaceDetailResource;
use App\Modules\Tenancy\Presentation\Http\Resources\WorkspaceResource;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * API пространств проектов
 */
class WorkspaceController extends Controller
{
    /**
     * Возвращает доступные пространства текущего пользователя
     */
    public function index(
        #[CurrentUser] User $user,
        WorkspaceRepositoryInterface $workspaces
    ): AnonymousResourceCollection {
        return WorkspaceResource::collection(
            $workspaces->accessibleToUser($user->id)
        );
    }

    /**
     * Заготовка создания пространства
     */
    public function store() {}

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
     * Заготовка изменения пространства
     */
    public function update() {}

    /**
     * Заготовка удаления пространства
     */
    public function destroy() {}
}
