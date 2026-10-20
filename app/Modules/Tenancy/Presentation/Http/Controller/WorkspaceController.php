<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Controller;

use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
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
     *
     * @param User                         $user
     * @param WorkspaceRepositoryInterface $workspaces
     *
     * @return AnonymousResourceCollection
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
     * Заготовка просмотра пространства
     */
    public function show() {}

    /**
     * Заготовка изменения пространства
     */
    public function update() {}

    /**
     * Заготовка удаления пространства
     */
    public function destroy() {}
}
